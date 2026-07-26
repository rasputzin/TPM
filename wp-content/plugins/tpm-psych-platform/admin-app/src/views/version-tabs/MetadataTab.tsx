import { useState } from 'react'
import { versionsApi } from '../../api/endpoints'
import type { InstrumentVersion } from '../../api/types'
import { ErrorBanner } from '../../components/ErrorBanner'
import { JsonField } from '../../components/JsonField'

export function MetadataTab({
  version,
  isDraft,
  onSaved,
}: {
  version: InstrumentVersion
  isDraft: boolean
  onSaved: () => void
}) {
  const [language, setLanguage] = useState(version.language)
  const [duration, setDuration] = useState(version.estimated_duration_minutes ?? '')
  const [instructions, setInstructions] = useState(version.instructions_text ?? '')
  const [missingPolicy, setMissingPolicy] = useState<Record<string, unknown>>(version.missing_response_policy ?? {})
  const [error, setError] = useState<unknown>(null)
  const [saving, setSaving] = useState(false)

  async function save() {
    setSaving(true)
    setError(null)
    try {
      await versionsApi.update(version.id, {
        language,
        estimated_duration_minutes: duration === '' ? null : Number(duration),
        instructions_text: instructions || null,
        missing_response_policy: Object.keys(missingPolicy).length > 0 ? missingPolicy : null,
      })
      onSaved()
    } catch (err) {
      setError(err)
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="tpm-panel">
      <ErrorBanner error={error} />
      <label className="tpm-field">
        <span>Limbă</span>
        <input value={language} onChange={(e) => setLanguage(e.target.value)} disabled={!isDraft} />
      </label>
      <label className="tpm-field">
        <span>Durată estimată (minute)</span>
        <input
          type="number"
          value={duration}
          onChange={(e) => setDuration(e.target.value === '' ? '' : Number(e.target.value))}
          disabled={!isDraft}
        />
      </label>
      <label className="tpm-field">
        <span>Instrucțiuni afișate respondentului</span>
        <textarea rows={5} value={instructions} onChange={(e) => setInstructions(e.target.value)} disabled={!isDraft} />
      </label>
      <JsonField label="Politica pentru răspunsuri lipsă (JSON)" value={missingPolicy} onChange={setMissingPolicy} />
      <p className="tpm-hint">
        Reguli exacte pentru răspunsuri lipsă (câte invalidează o scală, cum se tratează scoruri parțiale) — conform
        manualului instrumentului, nu inventate aici.
      </p>
      {isDraft && (
        <button className="primary" onClick={save} disabled={saving}>
          {saving ? 'Se salvează…' : 'Salvează metadatele'}
        </button>
      )}
    </div>
  )
}
