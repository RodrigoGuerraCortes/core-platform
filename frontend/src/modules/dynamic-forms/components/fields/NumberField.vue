<script setup lang="ts">
import type { NumberField } from '../../types'

const props = defineProps<{
  field: NumberField
  modelValue: number | null | undefined
  error?: string
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number | null]
}>()

function onInput(raw: string): void {
  emit('update:modelValue', raw === '' ? null : Number(raw))
}
</script>

<template>
  <v-text-field
    type="number"
    :label="field.label"
    :model-value="modelValue !== null && modelValue !== undefined ? String(modelValue) : ''"
    :error-messages="error"
    :hint="field.description"
    :disabled="disabled"
    :required="field.required"
    :min="field.validation?.min"
    :max="field.validation?.max"
    persistent-hint
    variant="outlined"
    density="comfortable"
    @update:model-value="onInput"
  />
</template>
