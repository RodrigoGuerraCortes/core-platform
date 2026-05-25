<script setup lang="ts">
import type { TextField } from '../../../types'
import BaseFieldConfig from './BaseFieldConfig.vue'

defineProps<{ field: TextField }>()
const emit = defineEmits<{ update: [patch: Partial<TextField>] }>()
</script>

<template>
  <div class="d-flex flex-column gap-3">
    <BaseFieldConfig :field="field" @update="emit('update', $event as Partial<TextField>)" />

    <v-divider />
    <p class="text-caption text-medium-emphasis">Validation</p>

    <div class="d-flex gap-3">
      <v-text-field
        :model-value="field.validation?.min_length ?? ''"
        label="Min Length"
        type="number"
        density="compact"
        variant="outlined"
        hide-details
        @update:model-value="(v) => emit('update', { validation: { ...field.validation, min_length: v ? Number(v) : undefined } })"
      />
      <v-text-field
        :model-value="field.validation?.max_length ?? ''"
        label="Max Length"
        type="number"
        density="compact"
        variant="outlined"
        hide-details
        @update:model-value="(v) => emit('update', { validation: { ...field.validation, max_length: v ? Number(v) : undefined } })"
      />
    </div>
  </div>
</template>
