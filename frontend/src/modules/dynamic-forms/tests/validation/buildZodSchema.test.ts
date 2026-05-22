import { describe, it, expect } from 'vitest'
import { buildZodSchema, mapZodErrors } from '../../validation/buildZodSchema'
import type { FormSchema } from '../../types'

// ─── Helpers ───────────────────────────────────────────────────────────────────

function makeSchema(fields: FormSchema['fields']): FormSchema {
  return { version: 1, title: 'Test', fields }
}

// ─── Required field validation ─────────────────────────────────────────────────

describe('buildZodSchema — required text field', () => {
  it('rejects an empty string', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'name', type: 'text', label: 'Name', required: true, order: 1 },
    ]))
    const result = schema.safeParse({ name: '' })
    expect(result.success).toBe(false)
  })

  it('accepts a non-empty string', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'name', type: 'text', label: 'Name', required: true, order: 1 },
    ]))
    expect(schema.safeParse({ name: 'Alice' }).success).toBe(true)
  })

  it('optional text field passes when absent', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'bio', type: 'text', label: 'Bio', required: false, order: 1 },
    ]))
    expect(schema.safeParse({}).success).toBe(true)
  })
})

// ─── max_length / min_length ───────────────────────────────────────────────────

describe('buildZodSchema — text max_length', () => {
  it('rejects a string exceeding max_length', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'bio', type: 'text', label: 'Bio', required: false, order: 1, validation: { max_length: 5 } },
    ]))
    const result = schema.safeParse({ bio: 'Too long value' })
    expect(result.success).toBe(false)
  })

  it('accepts a string within max_length', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'bio', type: 'text', label: 'Bio', required: false, order: 1, validation: { max_length: 100 } },
    ]))
    expect(schema.safeParse({ bio: 'Short' }).success).toBe(true)
  })
})

// ─── Email ─────────────────────────────────────────────────────────────────────

describe('buildZodSchema — email field', () => {
  it('rejects an invalid email', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'email', type: 'email', label: 'Email', required: true, order: 1 },
    ]))
    const result = schema.safeParse({ email: 'not-an-email' })
    expect(result.success).toBe(false)
  })

  it('accepts a valid email', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'email', type: 'email', label: 'Email', required: true, order: 1 },
    ]))
    expect(schema.safeParse({ email: 'user@example.com' }).success).toBe(true)
  })
})

// ─── Number ────────────────────────────────────────────────────────────────────

describe('buildZodSchema — number field', () => {
  it('rejects a string for a required number', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'age', type: 'number', label: 'Age', required: true, order: 1 },
    ]))
    const result = schema.safeParse({ age: 'twenty' })
    expect(result.success).toBe(false)
  })

  it('accepts zero as a valid number', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'score', type: 'number', label: 'Score', required: true, order: 1 },
    ]))
    expect(schema.safeParse({ score: 0 }).success).toBe(true)
  })

  it('rejects a value below min', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'age', type: 'number', label: 'Age', required: true, order: 1, validation: { min: 18 } },
    ]))
    expect(schema.safeParse({ age: 10 }).success).toBe(false)
  })

  it('rejects a value above max', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'score', type: 'number', label: 'Score', required: true, order: 1, validation: { max: 100 } },
    ]))
    expect(schema.safeParse({ score: 150 }).success).toBe(false)
  })

  it('rejects a decimal when integer_only is true', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'count', type: 'number', label: 'Count', required: true, order: 1, validation: { integer_only: true } },
    ]))
    expect(schema.safeParse({ count: 2.5 }).success).toBe(false)
  })

  it('accepts an integer when integer_only is true', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'count', type: 'number', label: 'Count', required: true, order: 1, validation: { integer_only: true } },
    ]))
    expect(schema.safeParse({ count: 3 }).success).toBe(true)
  })

  it('optional number accepts null', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'age', type: 'number', label: 'Age', required: false, order: 1 },
    ]))
    expect(schema.safeParse({ age: null }).success).toBe(true)
  })
})

// ─── Date ──────────────────────────────────────────────────────────────────────

describe('buildZodSchema — date field', () => {
  it('rejects an invalid date format', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'dob', type: 'date', label: 'DOB', required: true, order: 1 },
    ]))
    expect(schema.safeParse({ dob: '22/05/2026' }).success).toBe(false)
  })

  it('accepts a YYYY-MM-DD formatted date', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'dob', type: 'date', label: 'DOB', required: true, order: 1 },
    ]))
    expect(schema.safeParse({ dob: '2026-05-22' }).success).toBe(true)
  })
})

// ─── Select / Radio ────────────────────────────────────────────────────────────

describe('buildZodSchema — select field', () => {
  const selectSchema = buildZodSchema(makeSchema([
    {
      key: 'country', type: 'select', label: 'Country', required: true, order: 1,
      options: [{ value: 'us', label: 'US' }, { value: 'ca', label: 'Canada' }],
    },
  ]))

  it('rejects a value not in options', () => {
    expect(selectSchema.safeParse({ country: 'zz' }).success).toBe(false)
  })

  it('accepts a valid option value', () => {
    expect(selectSchema.safeParse({ country: 'us' }).success).toBe(true)
  })
})

// ─── Checkbox ──────────────────────────────────────────────────────────────────

describe('buildZodSchema — checkbox field', () => {
  it('required checkbox rejects false', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'agreed', type: 'checkbox', label: 'Agree', required: true, order: 1 },
    ]))
    expect(schema.safeParse({ agreed: false }).success).toBe(false)
  })

  it('required checkbox accepts true', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'agreed', type: 'checkbox', label: 'Agree', required: true, order: 1 },
    ]))
    expect(schema.safeParse({ agreed: true }).success).toBe(true)
  })

  it('optional checkbox accepts false', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'newsletter', type: 'checkbox', label: 'Newsletter', required: false, order: 1 },
    ]))
    expect(schema.safeParse({ newsletter: false }).success).toBe(true)
  })
})

// ─── Section fields are skipped ────────────────────────────────────────────────

describe('buildZodSchema — section field', () => {
  it('section fields do not generate a Zod validator', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'sec', type: 'section', label: 'Section', required: false, order: 1 },
      { key: 'name', type: 'text', label: 'Name', required: true, order: 2 },
    ]))
    // 'sec' key must not be in schema's shape — passing without it should be fine
    const result = schema.safeParse({ name: 'Alice' })
    expect(result.success).toBe(true)
  })
})

// ─── mapZodErrors ──────────────────────────────────────────────────────────────

describe('mapZodErrors', () => {
  it('maps first error per field into a flat object', () => {
    const schema = buildZodSchema(makeSchema([
      { key: 'name', type: 'text', label: 'Name', required: true, order: 1 },
      { key: 'email', type: 'email', label: 'Email', required: true, order: 2 },
    ]))
    const result = schema.safeParse({ name: '', email: 'bad-email' })
    expect(result.success).toBe(false)
    if (!result.success) {
      const errors = mapZodErrors(result.error)
      expect(errors).toHaveProperty('name')
      expect(errors).toHaveProperty('email')
      expect(typeof errors.name).toBe('string')
    }
  })
})
