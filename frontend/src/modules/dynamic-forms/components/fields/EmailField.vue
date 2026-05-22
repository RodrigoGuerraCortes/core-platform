<script setup lang="ts">
import type { EmailField } from '../../types'

const props = defineProps<{
  field: EmailField
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
    type="email"
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
