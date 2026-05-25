<script setup lang="ts">
/**
 * AppPageHeader — standardised page-level heading block.
 *
 * Sits at the top of a page body (inside AppPageLayout or standalone).
 * Provides title, optional description, optional breadcrumb trail,
 * and a right-aligned actions slot.
 *
 * Slots:
 *   actions  — right-side AppButton(s) or other controls
 */
defineProps<{
  title: string
  description?: string
}>()
</script>

<template>
  <div class="app-page-header d-flex align-start justify-space-between gap-4 mb-6">
    <div class="min-w-0">
      <h1 class="text-h5 font-weight-bold text-truncate">{{ title }}</h1>
      <p v-if="description" class="text-body-2 text-medium-emphasis mt-1">{{ description }}</p>
    </div>

    <div v-if="$slots.actions" class="d-flex gap-2 flex-shrink-0 align-center">
      <slot name="actions" />
    </div>
  </div>
</template>
