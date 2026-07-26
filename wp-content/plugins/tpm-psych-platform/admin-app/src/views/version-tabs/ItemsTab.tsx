import { Fragment, useEffect, useState } from 'react'
import { structureApi } from '../../api/endpoints'
import { ITEM_TYPES, type Item, type ItemType, type ResponseOption, type Section } from '../../api/types'
import { ErrorBanner } from '../../components/ErrorBanner'

const CHOICE_TYPES: ItemType[] = ['likert', 'single_choice', 'multiple_choice', 'matrix']

export function ItemsTab({ versionId, isDraft }: { versionId: number; isDraft: boolean }) {
  const [sections, setSections] = useState<Section[]>([])
  const [items, setItems] = useState<Item[]>([])
  const [error, setError] = useState<unknown>(null)
  const [expandedItemId, setExpandedItemId] = useState<number | null>(null)

  function reload() {
    setError(null)
    structureApi.listSections(versionId).then(setSections).catch(setError)
    structureApi.listItems(versionId).then(setItems).catch(setError)
  }

  useEffect(reload, [versionId])

  async function deleteItem(id: number) {
    if (!confirm('Ștergi acest item? Se șterg și opțiunile lui de răspuns.')) return
    try {
      await structureApi.deleteItem(id)
      reload()
    } catch (err) {
      setError(err)
    }
  }

  return (
    <div>
      <ErrorBanner error={error} />

      <SectionsPanel versionId={versionId} sections={sections} isDraft={isDraft} onChanged={reload} onError={setError} />

      <h3>Itemi</h3>
      {isDraft && (
        <AddItemForm
          versionId={versionId}
          sections={sections}
          nextOrderIndex={items.length}
          onCreated={reload}
          onError={setError}
        />
      )}

      {items.length === 0 ? (
        <p className="tpm-muted">Niciun item definit încă.</p>
      ) : (
        <table>
          <thead>
            <tr>
              <th>Cod</th>
              <th>Text</th>
              <th>Tip</th>
              <th>Secțiune</th>
              <th>Inversat</th>
              <th>Obligatoriu</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {items.map((item) => (
              <Fragment key={item.id}>
                <tr>
                  <td>{item.code}</td>
                  <td>{item.prompt_text}</td>
                  <td>{item.item_type}</td>
                  <td>{sections.find((s) => s.id === item.section_id)?.title ?? '—'}</td>
                  <td>{item.is_reverse_scored ? 'da' : 'nu'}</td>
                  <td>{item.is_required ? 'da' : 'nu'}</td>
                  <td>
                    {CHOICE_TYPES.includes(item.item_type) && (
                      <button onClick={() => setExpandedItemId(expandedItemId === item.id ? null : item.id)}>
                        {expandedItemId === item.id ? 'Ascunde opțiuni' : 'Opțiuni de răspuns'}
                      </button>
                    )}{' '}
                    {isDraft && (
                      <button className="danger" onClick={() => deleteItem(item.id)}>
                        Șterge
                      </button>
                    )}
                  </td>
                </tr>
                {expandedItemId === item.id && (
                  <tr>
                    <td colSpan={7}>
                      <ResponseOptionsPanel itemId={item.id} isDraft={isDraft} onError={setError} />
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

function SectionsPanel({
  versionId,
  sections,
  isDraft,
  onChanged,
  onError,
}: {
  versionId: number
  sections: Section[]
  isDraft: boolean
  onChanged: () => void
  onError: (e: unknown) => void
}) {
  const [title, setTitle] = useState('')
  const [saving, setSaving] = useState(false)

  async function addSection() {
    if (!title.trim()) return
    setSaving(true)
    try {
      await structureApi.createSection(versionId, { title, order_index: sections.length })
      setTitle('')
      onChanged()
    } catch (err) {
      onError(err)
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="tpm-panel">
      <h3 style={{ marginTop: 0 }}>Secțiuni</h3>
      {sections.length === 0 ? (
        <p className="tpm-muted">Nicio secțiune — itemii pot rămâne și fără secțiune.</p>
      ) : (
        <ul>
          {sections.map((s) => (
            <li key={s.id}>{s.title}</li>
          ))}
        </ul>
      )}
      {isDraft && (
        <div className="tpm-field tpm-field-inline">
          <input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="Titlu secțiune nouă" />
          <button onClick={addSection} disabled={saving}>
            + Adaugă secțiune
          </button>
        </div>
      )}
    </div>
  )
}

function AddItemForm({
  versionId,
  sections,
  nextOrderIndex,
  onCreated,
  onError,
}: {
  versionId: number
  sections: Section[]
  nextOrderIndex: number
  onCreated: () => void
  onError: (e: unknown) => void
}) {
  const [code, setCode] = useState('')
  const [promptText, setPromptText] = useState('')
  const [itemType, setItemType] = useState<ItemType>('likert')
  const [sectionId, setSectionId] = useState<number | ''>('')
  const [isReverseScored, setIsReverseScored] = useState(false)
  const [isRequired, setIsRequired] = useState(true)
  const [saving, setSaving] = useState(false)

  async function submit(e: React.FormEvent) {
    e.preventDefault()
    setSaving(true)
    try {
      await structureApi.createItem(versionId, {
        section_id: sectionId === '' ? null : sectionId,
        code,
        prompt_text: promptText,
        item_type: itemType,
        is_reverse_scored: isReverseScored,
        is_required: isRequired,
        order_index: nextOrderIndex,
      })
      setCode('')
      setPromptText('')
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
        <span>Cod item (unic în versiune) *</span>
        <input value={code} onChange={(e) => setCode(e.target.value)} placeholder="ex. Q1" required />
      </label>
      <label className="tpm-field">
        <span>Text item *</span>
        <textarea value={promptText} onChange={(e) => setPromptText(e.target.value)} required rows={2} />
      </label>
      <label className="tpm-field">
        <span>Tip item</span>
        <select value={itemType} onChange={(e) => setItemType(e.target.value as ItemType)}>
          {ITEM_TYPES.map((t) => (
            <option key={t} value={t}>
              {t}
            </option>
          ))}
        </select>
      </label>
      <label className="tpm-field">
        <span>Secțiune</span>
        <select value={sectionId} onChange={(e) => setSectionId(e.target.value === '' ? '' : Number(e.target.value))}>
          <option value="">(fără secțiune)</option>
          {sections.map((s) => (
            <option key={s.id} value={s.id}>
              {s.title}
            </option>
          ))}
        </select>
      </label>
      <label className="tpm-field tpm-field-inline">
        <input type="checkbox" checked={isReverseScored} onChange={(e) => setIsReverseScored(e.target.checked)} />
        <span>Item cu cotare inversă (informativ — introdu valorile deja corecte în opțiunile de răspuns)</span>
      </label>
      <label className="tpm-field tpm-field-inline">
        <input type="checkbox" checked={isRequired} onChange={(e) => setIsRequired(e.target.checked)} />
        <span>Obligatoriu</span>
      </label>
      <button className="primary" type="submit" disabled={saving}>
        {saving ? 'Se salvează…' : '+ Adaugă item'}
      </button>
    </form>
  )
}

function ResponseOptionsPanel({
  itemId,
  isDraft,
  onError,
}: {
  itemId: number
  isDraft: boolean
  onError: (e: unknown) => void
}) {
  const [options, setOptions] = useState<ResponseOption[] | null>(null)
  const [label, setLabel] = useState('')
  const [rawValue, setRawValue] = useState(0)

  function reload() {
    structureApi.listResponseOptions(itemId).then(setOptions).catch(onError)
  }

  useEffect(reload, [itemId])

  async function add() {
    if (!label.trim()) return
    try {
      await structureApi.createResponseOption(itemId, {
        label_text: label,
        raw_value: rawValue,
        order_index: options?.length ?? 0,
      })
      setLabel('')
      setRawValue(0)
      reload()
    } catch (err) {
      onError(err)
    }
  }

  return (
    <div className="tpm-panel">
      {options === null ? (
        <p>Se încarcă…</p>
      ) : options.length === 0 ? (
        <p className="tpm-muted">Nicio opțiune de răspuns încă.</p>
      ) : (
        <table>
          <thead>
            <tr>
              <th>Etichetă</th>
              <th>Valoare de cotare</th>
            </tr>
          </thead>
          <tbody>
            {options.map((o) => (
              <tr key={o.id}>
                <td>{o.label_text}</td>
                <td>{o.raw_value}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
      {isDraft && (
        <div className="tpm-field tpm-field-inline">
          <input value={label} onChange={(e) => setLabel(e.target.value)} placeholder="Etichetă (ex. Des)" />
          <input
            type="number"
            value={rawValue}
            onChange={(e) => setRawValue(Number(e.target.value))}
            style={{ width: 80 }}
          />
          <button onClick={add}>+ Adaugă opțiune</button>
        </div>
      )}
    </div>
  )
}
