<script setup lang="ts">
import type { DateField } from '../../../types'
import BaseFieldConfig from './BaseFieldConfig.vue'

defineProps<{ field: DateField }>()
const emit = defineEmits<{ update: [patch: Partial<DateField>] }>()
</script>

<template>
  <div class="d-flex flex-column gap-3">
    <BaseFieldConfig :field="field" @update="emit('update', $event as Partial<DateField>)" />

    <v-divider />
    <p class="text-caption text-medium-emphasis">Validation</p>

    <v-text-field
      :model-value="field.validation?.min_date ?? ''"
      label="Min Date (YYYY-MM-DD)"
      density="compact"
      variant="outlined"
      hide-details
      placeholder="2024-01-01"
      @update:model-value="(v) => emit('update', { validation: { ...field.validation, min_date: v || undefined } })"
    />
    <v-text-field
      :model-value="field.validation?.max_date ?? ''"
      label="Max Date (YYYY-MM-DD)"
      density="compact"
      variant="outlined"
      hide-details
      placeholder="2099-12-31"
      @update:model-value="(v) => emit('update', { validation: { ...field.validation, max_date: v || undefined } })"
    />
  </div>
</template>
