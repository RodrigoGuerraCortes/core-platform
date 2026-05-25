<script setup lang="ts">
/**
 * AppTextField — standard single-line text input.
 *
 * Enforces platform defaults: density=comfortable, variant=outlined.
 * Modules must not use VTextField directly — this ensures consistent
 * validation display and disabled/loading behaviour across the platform.
 *
 * Error display:
 *   Pass an array of error strings from TanStack Query / form state.
 *   The first error is shown below the field — matching Laravel's validation
 *   response shape (Record<string, string[]>).
 *
 * Forbidden patterns:
 *   <v-text-field density="comfortable" variant="outlined" ... />
 */

const model = defineModel<string>({ default: '' })

withDefaults(
  defineProps<{
    label?: string
    placeholder?: string
    hint?: string
    /** Backend validation errors for this field (string[]). First is shown. */
    errors?: string[]
    disabled?: boolean
    loading?: boolean
    type?: string
    autofocus?: boolean
    maxlength?: number
    required?: boolean
  }>(),
  {
    type: 'text',
    errors: () => [],
  },
)
</script>

<template>
  <v-text-field
    v-model="model"
    :label="label"
    :placeholder="placeholder"
    :hint="hint"
    :error-messages="errors"
    :error="errors && errors.length > 0"
    :disabled="disabled || loading"
    :loading="loading"
    :type="type"
    :autofocus="autofocus"
    :maxlength="maxlength"
    :required="required"
    density="comfortable"
    variant="outlined"
    persistent-hint
  />
</template>
