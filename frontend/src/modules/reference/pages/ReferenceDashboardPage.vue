<script setup lang="ts">
/**
 * ReferenceDashboardPage — landing hub for the reference module.
 *
 * Demonstrates:
 *   - useDashboardMetricsQuery with AppLoadingState / AppErrorState
 *   - Metric card grid layout using AppCard
 *   - Navigation cards linking to each reference example
 */
import { AppPageLayout, AppCard, AppButton } from '@/shared/ui'
import { useDashboardMetricsQuery } from '../composables'
import { useRouter } from 'vue-router'

const { data: metrics, isLoading, isError, refetch } = useDashboardMetricsQuery()

const router = useRouter()

interface ExampleEntry {
  title: string
  description: string
  icon: string
  color: string
  routeName: string
}

const examples: ExampleEntry[] = [
  {
    title: 'Users Table',
    description: 'Full CRUD data table with server-side pagination, sorting, filtering, and row actions.',
    icon: 'mdi-table',
    color: 'primary',
    routeName: 'reference-users',
  },
  {
    title: 'Approval Workflow',
    description: 'Status-driven workflow with comment threads and optimistic mutation patterns.',
    icon: 'mdi-check-decagram-outline',
    color: 'success',
    routeName: 'reference-approvals',
  },
  {
    title: 'File Upload',
    description: 'Drag-and-drop upload zone with per-file progress tracking, retry, and removal.',
    icon: 'mdi-cloud-upload-outline',
    color: 'info',
    routeName: 'reference-upload',
  },
]
</script>

<template>
  <AppPageLayout
    title="Reference Cookbook"
    description="Canonical patterns for building enterprise-grade features on this platform."
  >
    <!-- Metric cards -->
    <section class="mb-8">
      <p class="text-overline text-medium-emphasis mb-3">Live Platform Metrics</p>

      <div v-if="isLoading" class="d-flex gap-4">
        <v-skeleton-loader v-for="n in 4" :key="n" type="card" width="200" />
      </div>

      <div v-else-if="isError" class="d-flex align-center gap-3 pa-4 bg-error-container rounded-lg">
        <v-icon icon="mdi-alert-outline" color="error" />
        <p class="text-body-2">Could not load metrics.</p>
        <AppButton variant="ghost" size="small" @click="refetch">Retry</AppButton>
      </div>

      <v-row v-else-if="metrics" dense>
        <v-col
          v-for="card in metrics.cards"
          :key="card.label"
          cols="12"
          sm="6"
          lg="3"
        >
          <AppCard>
            <div class="d-flex align-center gap-4">
              <v-avatar :color="card.color" variant="tonal" size="48">
                <v-icon :icon="card.icon" />
              </v-avatar>
              <div>
                <p class="text-caption text-medium-emphasis">{{ card.label }}</p>
                <p class="text-h5 font-weight-bold">
                  {{ card.value }}
                  <span v-if="card.unit" class="text-body-2 font-weight-regular">{{ card.unit }}</span>
                </p>
                <p
                  v-if="card.trend"
                  :class="card.trend === 'up' ? 'text-success' : 'text-error'"
                  class="text-caption"
                >
                  <v-icon :icon="card.trend === 'up' ? 'mdi-trending-up' : 'mdi-trending-down'" size="14" />
                  {{ card.trendValue }}
                </p>
              </div>
            </div>
          </AppCard>
        </v-col>
      </v-row>
    </section>

    <!-- Reference examples -->
    <section>
      <p class="text-overline text-medium-emphasis mb-3">Reference Patterns</p>

      <v-row>
        <v-col
          v-for="example in examples"
          :key="example.routeName"
          cols="12"
          md="4"
        >
          <AppCard class="h-100 d-flex flex-column">
            <div class="d-flex align-start gap-3 mb-3">
              <v-avatar :color="example.color" variant="tonal" size="44">
                <v-icon :icon="example.icon" />
              </v-avatar>
              <div>
                <p class="text-body-1 font-weight-semibold">{{ example.title }}</p>
                <p class="text-caption text-medium-emphasis mt-1">{{ example.description }}</p>
              </div>
            </div>
            <v-spacer />
            <div class="mt-3">
              <AppButton
                variant="tonal"
                size="small"
                append-icon="mdi-arrow-right"
                @click="router.push({ name: example.routeName })"
              >
                View example
              </AppButton>
            </div>
          </AppCard>
        </v-col>
      </v-row>
    </section>
  </AppPageLayout>
</template>
