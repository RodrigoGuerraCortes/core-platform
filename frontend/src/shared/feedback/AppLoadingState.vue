<script setup lang="ts">
/**
 * AppLoadingState — standard async loading placeholder.
 *
 * Renders a Vuetify skeleton loader with the correct density.
 * Modules must not inline skeleton loaders or spinners — use this component.
 *
 * `type` maps directly to Vuetify's skeleton-loader type prop.
 * Prefer 'list' for table/list views, 'card' for card grids, 'form' for forms.
 *
 * Forbidden patterns:
 *   <v-skeleton-loader type="list-item-two-line" />
 *   <v-progress-circular indeterminate />   ← for page-level loading
 */

withDefaults(
  defineProps<{
    /** Number of repeated skeleton rows (for list/card types). */
    rows?: number
    /** Vuetify skeleton-loader type string. */
    type?: string
  }>(),
  {
    rows: 3,
    type: 'list-item-two-line',
  },
)
</script>

<template>
  <div class="d-flex flex-column gap-3" aria-busy="true" aria-label="Loading…">
    <v-skeleton-loader
      v-for="n in rows"
      :key="n"
      :type="type"
    />
  </div>
</template>
