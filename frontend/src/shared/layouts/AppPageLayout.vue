<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import AppLoadingState from '../feedback/AppLoadingState.vue'
import AppErrorState from '../feedback/AppErrorState.vue'

/**
 * AppPageLayout — the ONLY sanctioned page-level layout primitive.
 *
 * Every CRUD/admin page in every Core Platform module MUST use this component
 * as its outermost content wrapper. It provides:
 *
 *   - Deterministic page header (title, description, breadcrumbs, actions)
 *   - Consistent vertical spacing between sections
 *   - Integrated loading and error states so pages do not have to duplicate
 *     this logic inline
 *
 * Slots:
 *   default — page body content (shown when not loading/error)
 *   actions — top-right header area (AppButton group, etc.)
 *
 * Loading/error props short-circuit the default slot. Use them to bind
 * directly to TanStack Query's `isLoading` / `isError`.
 *
 * Breadcrumbs accept Vue Router `to` locations so they are type-safe.
 *
 * Forbidden patterns:
 *   <div class="d-flex align-center justify-space-between mb-6">
 *     <h1 class="text-h5 font-weight-bold">...</h1>
 *   </div>
 *
 * Example:
 *   <AppPageLayout
 *     title="Forms"
 *     description="Create and manage forms for this tenant."
 *     :loading="isLoading"
 *     :error="isError"
 *   >
 *     <template #actions>
 *       <AppButton prepend-icon="mdi-plus" @click="navigateToCreate">
 *         New Form
 *       </AppButton>
 *     </template>
 *     <!-- content -->
 *   </AppPageLayout>
 */

export interface Breadcrumb {
  title: string
  to?: RouteLocationRaw
  disabled?: boolean
}

withDefaults(
  defineProps<{
    title: string
    description?: string
    /** Bind to TanStack Query isLoading — shows AppLoadingState in body. */
    loading?: boolean
    /** Bind to TanStack Query isError — shows AppErrorState in body. */
    error?: boolean
    /** Custom error message (overrides AppErrorState default). */
    errorMessage?: string
    breadcrumbs?: Breadcrumb[]
  }>(),
  {},
)
</script>

<template>
  <div>
    <!-- ── Page header ──────────────────────────────────────────────── -->
    <div class="d-flex align-start justify-space-between mb-6 gap-4 flex-wrap">
      <div>
        <!-- Breadcrumbs -->
        <v-breadcrumbs
          v-if="breadcrumbs && breadcrumbs.length > 0"
          :items="breadcrumbs"
          class="pa-0 mb-2"
          density="compact"
        >
          <template #item="{ item }">
            <v-breadcrumbs-item
              :to="item.to"
              :disabled="item.disabled || !item.to"
              :title="item.title"
            />
          </template>
        </v-breadcrumbs>

        <h1 class="text-h5 font-weight-bold">{{ title }}</h1>

        <p
          v-if="description"
          class="text-body-2 text-medium-emphasis mt-1"
        >
          {{ description }}
        </p>
      </div>

      <!-- Actions slot — typically AppButton group -->
      <div v-if="$slots.actions" class="d-flex gap-2 align-center flex-shrink-0">
        <slot name="actions" />
      </div>
    </div>

    <!-- ── Page body ───────────────────────────────────────────────── -->
    <AppLoadingState v-if="loading" />

    <AppErrorState
      v-else-if="error"
      :message="errorMessage"
    />

    <slot v-else />
  </div>
</template>
