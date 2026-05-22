<script setup lang="ts">
import type { SectionField } from '../../types'

defineProps<{
  field: SectionField
}>()
</script>

<template>
  <div class="df-section mt-4 mb-2">
    <v-divider class="mb-4" />
    <p class="text-subtitle-1 font-weight-bold mb-1">
      {{ field.label }}
    </p>
    <p v-if="field.description" class="text-body-2 text-medium-emphasis mb-2">
      {{ field.description }}
    </p>
  </div>
</template>
