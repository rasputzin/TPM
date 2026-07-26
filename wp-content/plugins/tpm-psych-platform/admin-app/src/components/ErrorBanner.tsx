import { ApiError } from '../api/client'

export function ErrorBanner({ error }: { error: unknown }) {
  if (!error) return null

  const message = error instanceof ApiError ? error.message : error instanceof Error ? error.message : String(error)
  const details = error instanceof ApiError ? error.payload?.errors : undefined

  return (
    <div className="tpm-error-banner">
      <strong>{message}</strong>
      {details && details.length > 0 && (
        <ul>
          {details.map((d, i) => (
            <li key={i}>{d}</li>
          ))}
        </ul>
      )}
    </div>
  )
}
