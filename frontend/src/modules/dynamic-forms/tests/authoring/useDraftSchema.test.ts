import { describe, it, expect } from 'vitest'
import { useDraftSchema } from '../../composables/useDraftSchema'
import type { FormSchema } from '../../types'

const baseSchema: FormSchema = {
  version: 1,
  title: 'Test Form',
  fields: [
    { key: 'name', type: 'text', label: 'Name', required: true, order: 0 },
    { key: 'email', type: 'email', label: 'Email', required: false, order: 1 },
  ],
}

describe('useDraftSchema', () => {
  // ─── Initial state ─────────────────────────────────────────────────────────

  it('initialises with the provided schema', () => {
    const draft = useDraftSchema(baseSchema)
    expect(draft.schema.value.title).toBe('Test Form')
    expect(draft.schema.value.fields).toHaveLength(2)
  })

  it('starts with isDirty = false', () => {
    const draft = useDraftSchema(baseSchema)
    expect(draft.isDirty.value).toBe(false)
  })

  it('initialises empty when no schema provided', () => {
    const draft = useDraftSchema()
    expect(draft.schema.value.title).toBe('Untitled Form')
    expect(draft.schema.value.fields).toHaveLength(0)
  })

  // ─── setTitle ──────────────────────────────────────────────────────────────

  it('setTitle marks dirty', () => {
    const draft = useDraftSchema(baseSchema)
    draft.setTitle('New Title')
    expect(draft.schema.value.title).toBe('New Title')
    expect(draft.isDirty.value).toBe(true)
  })

  // ─── addField ──────────────────────────────────────────────────────────────

  it('addField appends a field and marks dirty', () => {
    const draft = useDraftSchema(baseSchema)
    draft.addField('text')
    expect(draft.schema.value.fields).toHaveLength(3)
    expect(draft.isDirty.value).toBe(true)
  })

  it('addField generates a unique key when type key already exists', () => {
    const draft = useDraftSchema(baseSchema)
    draft.addField('text') // → 'text_field'
    draft.addField('text') // → 'text_field_2'
    const keys = draft.schema.value.fields.map((f) => f.key)
    expect(new Set(keys).size).toBe(keys.length) // all unique
  })

  it('addField initialises select/radio with empty options array', () => {
    const draft = useDraftSchema()
    draft.addField('select')
    const field = draft.schema.value.fields[0]
    expect(field.type).toBe('select')
    if (field.type === 'select') {
      expect(field.options).toEqual([])
    }
  })

  // ─── removeField ──────────────────────────────────────────────────────────

  it('removeField removes the field by key', () => {
    const draft = useDraftSchema(baseSchema)
    draft.removeField('name')
    expect(draft.schema.value.fields.find((f) => f.key === 'name')).toBeUndefined()
    expect(draft.schema.value.fields).toHaveLength(1)
  })

  it('removeField normalises remaining order values', () => {
    const draft = useDraftSchema(baseSchema)
    draft.removeField('name')
    expect(draft.sortedFields.value[0].order).toBe(0)
  })

  it('removeField on unknown key is a no-op', () => {
    const draft = useDraftSchema(baseSchema)
    draft.removeField('nonexistent')
    expect(draft.schema.value.fields).toHaveLength(2)
  })

  // ─── moveField ─────────────────────────────────────────────────────────────

  it('moveField up moves a field one position earlier', () => {
    const draft = useDraftSchema(baseSchema)
    draft.moveField('email', 'up')
    expect(draft.sortedFields.value[0].key).toBe('email')
    expect(draft.sortedFields.value[1].key).toBe('name')
  })

  it('moveField down moves a field one position later', () => {
    const draft = useDraftSchema(baseSchema)
    draft.moveField('name', 'down')
    expect(draft.sortedFields.value[0].key).toBe('email')
    expect(draft.sortedFields.value[1].key).toBe('name')
  })

  it('moveField up on first field is a no-op', () => {
    const draft = useDraftSchema(baseSchema)
    draft.moveField('name', 'up')
    expect(draft.sortedFields.value[0].key).toBe('name')
  })

  it('moveField down on last field is a no-op', () => {
    const draft = useDraftSchema(baseSchema)
    draft.moveField('email', 'down')
    expect(draft.sortedFields.value[draft.sortedFields.value.length - 1].key).toBe('email')
  })

  // ─── updateField ──────────────────────────────────────────────────────────

  it('updateField applies partial patch', () => {
    const draft = useDraftSchema(baseSchema)
    draft.updateField('name', { label: 'Full Name', key: 'name' }) // pin key to avoid re-derive
    const field = draft.schema.value.fields.find((f) => f.key === 'name')
    expect(field?.label).toBe('Full Name')
  })

  it('updateField re-derives key when label changes (auto-key)', () => {
    const draft = useDraftSchema(baseSchema)
    draft.updateField('name', { label: 'Full Name' })
    // Key should be re-derived from 'Full Name' → 'full_name'
    const updated = draft.schema.value.fields.find((f) => f.label === 'Full Name')
    expect(updated?.key).toBe('full_name')
  })

  it('updateField with explicit key overrides auto-derive', () => {
    const draft = useDraftSchema(baseSchema)
    draft.updateField('name', { label: 'Full Name', key: 'custom_key' })
    const field = draft.schema.value.fields.find((f) => f.label === 'Full Name')
    expect(field?.key).toBe('custom_key')
  })

  it('updateField does not mutate other fields', () => {
    const draft = useDraftSchema(baseSchema)
    draft.updateField('name', { label: 'Updated' })
    const emailField = draft.schema.value.fields.find((f) => f.key === 'email')
    expect(emailField?.label).toBe('Email')
  })

  // ─── markSaved / reset ────────────────────────────────────────────────────

  it('markSaved clears isDirty', () => {
    const draft = useDraftSchema(baseSchema)
    draft.setTitle('Changed')
    expect(draft.isDirty.value).toBe(true)
    draft.markSaved(draft.schema.value)
    expect(draft.isDirty.value).toBe(false)
  })

  it('reset replaces schema and clears isDirty', () => {
    const draft = useDraftSchema(baseSchema)
    draft.addField('text')
    const newSchema: FormSchema = { version: 1, title: 'Fresh', fields: [] }
    draft.reset(newSchema)
    expect(draft.schema.value.title).toBe('Fresh')
    expect(draft.schema.value.fields).toHaveLength(0)
    expect(draft.isDirty.value).toBe(false)
  })

  // ─── Immutability ─────────────────────────────────────────────────────────

  it('original schema is not mutated by addField', () => {
    const original = JSON.stringify(baseSchema)
    const draft = useDraftSchema(baseSchema)
    draft.addField('text')
    expect(JSON.stringify(baseSchema)).toBe(original)
  })

  it('sortedFields is always in ascending order order', () => {
    const draft = useDraftSchema(baseSchema)
    draft.addField('number')
    draft.addField('date')
    const orders = draft.sortedFields.value.map((f) => f.order)
    expect(orders).toEqual([...orders].sort((a, b) => a - b))
  })
})
