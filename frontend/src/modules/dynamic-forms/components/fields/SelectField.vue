<script setup lang="ts">
import type { SelectField } from '../../types'

defineProps<{
  field: SelectField
  modelValue: string | null | undefined
  error?: string
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | null]
}>()
</script>

<template>
  <v-select
    :label="field.label"
    :items="field.options"
    item-title="label"
    item-value="value"
    :model-value="modelValue ?? null"
    :error-messages="error"
    :hint="field.description"
    :disabled="disabled"
    :required="field.required"
    persistent-hint
    variant="outlined"
    density="comfortable"
    @update:model-value="emit('update:modelValue', $event)"
  />
</template>
