<script setup lang="ts">
import type { DateField } from '../../types'

const props = defineProps<{
  field: DateField
  modelValue: string | undefined
  error?: string
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <v-text-field
    type="date"
    :label="field.label"
    :model-value="modelValue ?? ''"
    :error-messages="error"
    :hint="field.description"
    :disabled="disabled"
    :required="field.required"
    :min="field.validation?.min_date"
    :max="field.validation?.max_date"
    persistent-hint
    variant="outlined"
    density="comfortable"
    @update:model-value="emit('update:modelValue', $event)"
  />
</template>
