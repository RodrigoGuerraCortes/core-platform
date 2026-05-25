<script setup lang="ts">
import { computed } from 'vue'
import type { FilterField, FilterValues } from '../types'

/**
 * AppFilterBar — canonical filter control row for table pages.
 *
 * Renders a set of filter fields based on a declarative descriptor array.
 * Emits `update:modelValue` when any field changes, compatible with v-model.
 *
 * Supported field types:
 *   text        → debounced text input (search)
 *   select      → single-value dropdown
 *   multiselect → multiple-value dropdown
 *
 * Usage:
 *   const filterFields: FilterField[] = [
 *     { key: 'search', label: 'Search', type: 'text', placeholder: 'Name…' },
 *     { key: 'status', label: 'Status', type: 'select', options: [
 *       { label: 'Draft', value: 'draft' },
 *       { label: 'Active', value: 'active' },
 *     ]},
 *   ]
 *
 *   <AppFilterBar
 *     v-model="table.filters.value"
 *     :fields="filterFields"
 *     @update:model-value="table.setFilters"
 *   />
 *
 * Forbidden:
 *   Do not write inline filter controls in module pages.
 *   Do not use VSelect/VTextField directly for filtering.
 */

const props = defineProps<{
  modelValue: FilterValues
  fields: FilterField[]
}>()

const emit = defineEmits<{
  'update:modelValue': [values: FilterValues]
}>()

const hasActive = computed(() =>
  Object.values(props.modelValue).some(
    (v) => v !== null && v !== '' && !(Array.isArray(v) && v.length === 0),
  ),
)

function update(key: string, value: string | string[] | null): void {
  emit('update:modelValue', { ...props.modelValue, [key]: value })
}

function clear(): void {
  const cleared: FilterValues = {}
  for (const field of props.fields) {
    cleared[field.key] = null
  }
  emit('update:modelValue', cleared)
}
</script>

<template>
  <div class="d-flex align-center gap-2 flex-wrap">
    <template v-for="field in fields" :key="field.key">
      <!-- Text / search filter -->
      <v-text-field
        v-if="field.type === 'text'"
        :model-value="(modelValue[field.key] as string) ?? ''"
        :label="field.label"
        :placeholder="field.placeholder"
        density="compact"
        variant="outlined"
        hide-details
        clearable
        prepend-inner-icon="mdi-magnify"
        style="min-width: 200px; max-width: 280px;"
        @update:model-value="update(field.key, $event || null)"
        @click:clear="update(field.key, null)"
      />

      <!-- Select filter -->
      <v-select
        v-else-if="field.type === 'select'"
        :model-value="(modelValue[field.key] as string) ?? null"
        :label="field.label"
        :placeholder="field.placeholder ?? `All ${field.label}`"
        :items="field.options ?? []"
        item-title="label"
        item-value="value"
        density="compact"
        variant="outlined"
        hide-details
        clearable
        style="min-width: 160px; max-width: 220px;"
        @update:model-value="update(field.key, $event ?? null)"
      />

      <!-- Multiselect filter -->
      <v-select
        v-else-if="field.type === 'multiselect'"
        :model-value="(modelValue[field.key] as string[]) ?? []"
        :label="field.label"
        :items="field.options ?? []"
        item-title="label"
        item-value="value"
        density="compact"
        variant="outlined"
        hide-details
        clearable
        multiple
        chips
        style="min-width: 200px; max-width: 300px;"
        @update:model-value="update(field.key, $event?.length ? $event : null)"
      />
    </template>

    <!-- Clear all button — shown when any filter is active -->
    <v-btn
      v-if="hasActive"
      variant="text"
      size="small"
      density="compact"
      icon="mdi-filter-remove-outline"
      title="Clear all filters"
      @click="clear"
    />
  </div>
</template>
