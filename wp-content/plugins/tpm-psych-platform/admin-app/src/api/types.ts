// Oglindește DTO-urile PHP (TpmPsych\Domain\...::toArray()) — vezi
// wp-content/plugins/tpm-psych-platform/src/Domain/**/*.php pentru sursa de adevăr.

export type InstrumentStatus = 'draft' | 'active' | 'archived'
export type VersionStatus = 'draft' | 'active' | 'deprecated'
export type RuleStatus = 'draft' | 'active' | 'deprecated'

export interface Instrument {
  id: number
  organization_id: number | null
  code: string
  name: string
  rights_holder: string
  license_reference: string | null
  target_population: string | null
  status: InstrumentStatus
}

export interface InstrumentVersion {
  id: number
  instrument_id: number
  version_label: string
  language: string
  estimated_duration_minutes: number | null
  instructions_text: string | null
  missing_response_policy: Record<string, unknown> | null
  status: VersionStatus
}

export interface Section {
  id: number
  instrument_version_id: number
  parent_section_id: number | null
  title: string | null
  order_index: number
  branching_rule: Record<string, unknown> | null
}

export const ITEM_TYPES = [
  'likert',
  'single_choice',
  'multiple_choice',
  'boolean',
  'numeric',
  'free_text',
  'matrix',
] as const
export type ItemType = (typeof ITEM_TYPES)[number]

export interface Item {
  id: number
  instrument_version_id: number
  section_id: number | null
  code: string
  prompt_text: string
  item_type: ItemType
  is_reverse_scored: boolean
  is_required: boolean
  conditional_rule: Record<string, unknown> | null
  order_index: number
}

export interface ResponseOption {
  id: number
  item_id: number
  label_text: string
  raw_value: number
  order_index: number
}

export interface Scale {
  id: number
  instrument_version_id: number
  parent_scale_id: number | null
  code: string
  name: string
}

export const RULE_TYPES = ['sum', 'weighted_sum', 'formula', 'conditional', 'lookup'] as const
export type RuleType = (typeof RULE_TYPES)[number]

export interface ScoringRule {
  id: number
  scale_id: number
  rule_type: RuleType
  formula_definition: Record<string, unknown>
  engine_version: string
  status: RuleStatus
}

export const STANDARD_SCORE_TYPES = ['percentile', 't_score', 'z_score', 'stanine', 'custom'] as const
export type StandardScoreType = (typeof STANDARD_SCORE_TYPES)[number]

export interface NormSet {
  id: number
  instrument_version_id: number
  label: string
  applicable_criteria: Record<string, unknown> | null
  version_label: string
  status: 'draft' | 'active' | 'deprecated'
}

export interface NormEntry {
  id: number
  norm_set_id: number
  scale_id: number
  raw_score_min: number
  raw_score_max: number
  standard_score_type: StandardScoreType
  standard_score_value: number
  interpretation_range_code: string | null
}

export interface ReportTemplate {
  id: number
  organization_id: number
  instrument_id: number | null
  version_label: string
  layout_definition: {
    interpretation_texts?: Array<{ interpretation_range_code: string; title?: string; body: string }>
    [key: string]: unknown
  }
  status: 'draft' | 'active' | 'deprecated'
}

export interface ApiErrorPayload {
  code: string
  message: string
  errors?: string[]
}
