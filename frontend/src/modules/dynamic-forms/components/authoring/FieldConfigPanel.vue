<script setup lang="ts">
/**
 * Dispatches to the correct per-type field configuration panel.
 *
 * This is the only place that knows the type-to-component mapping for authoring.
 * Adding a new field type requires an explicit entry in the switch below.
 * No reflection, no auto-discovery.
 */
import type { FormField } from '../../types'
import TextFieldConfig from './field-config/TextFieldConfig.vue'
import TextareaFieldConfig from './field-config/TextareaFieldConfig.vue'
import NumberFieldConfig from './field-config/NumberFieldConfig.vue'
import DateFieldConfig from './field-config/DateFieldConfig.vue'
import OptionsFieldConfig from './field-config/OptionsFieldConfig.vue'
import SimpleFieldConfig from './field-config/SimpleFieldConfig.vue'

defineProps<{
  field: FormField
}>()

const emit = defineEmits<{
  update: [patch: Partial<FormField>]
  close: []
}>()
</script>

<template>
  <v-card variant="outlined" rounded="lg" class="pa-4">
    <!-- Panel header -->
    <div class="d-flex align-center justify-space-between mb-4">
      <p class="text-body-2 font-weight-semibold text-capitalize">
        {{ field.type }} Field
      </p>
      <v-btn icon="mdi-close" size="x-small" variant="text" @click="emit('close')" />
    </div>

    <!-- Per-type config panel -->
    <TextFieldConfig
      v-if="field.type === 'text'"
      :field="field"
      @update="emit('update', $event)"
    />
    <TextareaFieldConfig
      v-else-if="field.type === 'textarea'"
      :field="field"
      @update="emit('update', $event)"
    />
    <NumberFieldConfig
      v-else-if="field.type === 'number'"
      :field="field"
      @update="emit('update', $event)"
    />
    <DateFieldConfig
      v-else-if="field.type === 'date'"
      :field="field"
      @update="emit('update', $event)"
    />
    <OptionsFieldConfig
      v-else-if="field.type === 'select' || field.type === 'radio'"
      :field="field"
      @update="emit('update', $event)"
    />
    <SimpleFieldConfig
      v-else-if="field.type === 'email' || field.type === 'checkbox' || field.type === 'section'"
      :field="field"
      @update="emit('update', $event)"
    />
    <p v-else class="text-caption text-medium-emphasis">
      No configuration available for this field type.
    </p>
  </v-card>
</template>
