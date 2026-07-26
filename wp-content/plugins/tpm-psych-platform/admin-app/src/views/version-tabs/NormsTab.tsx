import { Fragment, useEffect, useState } from 'react'
import { normsApi, scalesApi } from '../../api/endpoints'
import { STANDARD_SCORE_TYPES, type NormEntry, type NormSet, type Scale, type StandardScoreType } from '../../api/types'
import { ErrorBanner } from '../../components/ErrorBanner'
import { JsonField } from '../../components/JsonField'
import { StatusBadge } from '../../components/StatusBadge'

// isDraft-ul versiunii nu gatează crearea etaloanelor — acestea au propria
// versionare, independentă de versiunea instrumentului (vezi hint-ul de mai
// jos); fiecare norm_set își controlează singur mutabilitatea prin statusul
// propriu, verificat per-set în NormEntriesPanel.
export function NormsTab({ versionId }: { versionId: number; isDraft: boolean }) {
  const [normSets, setNormSets] = useState<NormSet[]>([])
  const [scales, setScales] = useState<Scale[]>([])
  const [error, setError] = useState<unknown>(null)
  const [expandedSetId, setExpandedSetId] = useState<number | null>(null)

  function reload() {
    normsApi.listSets(versionId).then(setNormSets).catch(setError)
    scalesApi.list(versionId).then(setScales).catch(setError)
  }

  useEffect(reload, [versionId])

  async function publishSet(id: number) {
    if (!confirm('Publici acest etalon? Devine imutabil și orice alt etalon activ al aceleiași versiuni va fi depreciat.'))
      return
    try {
      await normsApi.publishSet(id)
      reload()
    } catch (err) {
      setError(err)
    }
  }

  return (
    <div>
      <ErrorBanner error={error} />
      <p className="tpm-hint">
        Etaloanele au propria versionare, independentă de versiunea instrumentului — pot fi adăugate oricând, dar
        odată publicate devin imutabile la fel ca o versiune de instrument.
      </p>

      <AddNormSetForm versionId={versionId} onCreated={reload} onError={setError} />

      {normSets.length === 0 ? (
        <p className="tpm-muted">Niciun etalon definit încă.</p>
      ) : (
        <table>
          <thead>
            <tr>
              <th>Etichetă</th>
              <th>Versiune</th>
              <th>Stare</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {normSets.map((set) => (
              <Fragment key={set.id}>
                <tr>
                  <td>{set.label}</td>
                  <td>{set.version_label}</td>
                  <td>
                    <StatusBadge status={set.status} />
                  </td>
                  <td>
                    <button onClick={() => setExpandedSetId(expandedSetId === set.id ? null : set.id)}>
                      {expandedSetId === set.id ? 'Ascunde intrări' : 'Intrări etalon'}
                    </button>{' '}
                    {set.status === 'draft' && <button onClick={() => publishSet(set.id)}>Publică etalonul</button>}
                  </td>
                </tr>
                {expandedSetId === set.id && (
                  <tr>
                    <td colSpan={4}>
                      <NormEntriesPanel
                        normSetId={set.id}
                        scales={scales}
                        isDraft={set.status === 'draft'}
                        onError={setError}
                      />
                    </td>
                  </tr>
                )}
              </Fragment>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}

function AddNormSetForm({
  versionId,
  onCreated,
  onError,
}: {
  versionId: number
  onCreated: () => void
  onError: (e: unknown) => void
}) {
  const [label, setLabel] = useState('')
  const [versionLabel, setVersionLabel] = useState('1.0.0')
  const [criteria, setCriteria] = useState<Record<string, unknown>>({})
  const [saving, setSaving] = useState(false)

  async function submit(e: React.FormEvent) {
    e.preventDefault()
    setSaving(true)
    try {
      await normsApi.createSet(versionId, {
        label,
        version_label: versionLabel,
        applicable_criteria: Object.keys(criteria).length > 0 ? criteria : null,
      })
      setLabel('')
      onCreated()
    } catch (err) {
      onError(err)
    } finally {
      setSaving(false)
    }
  }

  return (
    <form className="tpm-panel" onSubmit={submit}>
      <label className="tpm-field">
        <span>Etichetă (ex. „Adulți 18-40, populație clinică RO”) *</span>
        <input value={label} onChange={(e) => setLabel(e.target.value)} required />
      </label>
      <label className="tpm-field">
        <span>Etichetă versiune etalon *</span>
        <input value={versionLabel} onChange={(e) => setVersionLabel(e.target.value)} required />
      </label>
      <JsonField label="Criterii de aplicabilitate (JSON, opțional)" value={criteria} onChange={setCriteria} rows={3} />
      <button className="primary" type="submit" disabled={saving}>
        {saving ? 'Se salvează…' : '+ Adaugă etalon (draft)'}
      </button>
    </form>
  )
}

function NormEntriesPanel({
  normSetId,
  scales,
  isDraft,
  onError,
}: {
  normSetId: number
  scales: Scale[]
  isDraft: boolean
  onError: (e: unknown) => void
}) {
  const [entries, setEntries] = useState<NormEntry[] | null>(null)
  const [scaleId, setScaleId] = useState<number | ''>(scales[0]?.id ?? '')
  const [rawMin, setRawMin] = useState(0)
  const [rawMax, setRawMax] = useState(0)
  const [scoreType, setScoreType] = useState<StandardScoreType>('t_score')
  const [scoreValue, setScoreValue] = useState(0)
  const [interpretationCode, setInterpretationCode] = useState('')
  const [saving, setSaving] = useState(false)

  function reload() {
    normsApi.listEntries(normSetId).then(setEntries).catch(onError)
  }

  useEffect(reload, [normSetId])

  async function add() {
    if (scaleId === '') return
    setSaving(true)
    try {
      await normsApi.createEntry(normSetId, {
        scale_id: scaleId,
        raw_score_min: rawMin,
        raw_score_max: rawMax,
        standard_score_type: scoreType,
        standard_score_value: scoreValue,
        interpretation_range_code: interpretationCode || null,
      })
      reload()
    } catch (err) {
      onError(err)
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="tpm-panel">
      {entries === null ? (
        <p>Se încarcă…</p>
      ) : entries.length === 0 ? (
        <p className="tpm-muted">Nicio intrare încă.</p>
      ) : (
        <table>
          <thead>
            <tr>
              <th>Scală</th>
              <th>Interval scor brut</th>
              <th>Scor standardizat</th>
              <th>Cod interpretare</th>
            </tr>
          </thead>
          <tbody>
            {entries.map((e) => (
              <tr key={e.id}>
                <td>{scales.find((s) => s.id === e.scale_id)?.code ?? e.scale_id}</td>
                <td>
                  {e.raw_score_min} – {e.raw_score_max}
                </td>
                <td>
                  {e.standard_score_type} = {e.standard_score_value}
                </td>
                <td>{e.interpretation_range_code ?? '—'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}

      {isDraft && (
        <div className="tpm-toolbar" style={{ flexWrap: 'wrap' }}>
          <select value={scaleId} onChange={(e) => setScaleId(e.target.value === '' ? '' : Number(e.target.value))}>
            {scales.map((s) => (
              <option key={s.id} value={s.id}>
                {s.code}
              </option>
            ))}
          </select>
          <input type="number" value={rawMin} onChange={(e) => setRawMin(Number(e.target.value))} placeholder="min" style={{ width: 70 }} />
          <input type="number" value={rawMax} onChange={(e) => setRawMax(Number(e.target.value))} placeholder="max" style={{ width: 70 }} />
          <select value={scoreType} onChange={(e) => setScoreType(e.target.value as StandardScoreType)}>
            {STANDARD_SCORE_TYPES.map((t) => (
              <option key={t} value={t}>
                {t}
              </option>
            ))}
          </select>
          <input
            type="number"
            value={scoreValue}
            onChange={(e) => setScoreValue(Number(e.target.value))}
            placeholder="valoare"
            style={{ width: 90 }}
          />
          <input
            value={interpretationCode}
            onChange={(e) => setInterpretationCode(e.target.value)}
            placeholder="cod interpretare (ex. low)"
          />
          <button onClick={add} disabled={saving}>
            + Adaugă intrare
          </button>
        </div>
      )}
    </div>
  )
}
