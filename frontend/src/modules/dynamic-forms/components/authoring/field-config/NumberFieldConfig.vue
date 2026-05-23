<script setup lang="ts">
import type { NumberField } from '../../../types'
import BaseFieldConfig from './BaseFieldConfig.vue'

const props = defineProps<{ field: NumberField }>()
const emit = defineEmits<{ update: [patch: Partial<NumberField>] }>()
</script>

<template>
  <div class="d-flex flex-column gap-3">
    <BaseFieldConfig :field="field" @update="emit('update', $event as Partial<NumberField>)" />

    <v-divider />
    <p class="text-caption text-medium-emphasis">Validation</p>

    <div class="d-flex gap-3">
      <v-text-field
        :model-value="field.validation?.min ?? ''"
        label="Min"
        type="number"
        density="compact"
        variant="outlined"
        hide-details
        @update:model-value="(v) => emit('update', { validation: { ...field.validation, min: v !== '' ? Number(v) : undefined } })"
      />
      <v-text-field
        :model-value="field.validation?.max ?? ''"
        label="Max"
        type="number"
        density="compact"
        variant="outlined"
        hide-details
        @update:model-value="(v) => emit('update', { validation: { ...field.validation, max: v !== '' ? Number(v) : undefined } })"
      />
    </div>

    <v-checkbox
      :model-value="field.validation?.integer_only ?? false"
      label="Integer only"
      density="compact"
      hide-details
      @update:model-value="(v) => emit('update', { validation: { ...field.validation, integer_only: Boolean(v) } })"
    />
  </div>
</template>
