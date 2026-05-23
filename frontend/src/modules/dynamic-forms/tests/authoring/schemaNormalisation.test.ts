import { describe, it, expect } from 'vitest'
import { useDraftSchema } from '../../composables/useDraftSchema'
import type { FormSchema, SelectField } from '../../types'

/**
 * Schema normalisation contract tests.
 * These verify the deterministic output guarantees of useDraftSchema.
 */
describe('Schema normalisation', () => {
  it('order values are always a contiguous 0-based sequence', () => {
    const draft = useDraftSchema()
    draft.addField('text')
    draft.addField('email')
    draft.addField('number')
    draft.addField('date')
    draft.removeField(draft.sortedFields.value[1].key) // remove index 1

    const orders = draft.sortedFields.value.map((f) => f.order)
    expect(orders).toEqual([0, 1, 2])
  })

  it('field keys are valid slug identifiers', () => {
    const draft = useDraftSchema()
    draft.addField('text')
    draft.addField('text')
    draft.addField('text')
    const keys = draft.sortedFields.value.map((f) => f.key)
    for (const key of keys) {
      expect(key).toMatch(/^[a-z0-9_]+$/)
    }
  })

  it('updateField with label "Hello World" derives key "hello_world"', () => {
    const draft = useDraftSchema()
    draft.addField('text')
    const key = draft.sortedFields.value[0].key
    draft.updateField(key, { label: 'Hello World' })
    const updated = draft.sortedFields.value.find((f) => f.label === 'Hello World')
    expect(updated?.key).toBe('hello_world')
  })

  it('select field options are preserved across moves', () => {
    const schema: FormSchema = {
      version: 1,
      title: 'T',
      fields: [
        { key: 'a', type: 'text', label: 'A', required: false, order: 0 },
        {
          key: 'role',
          type: 'select',
          label: 'Role',
          required: false,
          order: 1,
          options: [{ value: 'admin', label: 'Admin' }, { value: 'user', label: 'User' }],
        },
      ],
    }
    const draft = useDraftSchema(schema)
    draft.moveField('role', 'up')
    const movedField = draft.sortedFields.value.find((f) => f.key === 'role') as SelectField
    expect(movedField.options).toHaveLength(2)
    expect(movedField.options[0].value).toBe('admin')
  })

  it('schema JSON output is deterministic for the same sequence of operations', () => {
    function buildSchema() {
      const draft = useDraftSchema()
      draft.setTitle('My Form')
      draft.addField('text')
      draft.addField('email')
      draft.updateField(draft.sortedFields.value[0].key, { label: 'Full Name' })
      return JSON.stringify(draft.schema.value)
    }

    expect(buildSchema()).toBe(buildSchema())
  })

  it('immutable published version is never mutated by markSaved', () => {
    const original: FormSchema = { version: 1, title: 'Published', fields: [] }
    const snapshot = JSON.stringify(original)
    const draft = useDraftSchema(original)
    draft.setTitle('Modified')
    draft.markSaved(draft.schema.value)
    // original object should not have changed
    expect(JSON.stringify(original)).toBe(snapshot)
  })
})
