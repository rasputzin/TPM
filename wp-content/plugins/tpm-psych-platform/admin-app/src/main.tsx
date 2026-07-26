import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import { App } from './App'

const container = document.getElementById('tpm-admin-root')

if (!container) {
  throw new Error('tpm-admin-root nu a fost găsit în pagină — AdminPage::render() trebuie să-l emită.')
}

createRoot(container).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
