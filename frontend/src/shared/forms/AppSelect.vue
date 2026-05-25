<script setup lang="ts">
/**
 * AppSelect — standard select/dropdown input.
 *
 * Items can be plain strings or {title, value} objects.
 * Modules must not use VSelect directly.
 */

const model = defineModel<string | number | null>({ default: null })

withDefaults(
  defineProps<{
    label?: string
    placeholder?: string
    hint?: string
    errors?: string[]
    disabled?: boolean
    loading?: boolean
    required?: boolean
    /** Select items: string[] or {title, value}[] */
    items: Array<string | { title: string; value: string | number }>
    /** Allow clearing the selection. */
    clearable?: boolean
  }>(),
  {
    errors: () => [],
    clearable: false,
  },
)
</script>

<template>
  <v-select
    v-model="model"
    :label="label"
    :placeholder="placeholder"
    :hint="hint"
    :error-messages="errors"
    :error="errors && errors.length > 0"
    :disabled="disabled || loading"
    :loading="loading"
    :required="required"
    :items="items"
    :clearable="clearable"
    item-title="title"
    item-value="value"
    density="comfortable"
    variant="outlined"
    persistent-hint
  />
</template>
