<script setup lang="ts">
/**
 * AppErrorState — standard async error placeholder.
 *
 * Used when a data fetch fails. Shows a tonal error alert with an optional
 * retry action via the `action` slot.
 *
 * Slots:
 *   action — retry / fallback button (typically an AppButton variant=ghost)
 *
 * Forbidden patterns:
 *   <v-alert type="error" variant="tonal">Could not load…</v-alert>
 */

withDefaults(
  defineProps<{
    title?: string
    message?: string
  }>(),
  {
    title: 'Something went wrong',
    message: 'Could not load data. Please try again.',
  },
)
</script>

<template>
  <v-alert
    type="error"
    variant="tonal"
    rounded="lg"
    :title="title"
    :text="message"
  >
    <template v-if="$slots.action" #append>
      <slot name="action" />
    </template>
  </v-alert>
</template>
