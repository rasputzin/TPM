import { Fragment, useEffect, useState } from 'react'
import { scalesApi, scoringRulesApi } from '../../api/endpoints'
import { RULE_TYPES, type RuleType, type Scale, type ScoringRule } from '../../api/types'
import { ErrorBanner } from '../../components/ErrorBanner'
import { JsonField } from '../../components/JsonField'
import { StatusBadge } from '../../components/StatusBadge'

const FORMULA_PLACEHOLDER: Record<RuleType, Record<string, unknown>> = {
  sum: { item_codes: ['Q1', 'Q2'] },
  weighted_sum: { weights: { Q1: 2, Q2: 0.5 } },
  formula: { expression: '(Q1 + Q2) * 2 - Q3' },
  conditional: {
    conditions: [{ if_item: 'Q1', equals: 1, then_value: 10 }],
    default: 0,
  },
  lookup: { item_code: 'Q1', table: [{ match: 0, value: 0 }, { match: 1, value: 5 }] },
}

export function ScalesTab({ versionId, isDraft }: { versionId: number; isDraft: boolean }) {
  const [scales, setScales] = useState<Scale[]>([])
  const [error, setError] = useState<unknown>(null)
  const [expandedScaleId, setExpandedScaleId] = useState<number | null>(null)

  function reload() {
    scalesApi.list(versionId).then(setScales).catch(setError)
  }

  useEffect(reload, [versionId])

  return (
    <div>
      <ErrorBanner error={error} />

      {isDraft && <AddScaleForm versionId={versionId} onCreated={reload} onError={setError} />}

      {scales.length === 0 ? (
        <p className="tpm-muted">Nicio scală definită încă.</p>
      ) : (
        <table>
          <thead>
            <tr>
              <th>Cod</th>
              <th>Denumire</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {scales.map((scale) => (
              <Fragment key={scale.id}>
                <tr>
                  <td>{scale.code}</td>
                  <td>{scale.name}</td>
                  <td>
                    <button onClick={() => setExpandedScaleId(expandedScaleId === scale.id ? null : scale.id)}>
                      {expandedScaleId === scale.id ? 'Ascunde reguli' : 'Reguli de cotare'}
                    </button>
                  </td>
                </tr>
                {expandedScaleId === scale.id && (
                  <tr>
                    <td colSpan={3}>
                      <ScoringRulesPanel scaleId={scale.id} isDraft={isDraft} onError={setError} />
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

function AddScaleForm({
  versionId,
  onCreated,
  onError,
}: {
  versionId: number
  onCreated: () => void
  onError: (e: unknown) => void
}) {
  const [code, setCode] = useState('')
  const [name, setName] = useState('')
  const [saving, setSaving] = useState(false)

  async function submit(e: React.FormEvent) {
    e.preventDefault()
    setSaving(true)
    try {
      await scalesApi.create(versionId, { code, name })
      setCode('')
      setName('')
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
        <span>Cod scală (unic în versiune) *</span>
        <input value={code} onChange={(e) => setCode(e.target.value)} placeholder="ex. TOTAL" required />
      </label>
      <label className="tpm-field">
        <span>Denumire *</span>
        <input value={name} onChange={(e) => setName(e.target.value)} required />
      </label>
      <button className="primary" type="submit" disabled={saving}>
        {saving ? 'Se salvează…' : '+ Adaugă scală'}
      </button>
    </form>
  )
}

function ScoringRulesPanel({
  scaleId,
  isDraft,
  onError,
}: {
  scaleId: number
  isDraft: boolean
  onError: (e: unknown) => void
}) {
  const [rules, setRules] = useState<ScoringRule[] | null>(null)
  const [ruleType, setRuleType] = useState<RuleType>('sum')
  const [formulaDefinition, setFormulaDefinition] = useState<Record<string, unknown>>(FORMULA_PLACEHOLDER.sum)
  const [saving, setSaving] = useState(false)

  function reload() {
    scoringRulesApi.list(scaleId).then(setRules).catch(onError)
  }

  useEffect(reload, [scaleId])

  function changeRuleType(next: RuleType) {
    setRuleType(next)
    setFormulaDefinition(FORMULA_PLACEHOLDER[next])
  }

  async function save() {
    setSaving(true)
    try {
      await scoringRulesApi.create(scaleId, { rule_type: ruleType, formula_definition: formulaDefinition })
      reload()
    } catch (err) {
      onError(err)
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="tpm-panel">
      {rules === null ? (
        <p>Se încarcă…</p>
      ) : rules.length === 0 ? (
        <p className="tpm-muted">Nicio regulă de cotare încă — scala nu poate fi folosită la publicare fără una.</p>
      ) : (
        <table>
          <thead>
            <tr>
              <th>Tip</th>
              <th>Definiție</th>
              <th>Motor</th>
              <th>Stare</th>
            </tr>
          </thead>
          <tbody>
            {rules.map((r) => (
              <tr key={r.id}>
                <td>{r.rule_type}</td>
                <td>
                  <code>{JSON.stringify(r.formula_definition)}</code>
                </td>
                <td>{r.engine_version}</td>
                <td>
                  <StatusBadge status={r.status} />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}

      {isDraft && (
        <div>
          <label className="tpm-field">
            <span>Tip regulă nouă</span>
            <select value={ruleType} onChange={(e) => changeRuleType(e.target.value as RuleType)}>
              {RULE_TYPES.map((t) => (
                <option key={t} value={t}>
                  {t}
                </option>
              ))}
            </select>
          </label>
          <JsonField label="Definiție (formula_definition)" value={formulaDefinition} onChange={setFormulaDefinition} />
          <p className="tpm-hint">
            Codurile de item trebuie să existe deja în versiune — validate de server la salvare. Vezi exemple pentru
            fiecare tip în FormulaDefinitionValidator.php.
          </p>
          <button className="primary" onClick={save} disabled={saving}>
            {saving ? 'Se salvează…' : '+ Adaugă regulă'}
          </button>
        </div>
      )}
    </div>
  )
}
