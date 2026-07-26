import { useState } from 'react'
import { InstrumentsListView } from './views/InstrumentsListView'
import { InstrumentDetailView } from './views/InstrumentDetailView'
import { VersionDetailView } from './views/VersionDetailView'
import { ReportTemplatesView } from './views/ReportTemplatesView'

export type Route =
  | { name: 'instruments' }
  | { name: 'instrument'; instrumentId: number }
  | { name: 'version'; versionId: number; instrumentId: number }
  | { name: 'templates' }

export function App() {
  const [route, setRoute] = useState<Route>({ name: 'instruments' })

  return (
    <div className="tpm-app">
      <header className="tpm-header">
        <h1>TPM Psych Platform — Administrare instrumente</h1>
        <nav>
          <button onClick={() => setRoute({ name: 'instruments' })}>Instrumente</button>
          <button onClick={() => setRoute({ name: 'templates' })}>Șabloane de raport</button>
        </nav>
      </header>

      <main className="tpm-main">
        {route.name === 'instruments' && (
          <InstrumentsListView onOpenInstrument={(id) => setRoute({ name: 'instrument', instrumentId: id })} />
        )}
        {route.name === 'instrument' && (
          <InstrumentDetailView
            instrumentId={route.instrumentId}
            onBack={() => setRoute({ name: 'instruments' })}
            onOpenVersion={(versionId) => setRoute({ name: 'version', versionId, instrumentId: route.instrumentId })}
          />
        )}
        {route.name === 'version' && (
          <VersionDetailView
            versionId={route.versionId}
            onBack={() => setRoute({ name: 'instrument', instrumentId: route.instrumentId })}
          />
        )}
        {route.name === 'templates' && <ReportTemplatesView />}
      </main>
    </div>
  )
}
