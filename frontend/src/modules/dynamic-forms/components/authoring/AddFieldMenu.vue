<script setup lang="ts">
import type { FormField } from '../../types'

const emit = defineEmits<{
  add: [type: FormField['type']]
}>()

const FIELD_TYPES: { type: FormField['type']; label: string; icon: string }[] = [
  { type: 'text',     label: 'Text',     icon: 'mdi-format-text' },
  { type: 'textarea', label: 'Textarea', icon: 'mdi-text-long' },
  { type: 'number',   label: 'Number',   icon: 'mdi-numeric' },
  { type: 'email',    label: 'Email',    icon: 'mdi-email-outline' },
  { type: 'date',     label: 'Date',     icon: 'mdi-calendar-outline' },
  { type: 'select',   label: 'Select',   icon: 'mdi-chevron-down-box-outline' },
  { type: 'radio',    label: 'Radio',    icon: 'mdi-radiobox-marked' },
  { type: 'checkbox', label: 'Checkbox', icon: 'mdi-checkbox-outline' },
  { type: 'section',  label: 'Section',  icon: 'mdi-minus' },
]
</script>

<template>
  <v-menu location="bottom start" :close-on-content-click="true">
    <template #activator="{ props: menuProps }">
      <v-btn
        v-bind="menuProps"
        prepend-icon="mdi-plus"
        variant="tonal"
        color="primary"
        size="small"
      >
        Add Field
      </v-btn>
    </template>

    <v-list density="compact" min-width="180">
      <v-list-item
        v-for="item in FIELD_TYPES"
        :key="item.type"
        :prepend-icon="item.icon"
        :title="item.label"
        @click="emit('add', item.type)"
      />
    </v-list>
  </v-menu>
</template>
