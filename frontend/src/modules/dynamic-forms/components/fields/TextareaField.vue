<script setup lang="ts">
import type { TextareaField } from '../../types'

const props = defineProps<{
  field: TextareaField
  modelValue: string | undefined
  error?: string
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <v-textarea
    :label="field.label"
    :model-value="modelValue ?? ''"
    :error-messages="error"
    :hint="field.description"
    :disabled="disabled"
    :required="field.required"
    persistent-hint
    variant="outlined"
    density="comfortable"
    auto-grow
    rows="3"
    @update:model-value="emit('update:modelValue', $event)"
  />
</template>
