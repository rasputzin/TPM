import { api } from './client'
import type {
  Instrument,
  InstrumentVersion,
  Item,
  NormEntry,
  NormSet,
  ReportTemplate,
  ResponseOption,
  Scale,
  ScoringRule,
  Section,
} from './types'

const BASE = '/tpm/v1/admin'

export const instrumentsApi = {
  list: (status?: string) => api.get<Instrument[]>(`${BASE}/instruments${status ? `?status=${status}` : ''}`),
  get: (id: number) => api.get<Instrument>(`${BASE}/instruments/${id}`),
  create: (data: {
    code: string
    name: string
    rights_holder: string
    license_reference?: string | null
    target_population?: string | null
  }) => api.post<Instrument>(`${BASE}/instruments`, data),
  update: (
    id: number,
    data: { name: string; rights_holder: string; license_reference?: string | null; target_population?: string | null },
  ) => api.put<Instrument>(`${BASE}/instruments/${id}`, data),
  archive: (id: number) => api.post<{ status: string }>(`${BASE}/instruments/${id}/archive`),
}

export const versionsApi = {
  list: (instrumentId: number) => api.get<InstrumentVersion[]>(`${BASE}/instruments/${instrumentId}/versions`),
  get: (id: number) => api.get<InstrumentVersion>(`${BASE}/instrument-versions/${id}`),
  create: (instrumentId: number, data: { version_label: string; language: string }) =>
    api.post<InstrumentVersion>(`${BASE}/instruments/${instrumentId}/versions`, data),
  update: (
    id: number,
    data: {
      language: string
      estimated_duration_minutes?: number | null
      instructions_text?: string | null
      missing_response_policy?: Record<string, unknown> | null
    },
  ) => api.put<InstrumentVersion>(`${BASE}/instrument-versions/${id}`, data),
  clone: (id: number, newVersionLabel: string) =>
    api.post<InstrumentVersion>(`${BASE}/instrument-versions/${id}/clone`, { new_version_label: newVersionLabel }),
  publish: (id: number) => api.post<InstrumentVersion>(`${BASE}/instrument-versions/${id}/publish`),
}

export const structureApi = {
  listSections: (versionId: number) => api.get<Section[]>(`${BASE}/instrument-versions/${versionId}/sections`),
  createSection: (versionId: number, data: { title?: string | null; order_index: number; parent_section_id?: number | null }) =>
    api.post<Section>(`${BASE}/instrument-versions/${versionId}/sections`, data),

  listItems: (versionId: number) => api.get<Item[]>(`${BASE}/instrument-versions/${versionId}/items`),
  createItem: (
    versionId: number,
    data: {
      section_id?: number | null
      code: string
      prompt_text: string
      item_type: string
      is_reverse_scored: boolean
      is_required: boolean
      order_index: number
    },
  ) => api.post<Item>(`${BASE}/instrument-versions/${versionId}/items`, data),
  updateItem: (
    id: number,
    data: { prompt_text: string; is_reverse_scored: boolean; is_required: boolean; order_index: number },
  ) => api.put<Item>(`${BASE}/items/${id}`, data),
  deleteItem: (id: number) => api.del<{ status: string }>(`${BASE}/items/${id}`),

  listResponseOptions: (itemId: number) => api.get<ResponseOption[]>(`${BASE}/items/${itemId}/response-options`),
  createResponseOption: (itemId: number, data: { label_text: string; raw_value: number; order_index: number }) =>
    api.post<ResponseOption>(`${BASE}/items/${itemId}/response-options`, data),
}

export const scalesApi = {
  list: (versionId: number) => api.get<Scale[]>(`${BASE}/instrument-versions/${versionId}/scales`),
  create: (versionId: number, data: { code: string; name: string; parent_scale_id?: number | null }) =>
    api.post<Scale>(`${BASE}/instrument-versions/${versionId}/scales`, data),
  update: (id: number, data: { name: string }) => api.put<Scale>(`${BASE}/scales/${id}`, data),
}

export const scoringRulesApi = {
  list: (scaleId: number) => api.get<ScoringRule[]>(`${BASE}/scales/${scaleId}/scoring-rules`),
  create: (scaleId: number, data: { rule_type: string; formula_definition: Record<string, unknown> }) =>
    api.post<ScoringRule>(`${BASE}/scales/${scaleId}/scoring-rules`, data),
}

export const normsApi = {
  listSets: (versionId: number) => api.get<NormSet[]>(`${BASE}/instrument-versions/${versionId}/norm-sets`),
  createSet: (
    versionId: number,
    data: { label: string; version_label: string; applicable_criteria?: Record<string, unknown> | null },
  ) => api.post<NormSet>(`${BASE}/instrument-versions/${versionId}/norm-sets`, data),
  publishSet: (id: number) => api.post<NormSet>(`${BASE}/norm-sets/${id}/publish`),

  listEntries: (normSetId: number, scaleId?: number) =>
    api.get<NormEntry[]>(`${BASE}/norm-sets/${normSetId}/entries${scaleId ? `?scale_id=${scaleId}` : ''}`),
  createEntry: (
    normSetId: number,
    data: {
      scale_id: number
      raw_score_min: number
      raw_score_max: number
      standard_score_type: string
      standard_score_value: number
      interpretation_range_code?: string | null
    },
  ) => api.post<NormEntry>(`${BASE}/norm-sets/${normSetId}/entries`, data),
}

export const reportTemplatesApi = {
  list: (organizationId: number) => api.get<ReportTemplate[]>(`${BASE}/organizations/${organizationId}/report-templates`),
  create: (
    organizationId: number,
    data: {
      instrument_id?: number | null
      version_label: string
      layout_definition: Record<string, unknown>
      reference_instrument_version_id?: number | null
    },
  ) => api.post<ReportTemplate>(`${BASE}/organizations/${organizationId}/report-templates`, data),
  publish: (id: number) => api.post<ReportTemplate>(`${BASE}/report-templates/${id}/publish`),
}
