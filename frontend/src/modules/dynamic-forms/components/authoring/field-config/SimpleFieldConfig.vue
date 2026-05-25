<script setup lang="ts">
import type { EmailField, CheckboxField, SectionField } from '../../../types'
import BaseFieldConfig from './BaseFieldConfig.vue'

// Email, Checkbox, Section — no extra properties beyond base config.
type SimpleField = EmailField | CheckboxField | SectionField

defineProps<{ field: SimpleField }>()
const emit = defineEmits<{ update: [patch: Partial<SimpleField>] }>()
</script>

<template>
  <BaseFieldConfig
    :field="field"
    :show-key="field.type !== 'section'"
    @update="emit('update', $event as Partial<SimpleField>)"
  />
</template>
