import { useEffect, useState } from 'react'
import { instrumentsApi, versionsApi } from '../api/endpoints'
import type { Instrument, InstrumentVersion } from '../api/types'
import { ErrorBanner } from '../components/ErrorBanner'
import { StatusBadge } from '../components/StatusBadge'

export function InstrumentDetailView({
  instrumentId,
  onBack,
  onOpenVersion,
}: {
  instrumentId: number
  onBack: () => void
  onOpenVersion: (versionId: number) => void
}) {
  const [instrument, setInstrument] = useState<Instrument | null>(null)
  const [versions, setVersions] = useState<InstrumentVersion[] | null>(null)
  const [error, setError] = useState<unknown>(null)
  const [showNewVersionForm, setShowNewVersionForm] = useState(false)

  function reload() {
    setError(null)
    instrumentsApi.get(instrumentId).then(setInstrument).catch(setError)
    versionsApi.list(instrumentId).then(setVersions).catch(setError)
  }

  useEffect(reload, [instrumentId])

  async function archive() {
    if (!confirm('Arhivezi acest instrument? Versiunile rămân neschimbate.')) return
    try {
      await instrumentsApi.archive(instrumentId)
      reload()
    } catch (err) {
      setError(err)
    }
  }

  async function publish(versionId: number) {
    if (!confirm('Publici această versiune? Devine imutabilă și versiunea activă anterioară va fi depreciată.')) return
    try {
      await versionsApi.publish(versionId)
      reload()
    } catch (err) {
      setError(err)
    }
  }

  async function cloneVersion(sourceId: number) {
    const label = prompt('Eticheta noii versiuni (ex. 1.1.0):')
    if (!label) return
    try {
      await versionsApi.clone(sourceId, label)
      reload()
    } catch (err) {
      setError(err)
    }
  }

  return (
    <div>
      <button className="tpm-back-link" onClick={onBack}>
        ← Înapoi la instrumente
      </button>

      <ErrorBanner error={error} />

      {instrument && (
        <div className="tpm-panel">
          <h2>
            {instrument.name} <StatusBadge status={instrument.status} />
          </h2>
          <p className="tpm-muted">
            Cod: {instrument.code} · Titular drepturi: {instrument.rights_holder}
            {instrument.target_population ? ` · Populație țintă: ${instrument.target_population}` : ''}
          </p>
          {instrument.status !== 'archived' && (
            <button className="danger" onClick={archive}>
              Arhivează instrumentul
            </button>
          )}
        </div>
      )}

      <div className="tpm-toolbar">
        <h3 style={{ margin: 0 }}>Versiuni</h3>
        <button className="primary" onClick={() => setShowNewVersionForm((v) => !v)}>
          {showNewVersionForm ? 'Anulează' : '+ Versiune nouă (draft)'}
        </button>
      </div>

      {showNewVersionForm && (
        <NewVersionForm
          instrumentId={instrumentId}
          onCreated={() => {
            setShowNewVersionForm(false)
            reload()
          }}
          onError={setError}
        />
      )}

      {versions === null ? (
        <p>Se încarcă…</p>
      ) : versions.length === 0 ? (
        <p className="tpm-muted">Nicio versiune definită încă.</p>
      ) : (
        <table>
          <thead>
            <tr>
              <th>Etichetă</th>
              <th>Limbă</th>
              <th>Stare</th>
              <th>Acțiuni</th>
            </tr>
          </thead>
          <tbody>
            {versions.map((v) => (
              <tr key={v.id}>
                <td className="clickable" onClick={() => onOpenVersion(v.id)}>
                  {v.version_label}
                </td>
                <td>{v.language}</td>
                <td>
                  <StatusBadge status={v.status} />
                </td>
                <td>
                  <button onClick={() => onOpenVersion(v.id)}>Editează structura</button>{' '}
                  {v.status === 'draft' && <button onClick={() => publish(v.id)}>Publică</button>}{' '}
                  <button onClick={() => cloneVersion(v.id)}>Clonează ca draft nou</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}

function NewVersionForm({
  instrumentId,
  onCreated,
  onError,
}: {
  instrumentId: number
  onCreated: () => void
  onError: (e: unknown) => void
}) {
  const [versionLabel, setVersionLabel] = useState('1.0.0')
  const [language, setLanguage] = useState('ro')
  const [saving, setSaving] = useState(false)

  async function submit(e: React.FormEvent) {
    e.preventDefault()
    setSaving(true)
    try {
      await versionsApi.create(instrumentId, { version_label: versionLabel, language })
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
        <span>Etichetă versiune *</span>
        <input value={versionLabel} onChange={(e) => setVersionLabel(e.target.value)} required />
      </label>
      <label className="tpm-field">
        <span>Limbă *</span>
        <input value={language} onChange={(e) => setLanguage(e.target.value)} required />
      </label>
      <button className="primary" type="submit" disabled={saving}>
        {saving ? 'Se salvează…' : 'Creează versiunea draft'}
      </button>
    </form>
  )
}
