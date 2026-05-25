<script setup lang="ts">
/**
 * AppTableToolbar — canonical toolbar above AppDataTable.
 *
 * Provides a consistent layout for the table control surface:
 *   - Left: optional title + record count
 *   - Center: search + filters slot
 *   - Right: action buttons (create, export, etc.)
 *
 * Slots:
 *   search  — search input (typically AppTextField with prepend-icon)
 *   filters — filter controls (AppFilterBar or individual AppSelect)
 *   actions — right-side buttons (AppButton group)
 *   bulk    — shown only when `selectedCount > 0` (bulk action area)
 *
 * Usage:
 *   <AppTableToolbar :total="meta.total" :selected-count="selection.length">
 *     <template #search>
 *       <AppTextField v-model="search" placeholder="Search…" prepend-inner-icon="mdi-magnify" />
 *     </template>
 *     <template #actions>
 *       <AppButton prepend-icon="mdi-plus" @click="create">New</AppButton>
 *     </template>
 *   </AppTableToolbar>
 */

withDefaults(
  defineProps<{
    /** Optional toolbar title (distinct from page title). */
    title?: string
    /** Total record count — shown as "N records" beside the title. */
    total?: number
    /** Number of selected rows — reveals bulk actions slot. */
    selectedCount?: number
  }>(),
  {
    selectedCount: 0,
  },
)
</script>

<template>
  <div class="d-flex align-center gap-3 flex-wrap">
    <!-- Left: title + count -->
    <div v-if="title || total !== undefined" class="d-flex align-center gap-2 flex-shrink-0">
      <span v-if="title" class="text-body-2 font-weight-semibold">{{ title }}</span>
      <v-chip
        v-if="total !== undefined"
        size="x-small"
        variant="tonal"
        color="default"
      >
        {{ total.toLocaleString() }}
      </v-chip>
    </div>

    <!-- Bulk actions (shown when rows are selected) -->
    <template v-if="selectedCount > 0">
      <v-chip size="small" color="primary" variant="tonal">
        {{ selectedCount }} selected
      </v-chip>
      <slot name="bulk" />
    </template>

    <!-- Search slot -->
    <div v-if="$slots.search" class="flex-grow-1" style="max-width: 320px; min-width: 180px;">
      <slot name="search" />
    </div>

    <!-- Filters slot -->
    <template v-if="$slots.filters">
      <slot name="filters" />
    </template>

    <!-- Spacer pushes actions right -->
    <div class="flex-grow-1" />

    <!-- Actions slot -->
    <div v-if="$slots.actions" class="d-flex gap-2 align-center flex-shrink-0">
      <slot name="actions" />
    </div>
  </div>
</template>
