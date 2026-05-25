<script setup lang="ts">
/**
 * AppDetailLayout — canonical enterprise detail screen wrapper.
 *
 * Use for any single-entity page: user detail, form editor, approval detail, etc.
 * Provides a sticky header with breadcrumbs, status chip, title, metadata,
 * actions, optional tabs, a main content area, and an optional right sidebar.
 *
 * Slots:
 *   breadcrumbs   — v-breadcrumbs or custom nav trail (optional)
 *   status        — AppStatusChip or AppEntityStatus (optional)
 *   title         — custom title content, overrides `title` prop
 *   subtitle      — text beneath the title
 *   metadata      — AppEntityMeta row(s) under the header
 *   actions       — right-aligned AppButton group
 *   tabs          — v-tabs bar (optional; appears below header)
 *   default       — main content area
 *   sidebar       — right rail (optional; hidden on mobile)
 *   activity      — activity / timeline panel (optional)
 *
 * Loading/error props delegate to AppLoadingState / AppErrorState
 * so page components don't need to conditionally wrap.
 *
 * Layout:
 *   ┌────────────────────────────────────────┐
 *   │ [breadcrumbs]                          │
 *   │ [status]  Title               [actions]│
 *   │ [subtitle]                             │
 *   │ [metadata]                             │
 *   │ [tabs]                                 │
 *   ├──────────────────────┬─────────────────┤
 *   │ default slot         │ sidebar (lg+)   │
 *   │                      │                 │
 *   │ [activity]           │                 │
 *   └──────────────────────┴─────────────────┘
 */
import AppLoadingState from '../feedback/AppLoadingState.vue'
import AppErrorState from '../feedback/AppErrorState.vue'
import type { RouteLocationRaw } from 'vue-router'

export interface DetailBreadcrumb {
  title: string
  to?: RouteLocationRaw
  disabled?: boolean
}

withDefaults(
  defineProps<{
    /** Primary entity name shown as the page title. */
    title?: string
    /** Secondary descriptor below the title. */
    subtitle?: string
    /** Optional breadcrumbs. Use DetailBreadcrumb[]. */
    breadcrumbs?: DetailBreadcrumb[]
    /** Bind to TanStack Query isLoading. */
    loading?: boolean
    /** Bind to TanStack Query isError. */
    error?: boolean
    /** Custom error copy. */
    errorMessage?: string
    /**
     * When true, the header bar sticks to the top on scroll.
     * Default: true on desktop, false on mobile.
     */
    stickyHeader?: boolean
    /** Show a divider between header and content. Default: true. */
    headerDivider?: boolean
    /** Show the right sidebar column. Default: false. */
    withSidebar?: boolean
  }>(),
  {
    title: undefined,
    subtitle: undefined,
    breadcrumbs: undefined,
    loading: false,
    error: false,
    errorMessage: undefined,
    stickyHeader: true,
    headerDivider: true,
    withSidebar: false,
  },
)
</script>

<template>
  <div class="app-detail-layout">
    <!-- ── Sticky header ──────────────────────────────────────────────────── -->
    <div
      :class="[
        'app-detail-header',
        { 'app-detail-header--sticky': stickyHeader },
        'bg-background pb-0',
      ]"
    >
      <!-- Breadcrumbs -->
      <v-breadcrumbs
        v-if="breadcrumbs && breadcrumbs.length"
        :items="breadcrumbs"
        class="pa-0 mb-2"
        density="compact"
      >
        <template #item="{ item }">
          <v-breadcrumbs-item
            :to="item.to"
            :disabled="item.disabled || !item.to"
            :title="item.title"
            class="text-caption"
          />
        </template>
        <template #divider>
          <v-icon icon="mdi-chevron-right" size="14" />
        </template>
      </v-breadcrumbs>

      <!-- Title row -->
      <div class="d-flex align-start justify-space-between gap-4 flex-wrap mb-2">
        <div class="d-flex align-start gap-3 min-w-0 flex-grow-1">
          <!-- Status chip slot (left of title) -->
          <div v-if="$slots.status" class="flex-shrink-0 mt-1">
            <slot name="status" />
          </div>

          <!-- Title -->
          <div class="min-w-0">
            <slot name="title">
              <h1 v-if="title" class="text-h5 font-weight-bold text-truncate">{{ title }}</h1>
            </slot>
            <slot name="subtitle">
              <p v-if="subtitle" class="text-body-2 text-medium-emphasis mt-0-5">{{ subtitle }}</p>
            </slot>
          </div>
        </div>

        <!-- Actions -->
        <div v-if="$slots.actions" class="d-flex gap-2 align-center flex-shrink-0">
          <slot name="actions" />
        </div>
      </div>

      <!-- Metadata row -->
      <div v-if="$slots.metadata" class="mb-3">
        <slot name="metadata" />
      </div>

      <!-- Tabs -->
      <div v-if="$slots.tabs" class="mt-1">
        <slot name="tabs" />
      </div>

      <v-divider v-if="headerDivider" class="mt-2" />
    </div>

    <!-- ── Body ──────────────────────────────────────────────────────────── -->
    <div class="app-detail-body mt-4">
      <!-- Loading / error delegates -->
      <AppLoadingState v-if="loading" />

      <AppErrorState v-else-if="error" :message="errorMessage" />

      <!-- Content + optional sidebar -->
      <div v-else :class="withSidebar ? 'app-detail-grid' : ''">
        <!-- Main content -->
        <div class="app-detail-main">
          <slot />
        </div>

        <!-- Right sidebar (desktop only) -->
        <aside v-if="withSidebar && $slots.sidebar" class="app-detail-sidebar">
          <slot name="sidebar" />
        </aside>
      </div>

      <!-- Activity / audit section — full width below content -->
      <div v-if="$slots.activity" class="mt-6">
        <slot name="activity" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.app-detail-header--sticky {
  position: sticky;
  top: 0;
  z-index: 10;
  padding-top: 16px;
}

/* Two-column layout: main + sidebar */
.app-detail-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px;
}

@media (min-width: 1024px) {
  .app-detail-grid {
    grid-template-columns: 1fr 320px;
  }
}

.app-detail-sidebar {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
</style>
