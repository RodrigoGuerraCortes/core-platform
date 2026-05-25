<script setup lang="ts">
/**
 * AppCheckbox — standard boolean checkbox input.
 *
 * Modules must not use VCheckbox directly.
 * Error display matches AppTextField (first error shown below).
 */

const model = defineModel<boolean>({ default: false })

withDefaults(
  defineProps<{
    label?: string
    hint?: string
    errors?: string[]
    disabled?: boolean
    loading?: boolean
    required?: boolean
  }>(),
  {
    errors: () => [],
  },
)
</script>

<template>
  <v-checkbox
    v-model="model"
    :label="label"
    :hint="hint"
    :error-messages="errors"
    :error="errors && errors.length > 0"
    :disabled="disabled || loading"
    :loading="loading"
    :required="required"
    density="comfortable"
    persistent-hint
    hide-details="auto"
  />
</template>
