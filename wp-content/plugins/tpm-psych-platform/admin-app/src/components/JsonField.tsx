import { useState } from 'react'

/**
 * Câmp text pentru structuri JSON auxiliare (formula_definition,
 * applicable_criteria, layout_definition etc.) — validează sintaxa JSON
 * local, înainte de trimitere, dar validarea de conținut (coduri de item
 * cunoscute, tipuri corecte) rămâne responsabilitatea serverului.
 */
export function JsonField({
  label,
  value,
  onChange,
  placeholder,
  rows = 6,
}: {
  label: string
  value: Record<string, unknown>
  onChange: (value: Record<string, unknown>) => void
  placeholder?: string
  rows?: number
}) {
  const [text, setText] = useState(() => JSON.stringify(value, null, 2))
  const [parseError, setParseError] = useState<string | null>(null)

  function handleChange(next: string) {
    setText(next)
    try {
      const parsed = next.trim() === '' ? {} : JSON.parse(next)
      setParseError(null)
      onChange(parsed)
    } catch {
      setParseError('JSON invalid — corectează sintaxa înainte de a salva.')
    }
  }

  return (
    <label className="tpm-field">
      <span>{label}</span>
      <textarea
        value={text}
        onChange={(e) => handleChange(e.target.value)}
        placeholder={placeholder}
        rows={rows}
        spellCheck={false}
        className="tpm-json-field"
      />
      {parseError && <span className="tpm-field-error">{parseError}</span>}
    </label>
  )
}
