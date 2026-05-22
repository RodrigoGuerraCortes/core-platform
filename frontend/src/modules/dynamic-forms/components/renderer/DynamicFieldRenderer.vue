<script setup lang="ts">
import { computed } from 'vue'
import type { FormField } from '../../types'
import { resolveFieldRenderer } from './registry'

/**
 * Resolves the correct field renderer from the registry and delegates rendering.
 *
 * Responsibilities:
 * - Resolve the async renderer component by field type.
 * - Forward model-value and error props.
 * - Fail safely (skip silently) for unregistered types.
 */
const props = defineProps<{
  field: FormField
  modelValue: unknown
  error?: string
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: unknown]
}>()

const renderer = computed(() => resolveFieldRenderer(props.field.type))
</script>

<template>
  <component
    :is="renderer"
    v-if="renderer"
    :field="field"
    :model-value="modelValue"
    :error="error"
    :disabled="disabled"
    @update:model-value="emit('update:modelValue', $event)"
  />
  <!-- Unsupported field types are silently skipped in production.
       In development a console warning is emitted by the registry. -->
</template>
