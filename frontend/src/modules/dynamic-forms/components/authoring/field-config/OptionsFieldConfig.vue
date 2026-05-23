<script setup lang="ts">
/**
 * Shared options editor for Select and Radio fields.
 * Options are value/label pairs that can be added and removed.
 */
import { ref } from 'vue'
import type { SelectField, RadioField, SelectOption } from '../../../types'
import BaseFieldConfig from './BaseFieldConfig.vue'

type OptionsField = SelectField | RadioField

const props = defineProps<{ field: OptionsField }>()
const emit = defineEmits<{ update: [patch: Partial<OptionsField>] }>()

const newLabel = ref('')

function addOption(): void {
  const label = newLabel.value.trim()
  if (!label) return

  const value = label
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')

  const option: SelectOption = { value, label }
  emit('update', { options: [...props.field.options, option] } as Partial<OptionsField>)
  newLabel.value = ''
}

function removeOption(index: number): void {
  const updated = props.field.options.filter((_, i) => i !== index)
  emit('update', { options: updated } as Partial<OptionsField>)
}

function updateOptionLabel(index: number, label: string): void {
  const updated = props.field.options.map((opt, i) =>
    i === index ? { ...opt, label } : opt,
  )
  emit('update', { options: updated } as Partial<OptionsField>)
}
</script>

<template>
  <div class="d-flex flex-column gap-3">
    <BaseFieldConfig :field="field" @update="emit('update', $event as Partial<OptionsField>)" />

    <v-divider />
    <p class="text-caption text-medium-emphasis">Options</p>

    <!-- Existing options -->
    <div v-if="field.options.length > 0" class="d-flex flex-column gap-2">
      <div
        v-for="(opt, idx) in field.options"
        :key="idx"
        class="d-flex align-center gap-2"
      >
        <v-text-field
          :model-value="opt.label"
          density="compact"
          variant="outlined"
          hide-details
          :label="`Option ${idx + 1}`"
          @update:model-value="(v) => updateOptionLabel(idx, v)"
        />
        <v-btn
          icon="mdi-delete-outline"
          size="small"
          variant="text"
          color="error"
          @click="removeOption(idx)"
        />
      </div>
    </div>
    <p v-else class="text-caption text-medium-emphasis">No options yet.</p>

    <!-- Add option -->
    <div class="d-flex gap-2">
      <v-text-field
        v-model="newLabel"
        label="New option label"
        density="compact"
        variant="outlined"
        hide-details
        @keydown.enter.prevent="addOption"
      />
      <v-btn
        icon="mdi-plus"
        size="small"
        variant="tonal"
        color="primary"
        :disabled="!newLabel.trim()"
        @click="addOption"
      />
    </div>
  </div>
</template>
