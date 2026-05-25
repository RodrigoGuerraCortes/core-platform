<script setup lang="ts">
/**
 * AppCard — standard content card for Core Platform modules.
 *
 * Wraps VCard with consistent spacing, density, and border style.
 * Modules must not use VCard directly for content containers — use AppCard.
 *
 * Slots:
 *   default  — card body content
 *   header   — replaces built-in title/subtitle with custom markup
 *   actions  — bottom action area (buttons, links)
 *
 * Loading:
 *   When `loading` is true, overlays a skeleton loader in the body.
 *   Hides default slot content so layout does not flash.
 *
 * Forbidden patterns:
 *   <v-card variant="outlined" rounded="lg" class="pa-4" />
 */

withDefaults(
  defineProps<{
    title?: string
    subtitle?: string
    loading?: boolean
    /** Remove internal padding — useful for tables or full-bleed content. */
    flush?: boolean
  }>(),
  { flush: false },
)
</script>

<template>
  <v-card variant="outlined" rounded="lg">
    <!-- Custom header overrides built-in title/subtitle -->
    <template v-if="$slots.header">
      <div class="px-4 pt-4">
        <slot name="header" />
      </div>
    </template>

    <!-- Built-in title/subtitle -->
    <template v-else-if="title">
      <v-card-item class="pb-0">
        <v-card-title class="text-body-1 font-weight-semibold">{{ title }}</v-card-title>
        <v-card-subtitle v-if="subtitle" class="mt-0">{{ subtitle }}</v-card-subtitle>
      </v-card-item>
    </template>

    <!-- Body -->
    <v-card-text :class="flush ? 'pa-0' : undefined">
      <!-- Loading skeleton replaces content -->
      <template v-if="loading">
        <v-skeleton-loader type="list-item-two-line@3" />
      </template>

      <slot v-else />
    </v-card-text>

    <!-- Actions -->
    <template v-if="$slots.actions">
      <v-divider />
      <v-card-actions class="px-4 py-3">
        <slot name="actions" />
      </v-card-actions>
    </template>
  </v-card>
</template>
