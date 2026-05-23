import { ref, computed } from 'vue'
import type { FormField, FormSchema, SelectField, RadioField } from '../types'

/**
 * Manages local draft schema state for the form editor.
 *
 * Design principles:
 * - All mutations return new objects — no in-place mutations of nested state.
 * - `isDirty` tracks whether local state diverges from the last saved schema.
 * - Field `order` values are always normalised (0, 1, 2, …) after every operation.
 * - Field `key` is auto-derived from `label` but can be overridden manually.
 * - The composable does NOT talk to the API — the caller is responsible for saving.
 *
 * Usage:
 *   const draft = useDraftSchema(initialSchema)
 *   draft.addField('text')
 *   draft.updateField('my_key', { label: 'New Label' })
 *   draft.moveField('my_key', 'up')
 *   draft.removeField('my_key')
 *   // When ready to save:
 *   const schema = draft.schema.value
 */
export function useDraftSchema(initialSchema?: FormSchema) {
  // ─── State ────────────────────────────────────────────────────────────────

  const _empty: FormSchema = { version: 1, title: 'Untitled Form', fields: [] }
  const _saved = ref<FormSchema>(initialSchema ?? _empty)
  const _local = ref<FormSchema>(cloneSchema(_saved.value))

  // ─── Computed ─────────────────────────────────────────────────────────────

  /** Current working schema (reflects all unsaved edits). */
  const schema = computed(() => _local.value)

  /** True when local state differs from the last saved (persisted) version. */
  const isDirty = computed(
    () => JSON.stringify(_local.value) !== JSON.stringify(_saved.value),
  )

  const fieldCount = computed(() => _local.value.fields.length)

  // ─── Schema-level operations ───────────────────────────────────────────────

  function setTitle(title: string): void {
    _local.value = { ..._local.value, title }
  }

  /** Call after a successful API save to reset the dirty flag. */
  function markSaved(savedSchema: FormSchema): void {
    _saved.value = cloneSchema(savedSchema)
    _local.value = cloneSchema(savedSchema)
  }

  /** Replace the entire schema (e.g. when loading a newer version from the API). */
  function reset(schema: FormSchema): void {
    _saved.value = cloneSchema(schema)
    _local.value = cloneSchema(schema)
  }

  // ─── Field operations ──────────────────────────────────────────────────────

  /** Add a new field of the given type. Appended at the end with a generated key. */
  function addField(type: FormField['type']): void {
    const existingKeys = new Set(_local.value.fields.map((f) => f.key))
    const key = generateKey(type, existingKeys)
    const order = _local.value.fields.length

    const base = {
      key,
      label: labelForType(type),
      required: false,
      order,
      description: undefined,
    }

    let newField: FormField

    switch (type) {
      case 'select':
      case 'radio':
        newField = { ...base, type, options: [] } as SelectField | RadioField
        break
      default:
        newField = { ...base, type } as FormField
    }

    _local.value = {
      ..._local.value,
      fields: [..._local.value.fields, newField],
    }
  }

  /** Remove a field by key. Normalises order after removal. */
  function removeField(key: string): void {
    const filtered = _local.value.fields.filter((f) => f.key !== key)
    _local.value = {
      ..._local.value,
      fields: normaliseOrder(filtered),
    }
  }

  /** Move a field up or down by one position. No-op if already at boundary. */
  function moveField(key: string, direction: 'up' | 'down'): void {
    const fields = [..._local.value.fields].sort((a, b) => a.order - b.order)
    const idx = fields.findIndex((f) => f.key === key)

    if (idx === -1) return
    if (direction === 'up' && idx === 0) return
    if (direction === 'down' && idx === fields.length - 1) return

    const swapIdx = direction === 'up' ? idx - 1 : idx + 1
    ;[fields[idx], fields[swapIdx]] = [fields[swapIdx], fields[idx]]

    _local.value = {
      ..._local.value,
      fields: normaliseOrder(fields),
    }
  }

  /**
   * Apply a partial update to a field.
   * If `label` changes and the key was auto-derived, the key is re-derived
   * unless `key` is explicitly included in the patch (manual override).
   */
  function updateField(key: string, patch: Partial<FormField>): void {
    _local.value = {
      ..._local.value,
      fields: _local.value.fields.map((f) => {
        if (f.key !== key) return f

        const merged = { ...f, ...patch } as FormField

        // If key is being explicitly set, use it directly.
        // If label changed but key was not overridden, re-derive.
        if (!('key' in patch) && 'label' in patch && patch.label !== f.label) {
          const existingKeys = new Set(
            _local.value.fields.filter((x) => x.key !== key).map((x) => x.key),
          )
          merged.key = deriveKey(patch.label!, existingKeys)
        }

        return merged
      }),
    }
  }

  /** Directly set a field's key (manual override — does not re-derive). */
  function setFieldKey(currentKey: string, newKey: string): void {
    updateField(currentKey, { key: newKey } as Partial<FormField>)
  }

  // ─── Sorted fields for display ─────────────────────────────────────────────

  const sortedFields = computed(() =>
    [..._local.value.fields].sort((a, b) => a.order - b.order),
  )

  return {
    schema,
    isDirty,
    fieldCount,
    sortedFields,
    setTitle,
    addField,
    removeField,
    moveField,
    updateField,
    setFieldKey,
    markSaved,
    reset,
  }
}

// ─── Pure helpers ──────────────────────────────────────────────────────────────

function cloneSchema(schema: FormSchema): FormSchema {
  return JSON.parse(JSON.stringify(schema)) as FormSchema
}

/** Rewrite `order` so fields are numbered 0, 1, 2, … in current array order. */
function normaliseOrder(fields: FormField[]): FormField[] {
  return fields.map((f, idx) => ({ ...f, order: idx }))
}

/** Slugify a string into a valid field key. */
function deriveKey(label: string, existingKeys: Set<string>): string {
  const base = label
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
    || 'field'

  if (!existingKeys.has(base)) return base

  let n = 2
  while (existingKeys.has(`${base}_${n}`)) n++
  return `${base}_${n}`
}

function generateKey(type: FormField['type'], existingKeys: Set<string>): string {
  return deriveKey(labelForType(type), existingKeys)
}

function labelForType(type: FormField['type']): string {
  const labels: Record<FormField['type'], string> = {
    text: 'Text Field',
    textarea: 'Textarea',
    number: 'Number',
    email: 'Email',
    date: 'Date',
    select: 'Select',
    radio: 'Radio',
    checkbox: 'Checkbox',
    section: 'Section',
    file: 'File',
  }
  return labels[type] ?? type
}
