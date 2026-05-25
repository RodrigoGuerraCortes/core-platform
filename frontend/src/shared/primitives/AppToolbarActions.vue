<script setup lang="ts">
/**
 * AppToolbarActions — standardised right-side toolbar action area.
 *
 * Provides consistent spacing, alignment and responsive collapse behaviour
 * for toolbar action groups. Wraps AppButton(s) and AppFilterBar triggers.
 *
 * Slots:
 *   default  — action buttons, filter triggers, search fields
 *
 * Usage:
 *   <AppToolbarActions>
 *     <AppButton variant="primary" @click="create">New Item</AppButton>
 *   </AppToolbarActions>
 */
withDefaults(
  defineProps<{
    /** Align items to the end (right). Default: true. */
    alignEnd?: boolean
  }>(),
  { alignEnd: true },
)
</script>

<template>
  <div
    :class="[
      'd-flex align-center gap-2 flex-wrap',
      alignEnd ? 'justify-end' : 'justify-start',
    ]"
  >
    <slot />
  </div>
</template>
