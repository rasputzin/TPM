import { useEffect, useState } from 'react'
import { instrumentsApi } from '../api/endpoints'
import type { Instrument } from '../api/types'
import { ErrorBanner } from '../components/ErrorBanner'
import { StatusBadge } from '../components/StatusBadge'

export function InstrumentsListView({ onOpenInstrument }: { onOpenInstrument: (id: number) => void }) {
  const [instruments, setInstruments] = useState<Instrument[] | null>(null)
  const [error, setError] = useState<unknown>(null)
  const [showForm, setShowForm] = useState(false)

  function reload() {
    setError(null)
    instrumentsApi.list().then(setInstruments).catch(setError)
  }

  useEffect(reload, [])

  return (
    <div>
      <div className="tpm-toolbar">
        <button className="primary" onClick={() => setShowForm((v) => !v)}>
          {showForm ? 'Anulează' : '+ Instrument nou'}
        </button>
      </div>

      <ErrorBanner error={error} />

      {showForm && (
        <CreateInstrumentForm
          onCreated={() => {
            setShowForm(false)
            reload()
          }}
          onError={setError}
        />
      )}

      {instruments === null ? (
        <p>Se încarcă…</p>
      ) : instruments.length === 0 ? (
        <p className="tpm-muted">Niciun instrument definit încă.</p>
      ) : (
        <table>
          <thead>
            <tr>
              <th>Cod</th>
              <th>Denumire</th>
              <th>Titular drepturi</th>
              <th>Populație țintă</th>
              <th>Stare</th>
            </tr>
          </thead>
          <tbody>
            {instruments.map((instrument) => (
              <tr key={instrument.id} className="clickable" onClick={() => onOpenInstrument(instrument.id)}>
                <td>{instrument.code}</td>
                <td>{instrument.name}</td>
                <td>{instrument.rights_holder}</td>
                <td>{instrument.target_population ?? '—'}</td>
                <td>
                  <StatusBadge status={instrument.status} />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}

function CreateInstrumentForm({ onCreated, onError }: { onCreated: () => void; onError: (e: unknown) => void }) {
  const [code, setCode] = useState('')
  const [name, setName] = useState('')
  const [rightsHolder, setRightsHolder] = useState('')
  const [licenseReference, setLicenseReference] = useState('')
  const [targetPopulation, setTargetPopulation] = useState('')
  const [saving, setSaving] = useState(false)

  async function submit(e: React.FormEvent) {
    e.preventDefault()
    setSaving(true)
    try {
      await instrumentsApi.create({
        code,
        name,
        rights_holder: rightsHolder,
        license_reference: licenseReference || null,
        target_population: targetPopulation || null,
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
        <span>Cod (acronim, unic) *</span>
        <input value={code} onChange={(e) => setCode(e.target.value)} placeholder="ex. BDEFS" required />
      </label>
      <label className="tpm-field">
        <span>Denumire *</span>
        <input value={name} onChange={(e) => setName(e.target.value)} required />
      </label>
      <label className="tpm-field">
        <span>Titular drepturi *</span>
        <input value={rightsHolder} onChange={(e) => setRightsHolder(e.target.value)} required />
      </label>
      <label className="tpm-field">
        <span>Referință licență (intern)</span>
        <input value={licenseReference} onChange={(e) => setLicenseReference(e.target.value)} />
      </label>
      <label className="tpm-field">
        <span>Populație țintă</span>
        <input value={targetPopulation} onChange={(e) => setTargetPopulation(e.target.value)} />
      </label>
      <button className="primary" type="submit" disabled={saving}>
        {saving ? 'Se salvează…' : 'Creează instrument'}
      </button>
    </form>
  )
}
