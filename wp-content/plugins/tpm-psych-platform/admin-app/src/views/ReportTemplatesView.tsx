import { useEffect, useState } from 'react'
import { instrumentsApi, reportTemplatesApi } from '../api/endpoints'
import type { Instrument, ReportTemplate } from '../api/types'
import { ErrorBanner } from '../components/ErrorBanner'
import { JsonField } from '../components/JsonField'
import { StatusBadge } from '../components/StatusBadge'

// Organizația implicită (id=1), seed-uită la activarea pluginului — vezi
// Migration_2026_01_000_Initial::seedDefaultOrganization(). MVP-ul are o
// singură organizație; selectorul de organizație vine odată cu modulul SaaS.
const DEFAULT_ORGANIZATION_ID = 1

const TEMPLATE_PLACEHOLDER = {
  interpretation_texts: [
    { interpretation_range_code: 'low', title: 'Nivel scăzut', body: 'Text autorizat pentru nivel scăzut.' },
    { interpretation_range_code: 'high', title: 'Nivel ridicat', body: 'Text autorizat pentru nivel ridicat.' },
  ],
}

export function ReportTemplatesView() {
  const [templates, setTemplates] = useState<ReportTemplate[] | null>(null)
  const [instruments, setInstruments] = useState<Instrument[]>([])
  const [error, setError] = useState<unknown>(null)
  const [showForm, setShowForm] = useState(false)

  function reload() {
    reportTemplatesApi.list(DEFAULT_ORGANIZATION_ID).then(setTemplates).catch(setError)
    instrumentsApi.list().then(setInstruments).catch(setError)
  }

  useEffect(reload, [])

  async function publish(id: number) {
    if (!confirm('Publici acest șablon? Devine imutabil și orice alt șablon activ (același instrument sau generic) va fi depreciat.'))
      return
    try {
      await reportTemplatesApi.publish(id)
      reload()
    } catch (err) {
      setError(err)
    }
  }

  return (
    <div>
      <div className="tpm-toolbar">
        <h2 style={{ margin: 0 }}>Șabloane de raport</h2>
        <button className="primary" onClick={() => setShowForm((v) => !v)}>
          {showForm ? 'Anulează' : '+ Șablon nou'}
        </button>
      </div>

      <ErrorBanner error={error} />

      {showForm && (
        <CreateTemplateForm
          instruments={instruments}
          onCreated={() => {
            setShowForm(false)
            reload()
          }}
          onError={setError}
        />
      )}

      {templates === null ? (
        <p>Se încarcă…</p>
      ) : templates.length === 0 ? (
        <p className="tpm-muted">Niciun șablon definit încă.</p>
      ) : (
        <table>
          <thead>
            <tr>
              <th>Versiune</th>
              <th>Instrument</th>
              <th>Blocuri de text</th>
              <th>Stare</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {templates.map((t) => (
              <tr key={t.id}>
                <td>{t.version_label}</td>
                <td>{instruments.find((i) => i.id === t.instrument_id)?.code ?? 'generic'}</td>
                <td>{t.layout_definition.interpretation_texts?.length ?? 0}</td>
                <td>
                  <StatusBadge status={t.status} />
                </td>
                <td>{t.status === 'draft' && <button onClick={() => publish(t.id)}>Publică</button>}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}

function CreateTemplateForm({
  instruments,
  onCreated,
  onError,
}: {
  instruments: Instrument[]
  onCreated: () => void
  onError: (e: unknown) => void
}) {
  const [instrumentId, setInstrumentId] = useState<number | ''>('')
  const [versionLabel, setVersionLabel] = useState('1.0.0')
  const [layout, setLayout] = useState<Record<string, unknown>>(TEMPLATE_PLACEHOLDER)
  const [saving, setSaving] = useState(false)

  async function submit(e: React.FormEvent) {
    e.preventDefault()
    setSaving(true)
    try {
      await reportTemplatesApi.create(DEFAULT_ORGANIZATION_ID, {
        instrument_id: instrumentId === '' ? null : instrumentId,
        version_label: versionLabel,
        layout_definition: layout,
      })
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
        <span>Instrument (opțional — gol = șablon generic)</span>
        <select value={instrumentId} onChange={(e) => setInstrumentId(e.target.value === '' ? '' : Number(e.target.value))}>
          <option value="">(generic)</option>
          {instruments.map((i) => (
            <option key={i.id} value={i.id}>
              {i.code}
            </option>
          ))}
        </select>
      </label>
      <label className="tpm-field">
        <span>Etichetă versiune șablon *</span>
        <input value={versionLabel} onChange={(e) => setVersionLabel(e.target.value)} required />
      </label>
      <JsonField label="Definiție șablon (interpretation_texts, JSON)" value={layout} onChange={setLayout} rows={10} />
      <p className="tpm-hint">
        Fiecare bloc leagă un <code>interpretation_range_code</code> (definit la intrările etalonului) de un titlu și un
        text autorizat pe care ai dreptul să-l reproduci în raport.
      </p>
      <button className="primary" type="submit" disabled={saving}>
        {saving ? 'Se salvează…' : '+ Creează șablon (draft)'}
      </button>
    </form>
  )
}
