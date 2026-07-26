import type { ApiErrorPayload } from './types'

declare global {
  interface Window {
    tpmPsychAdmin: {
      restRoot: string
      nonce: string
    }
  }
}

export class ApiError extends Error {
  readonly status: number
  readonly payload: ApiErrorPayload | null

  constructor(message: string, status: number, payload: ApiErrorPayload | null) {
    super(message)
    this.status = status
    this.payload = payload
  }
}

/**
 * Toate cererile trec prin REST API-ul WordPress standard, autentificat prin
 * cookie de sesiune + nonce (X-WP-Nonce) — vezi RestRouteRegistrar.php.
 * Nonce-ul și rădăcina REST sunt injectate în pagină de AdminPage::enqueue()
 * prin wp_localize_script, nu hardcodate aici.
 */
async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const { restRoot, nonce } = window.tpmPsychAdmin

  const response = await fetch(`${restRoot}${path}`, {
    ...options,
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': nonce,
      ...options.headers,
    },
  })

  const text = await response.text()
  const data = text ? JSON.parse(text) : null

  if (!response.ok) {
    const payload = data as ApiErrorPayload | null
    throw new ApiError(payload?.message ?? `Eroare HTTP ${response.status}`, response.status, payload)
  }

  return data as T
}

export const api = {
  get: <T>(path: string) => request<T>(path, { method: 'GET' }),
  post: <T>(path: string, body?: unknown) =>
    request<T>(path, { method: 'POST', body: body !== undefined ? JSON.stringify(body) : undefined }),
  put: <T>(path: string, body?: unknown) =>
    request<T>(path, { method: 'PUT', body: body !== undefined ? JSON.stringify(body) : undefined }),
  del: <T>(path: string) => request<T>(path, { method: 'DELETE' }),
}
