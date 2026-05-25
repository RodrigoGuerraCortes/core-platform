<script setup lang="ts">
/**
 * AppTextarea — standard multi-line text input.
 *
 * Same governance contract as AppTextField: density=comfortable, variant=outlined.
 * Modules must not use VTextarea directly.
 */

const model = defineModel<string>({ default: '' })

withDefaults(
  defineProps<{
    label?: string
    placeholder?: string
    hint?: string
    errors?: string[]
    disabled?: boolean
    loading?: boolean
    rows?: number
    autofocus?: boolean
    required?: boolean
  }>(),
  {
    rows: 4,
    errors: () => [],
  },
)
</script>

<template>
  <v-textarea
    v-model="model"
    :label="label"
    :placeholder="placeholder"
    :hint="hint"
    :error-messages="errors"
    :error="errors && errors.length > 0"
    :disabled="disabled || loading"
    :loading="loading"
    :rows="rows"
    :autofocus="autofocus"
    :required="required"
    density="comfortable"
    variant="outlined"
    persistent-hint
  />
</template>
