import { useEffect, useState } from 'react'
import { versionsApi } from '../api/endpoints'
import type { InstrumentVersion } from '../api/types'
import { ErrorBanner } from '../components/ErrorBanner'
import { StatusBadge } from '../components/StatusBadge'
import { MetadataTab } from './version-tabs/MetadataTab'
import { ItemsTab } from './version-tabs/ItemsTab'
import { ScalesTab } from './version-tabs/ScalesTab'
import { NormsTab } from './version-tabs/NormsTab'

type Tab = 'metadata' | 'items' | 'scales' | 'norms'

export function VersionDetailView({ versionId, onBack }: { versionId: number; onBack: () => void }) {
  const [version, setVersion] = useState<InstrumentVersion | null>(null)
  const [error, setError] = useState<unknown>(null)
  const [tab, setTab] = useState<Tab>('items')

  function reload() {
    setError(null)
    versionsApi.get(versionId).then(setVersion).catch(setError)
  }

  useEffect(reload, [versionId])

  const isDraft = version?.status === 'draft'

  return (
    <div>
      <button className="tpm-back-link" onClick={onBack}>
        ← Înapoi la instrument
      </button>

      <ErrorBanner error={error} />

      {version && (
        <div className="tpm-panel">
          <h2>
            Versiunea {version.version_label} <StatusBadge status={version.status} />
          </h2>
          {!isDraft && (
            <p className="tpm-muted">
              Această versiune nu mai e draft — nu poate fi modificată. Clonează o versiune nouă din ecranul
              instrumentului dacă trebuie să faci corecții.
            </p>
          )}
        </div>
      )}

      <div className="tpm-tabs">
        <button className={tab === 'metadata' ? 'active' : ''} onClick={() => setTab('metadata')}>
          Metadate
        </button>
        <button className={tab === 'items' ? 'active' : ''} onClick={() => setTab('items')}>
          Secțiuni și itemi
        </button>
        <button className={tab === 'scales' ? 'active' : ''} onClick={() => setTab('scales')}>
          Scale și cotare
        </button>
        <button className={tab === 'norms' ? 'active' : ''} onClick={() => setTab('norms')}>
          Etaloane
        </button>
      </div>

      {version && (
        <>
          {tab === 'metadata' && <MetadataTab version={version} isDraft={isDraft} onSaved={reload} />}
          {tab === 'items' && <ItemsTab versionId={versionId} isDraft={isDraft} />}
          {tab === 'scales' && <ScalesTab versionId={versionId} isDraft={isDraft} />}
          {tab === 'norms' && <NormsTab versionId={versionId} isDraft={isDraft} />}
        </>
      )}
    </div>
  )
}
