<script setup lang="ts">
import type { TextField } from '../../types'

defineProps<{
  field: TextField
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
    :label="field.label"
    :model-value="modelValue ?? ''"
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
