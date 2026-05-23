<script setup lang="ts">
/**
 * Base field config — shared properties for all field types.
 * Individual field config panels include this component.
 */
import type { FormField } from '../../../types'

const props = defineProps<{
  field: FormField
  /** If true, the 'key' input is shown. Sections hide it. */
  showKey?: boolean
}>()

const emit = defineEmits<{
  update: [patch: Partial<FormField>]
}>()

function onLabel(value: string): void {
  emit('update', { label: value } as Partial<FormField>)
}

function onKey(value: string): void {
  // Sanitise: lowercase, replace spaces/special chars with underscore
  const sanitised = value
    .toLowerCase()
    .replace(/[^a-z0-9_]/g, '_')
    .replace(/^_+|_+$/g, '')
  emit('update', { key: sanitised } as Partial<FormField>)
}

function onRequired(value: boolean): void {
  emit('update', { required: value } as Partial<FormField>)
}

function onDescription(value: string): void {
  emit('update', { description: value || undefined } as Partial<FormField>)
}
</script>

<template>
  <div class="d-flex flex-column gap-3">
    <v-text-field
      :model-value="field.label"
      label="Label"
      density="compact"
      variant="outlined"
      hide-details="auto"
      @update:model-value="onLabel"
    />

    <v-text-field
      v-if="showKey !== false"
      :model-value="field.key"
      label="Field Key"
      density="compact"
      variant="outlined"
      hide-details="auto"
      hint="Unique identifier used in form data. Auto-derived from label."
      persistent-hint
      @update:model-value="onKey"
    />

    <v-textarea
      :model-value="field.description ?? ''"
      label="Helper Text"
      density="compact"
      variant="outlined"
      rows="2"
      auto-grow
      hide-details="auto"
      @update:model-value="onDescription"
    />

    <v-checkbox
      v-if="field.type !== 'section'"
      :model-value="field.required"
      label="Required"
      density="compact"
      hide-details
      @update:model-value="onRequired"
    />
  </div>
</template>
