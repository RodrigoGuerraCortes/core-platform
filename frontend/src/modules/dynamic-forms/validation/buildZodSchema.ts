import { z } from 'zod'
import type { FormField, FormSchema } from '../types'

/**
 * Builds a Zod schema from the schema's field definitions.
 *
 * Mirrors the backend FormSubmissionValidator's per-field rules so that
 * users get immediate client-side feedback before a round-trip to the server.
 *
 * Skipped types: section, file (V1 deferred).
 */
export function buildZodSchema(schema: FormSchema): z.ZodObject<z.ZodRawShape> {
  const shape: z.ZodRawShape = {}

  for (const field of schema.fields) {
    const validator = buildFieldValidator(field)
    if (validator !== null) {
      shape[field.key] = validator
    }
  }

  return z.object(shape)
}

function buildFieldValidator(field: FormField): z.ZodTypeAny | null {
  switch (field.type) {
    // ── Text / Textarea / Email ────────────────────────────────────────────
    case 'text':
    case 'textarea': {
      let s = z.string()
      const maxLength = field.validation?.max_length
      const minLength = field.validation?.min_length
      if (maxLength) s = s.max(maxLength, `Maximum ${maxLength} characters`)
      if (minLength) s = s.min(minLength, `Minimum ${minLength} characters`)
      return field.required ? s.min(1, `${field.label} is required`) : s.optional()
    }

    case 'email': {
      const s = z
        .string()
        .email('Invalid email address')
      return field.required ? s.min(1, `${field.label} is required`) : s.optional()
    }

    // ── Number ────────────────────────────────────────────────────────────
    case 'number': {
      // Values come in as number | null from the NumberField component.
      let s = z.number({ invalid_type_error: 'Must be a valid number' })
      const { min, max, integer_only } = field.validation ?? {}
      if (min !== undefined) s = s.min(min, `Minimum value is ${min}`)
      if (max !== undefined) s = s.max(max, `Maximum value is ${max}`)
      if (integer_only) s = s.int('Must be a whole number')
      return field.required ? s : s.nullable().optional()
    }

    // ── Date ──────────────────────────────────────────────────────────────
    case 'date': {
      let s = z.string().regex(/^\d{4}-\d{2}-\d{2}$/, 'Date must be in YYYY-MM-DD format')
      const { min_date, max_date } = field.validation ?? {}
      if (min_date) s = s.refine((v) => v >= min_date, `Date must be on or after ${min_date}`)
      if (max_date) s = s.refine((v) => v <= max_date, `Date must be on or before ${max_date}`)
      return field.required ? s.min(1, `${field.label} is required`) : s.optional()
    }

    // ── Select / Radio ────────────────────────────────────────────────────
    case 'select':
    case 'radio': {
      const optionValues = field.options.map((o) => o.value)

      if (field.allow_custom_value || optionValues.length === 0) {
        const s = z.string()
        return field.required ? s.min(1, `${field.label} is required`) : s.optional()
      }

      // z.enum requires a non-empty literal tuple
      const enumSchema = z.enum(optionValues as [string, ...string[]], {
        errorMap: () => ({ message: 'Please select a valid option' }),
      })
      return field.required ? enumSchema : enumSchema.optional()
    }

    // ── Checkbox ──────────────────────────────────────────────────────────
    case 'checkbox': {
      // Required checkbox must be exactly true (user must check the box).
      return field.required
        ? z.literal(true, { errorMap: () => ({ message: `${field.label} must be checked` }) })
        : z.boolean().optional()
    }

    // ── Section / File — skipped ──────────────────────────────────────────
    case 'section':
    case 'file':
      return null
  }
}

// ─── Error mapping helpers ──────────────────────────────────────────────────────

export type FieldErrors = Record<string, string>

/** Maps a ZodError to a flat { fieldKey → first error message } object. */
export function mapZodErrors(error: z.ZodError): FieldErrors {
  const result: FieldErrors = {}
  for (const issue of error.issues) {
    const key = issue.path[0]
    if (typeof key === 'string' && !(key in result)) {
      result[key] = issue.message
    }
  }
  return result
}
