/**
 * TypeScript contracts for the DynamicForms schema.
 * These are deliberately aligned with the backend's PHP schema validator —
 * see app/Core/DynamicForms/Validation/FormSchemaValidator.php.
 */

// ─── Field options ─────────────────────────────────────────────────────────────

export interface SelectOption {
  value: string
  label: string
}

// ─── Per-field validation configs ─────────────────────────────────────────────

export interface TextValidation {
  min_length?: number
  max_length?: number
}

export interface NumberValidation {
  min?: number
  max?: number
  integer_only?: boolean
}

export interface DateValidation {
  min_date?: string // YYYY-MM-DD
  max_date?: string // YYYY-MM-DD
}

// ─── Field discriminated union ─────────────────────────────────────────────────

interface BaseField {
  key: string
  label: string
  required: boolean
  order: number
  description?: string
}

export interface TextField extends BaseField {
  type: 'text'
  validation?: TextValidation
}

export interface TextareaField extends BaseField {
  type: 'textarea'
  validation?: TextValidation
}

export interface NumberField extends BaseField {
  type: 'number'
  validation?: NumberValidation
}

export interface EmailField extends BaseField {
  type: 'email'
}

export interface DateField extends BaseField {
  type: 'date'
  validation?: DateValidation
}

export interface SelectField extends BaseField {
  type: 'select'
  options: SelectOption[]
  allow_custom_value?: boolean
}

export interface RadioField extends BaseField {
  type: 'radio'
  options: SelectOption[]
  allow_custom_value?: boolean
}

export interface CheckboxField extends BaseField {
  type: 'checkbox'
}

export interface SectionField extends BaseField {
  type: 'section'
}

/** File is placeholder only in V1 — the renderer skips it silently. */
export interface FileField extends BaseField {
  type: 'file'
}

export type FormField =
  | TextField
  | TextareaField
  | NumberField
  | EmailField
  | DateField
  | SelectField
  | RadioField
  | CheckboxField
  | SectionField
  | FileField

// ─── Schema ────────────────────────────────────────────────────────────────────

export interface FormSettings {
  allow_multiple_submissions?: boolean
}

export interface FormSchema {
  /** Must be 1 for V1 runtime. */
  version: 1
  title: string
  settings?: FormSettings
  fields: FormField[]
}

// ─── API resource shapes (mirrors backend *Resource classes) ──────────────────

export interface FormVersionDetail {
  id: number
  form_id: number
  version_number: number
  schema: FormSchema
  schema_hash: string
  label?: string | null
  published_at: string | null
}

export interface FormDetail {
  id: number
  tenant_id: number
  name: string
  slug?: string | null
  description?: string | null
  status: 'draft' | 'active' | 'archived'
  active_version_id: number | null
  active_version: FormVersionDetail | null
  metadata?: Record<string, unknown> | null
  created_at: string
  updated_at: string
}

export interface FormSubmissionDetail {
  id: number
  form_id: number
  form_version_id: number
  submitted_by: number | null
  payload: Record<string, unknown>
  metadata?: Record<string, unknown> | null
  submitted_at: string
}

// ─── Runtime helpers ───────────────────────────────────────────────────────────

/** Sorted fields for rendering — excludes file fields (V1 deferred). */
export function sortedRenderableFields(schema: FormSchema): FormField[] {
  return [...schema.fields]
    .filter((f) => f.type !== 'file')
    .sort((a, b) => a.order - b.order)
}

/** Returns a sensible default value for each field type. */
export function defaultFieldValue(field: FormField): unknown {
  switch (field.type) {
    case 'checkbox':
      return false
    case 'number':
      return null
    case 'select':
    case 'radio':
      return null
    default:
      return ''
  }
}
