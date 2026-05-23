<script setup lang="ts">
import type { FormField } from '../../types'

const props = defineProps<{
  fields: FormField[]
  selectedKey: string | null
}>()

const emit = defineEmits<{
  select: [key: string]
  remove: [key: string]
  move: [key: string, direction: 'up' | 'down']
}>()

function typeLabel(type: FormField['type']): string {
  const map: Record<string, string> = {
    text: 'Text', textarea: 'Textarea', number: 'Number', email: 'Email',
    date: 'Date', select: 'Select', radio: 'Radio', checkbox: 'Checkbox',
    section: 'Section', file: 'File',
  }
  return map[type] ?? type
}

function typeIcon(type: FormField['type']): string {
  const map: Record<string, string> = {
    text: 'mdi-format-text', textarea: 'mdi-text-long', number: 'mdi-numeric',
    email: 'mdi-email-outline', date: 'mdi-calendar-outline',
    select: 'mdi-chevron-down-box-outline', radio: 'mdi-radiobox-marked',
    checkbox: 'mdi-checkbox-outline', section: 'mdi-minus', file: 'mdi-paperclip',
  }
  return map[type] ?? 'mdi-form-textbox'
}
</script>

<template>
  <div>
    <!-- Empty state -->
    <div v-if="fields.length === 0" class="text-center py-8 text-medium-emphasis">
      <v-icon icon="mdi-form-textbox" size="40" class="mb-2" />
      <p class="text-body-2">No fields yet. Add a field to get started.</p>
    </div>

    <!-- Field rows -->
    <v-list v-else density="compact" rounded="lg" border>
      <template v-for="(field, idx) in fields" :key="field.key">
        <v-list-item
          :active="selectedKey === field.key"
          active-color="primary"
          rounded="lg"
          class="py-2"
          @click="emit('select', field.key)"
        >
          <template #prepend>
            <v-icon :icon="typeIcon(field.type)" size="18" class="mr-2" />
          </template>

          <v-list-item-title class="text-body-2 font-weight-medium">
            {{ field.label || '(unlabelled)' }}
          </v-list-item-title>
          <v-list-item-subtitle class="text-caption">
            {{ typeLabel(field.type) }}
            <span v-if="field.required" class="text-error ml-1">*</span>
          </v-list-item-subtitle>

          <template #append>
            <div class="d-flex gap-1 align-center">
              <v-btn
                icon="mdi-chevron-up"
                size="x-small"
                variant="text"
                :disabled="idx === 0"
                @click.stop="emit('move', field.key, 'up')"
              />
              <v-btn
                icon="mdi-chevron-down"
                size="x-small"
                variant="text"
                :disabled="idx === fields.length - 1"
                @click.stop="emit('move', field.key, 'down')"
              />
              <v-btn
                icon="mdi-delete-outline"
                size="x-small"
                variant="text"
                color="error"
                @click.stop="emit('remove', field.key)"
              />
            </div>
          </template>
        </v-list-item>

        <v-divider v-if="idx < fields.length - 1" />
      </template>
    </v-list>
  </div>
</template>
