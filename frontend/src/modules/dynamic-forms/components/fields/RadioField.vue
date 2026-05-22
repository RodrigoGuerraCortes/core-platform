<script setup lang="ts">
import type { RadioField } from '../../types'

const props = defineProps<{
  field: RadioField
  modelValue: string | null | undefined
  error?: string
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | null]
}>()
</script>

<template>
  <v-radio-group
    :label="field.label"
    :model-value="modelValue ?? null"
    :error-messages="error"
    :disabled="disabled"
    :hint="field.description"
    persistent-hint
    @update:model-value="emit('update:modelValue', $event)"
  >
    <v-radio
      v-for="option in field.options"
      :key="option.value"
      :label="option.label"
      :value="option.value"
    />
  </v-radio-group>
</template>
