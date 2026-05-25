<script setup lang="ts">
import type { CheckboxField } from '../../types'

defineProps<{
  field: CheckboxField
  modelValue: boolean | undefined
  error?: string
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()
</script>

<template>
  <v-checkbox
    :label="field.label"
    :model-value="!!modelValue"
    :error-messages="error"
    :hint="field.description"
    :disabled="disabled"
    :required="field.required"
    persistent-hint
    density="comfortable"
    @update:model-value="emit('update:modelValue', !!$event)"
  />
</template>
