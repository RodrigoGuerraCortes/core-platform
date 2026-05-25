<script setup lang="ts">
/**
 * EmptyStatesExamplePage — canonical empty state pattern reference.
 *
 * Demonstrates all official AppEmptyState presets with their canonical
 * icon, title, description, and CTA placement. This page is the visual
 * specification for empty states across all platform modules.
 *
 * Official presets:
 *   no-results   — search returned nothing
 *   no-records   — collection genuinely empty
 *   permission   — access restricted
 *   archived     — items archived
 *   disconnected — service offline
 *   filtered     — active filters produced no results
 *   onboarding   — first-time prompt
 *
 * Platform rule:
 *   NEVER write ad-hoc empty states (text-center py-12 divs).
 *   ALWAYS use AppEmptyState with a preset or explicit props.
 */
import { AppPageLayout, AppButton, AppEmptyState, AppSection } from '@/shared/ui'
import type { DetailBreadcrumb } from '@/shared/ui'

const breadcrumbs: DetailBreadcrumb[] = [
  { title: 'Reference', to: { name: 'reference' } },
  { title: 'Empty States', disabled: true },
]

// Domain-specific variants showing how modules customize presets
const domainExamples = [
  {
    preset: 'no-records' as const,
    title: 'No users yet',
    description: 'Invite your first team member to get started.',
    cta: 'Invite User',
    ctaIcon: 'mdi-account-plus-outline',
  },
  {
    preset: 'no-records' as const,
    title: 'No forms published',
    description: 'Create and publish your first form to start collecting responses.',
    cta: 'Create Form',
    ctaIcon: 'mdi-file-plus-outline',
  },
  {
    preset: 'no-records' as const,
    title: 'No approvals pending',
    description: 'All items have been reviewed. New requests will appear here.',
    cta: undefined,
    ctaIcon: undefined,
  },
  {
    preset: 'no-records' as const,
    title: 'No uploads yet',
    description: 'Drag and drop files here, or use the upload button to get started.',
    cta: 'Upload File',
    ctaIcon: 'mdi-upload-outline',
  },
]
</script>

<template>
  <AppPageLayout
    title="Empty State Patterns"
    subtitle="Official AppEmptyState presets and domain-specific variants."
    :breadcrumbs="breadcrumbs"
  >
    <!-- ── All official presets ───────────────────────────────────────── -->
    <AppSection
      title="Official Presets"
      description="Use these preset values. Do not recreate them inline."
      divider
      class="mb-8"
    >
      <v-row dense>
        <v-col cols="12" sm="6" lg="4">
          <AppEmptyState preset="no-results">
            <template #action>
              <AppButton variant="ghost" size="small">Clear search</AppButton>
            </template>
          </AppEmptyState>
        </v-col>

        <v-col cols="12" sm="6" lg="4">
          <AppEmptyState preset="no-records">
            <template #action>
              <AppButton variant="primary" prepend-icon="mdi-plus" size="small">
                Create first record
              </AppButton>
            </template>
          </AppEmptyState>
        </v-col>

        <v-col cols="12" sm="6" lg="4">
          <AppEmptyState preset="permission">
            <template #action>
              <AppButton variant="ghost" size="small" prepend-icon="mdi-arrow-left">
                Go back
              </AppButton>
            </template>
          </AppEmptyState>
        </v-col>

        <v-col cols="12" sm="6" lg="4">
          <AppEmptyState preset="archived">
            <template #action>
              <AppButton variant="secondary" size="small" prepend-icon="mdi-archive-arrow-up-outline">
                View archive
              </AppButton>
            </template>
          </AppEmptyState>
        </v-col>

        <v-col cols="12" sm="6" lg="4">
          <AppEmptyState preset="disconnected">
            <template #action>
              <AppButton variant="primary" size="small" prepend-icon="mdi-refresh">
                Retry
              </AppButton>
            </template>
          </AppEmptyState>
        </v-col>

        <v-col cols="12" sm="6" lg="4">
          <AppEmptyState preset="filtered">
            <template #action>
              <AppButton variant="ghost" size="small" prepend-icon="mdi-filter-off-outline">
                Clear filters
              </AppButton>
            </template>
          </AppEmptyState>
        </v-col>

        <v-col cols="12">
          <AppEmptyState preset="onboarding">
            <template #action>
              <div class="d-flex justify-center gap-2">
                <AppButton variant="primary" prepend-icon="mdi-play-circle-outline">
                  Get started
                </AppButton>
                <AppButton variant="ghost">Learn more</AppButton>
              </div>
            </template>
          </AppEmptyState>
        </v-col>
      </v-row>
    </AppSection>

    <!-- ── Domain-specific variants ─────────────────────────────────── -->
    <AppSection
      title="Domain-Specific Variants"
      description="Use the no-records preset with custom title/description/CTA per resource type. Never duplicate the icon or layout."
      divider
      class="mb-8"
    >
      <v-row dense>
        <v-col
          v-for="example in domainExamples"
          :key="example.title"
          cols="12"
          sm="6"
        >
          <AppEmptyState
            :preset="example.preset"
            :title="example.title"
            :description="example.description"
          >
            <template v-if="example.cta" #action>
              <AppButton
                variant="primary"
                :prepend-icon="example.ctaIcon"
                size="small"
              >
                {{ example.cta }}
              </AppButton>
            </template>
          </AppEmptyState>
        </v-col>
      </v-row>
    </AppSection>

    <!-- ── Flat variant (inside cards/sections) ──────────────────────── -->
    <AppSection
      title="Flat Variant"
      description="Use ':flat' when AppEmptyState is nested in a card. Avoids double borders."
    >
      <v-card variant="outlined" rounded="lg" class="pa-4">
        <p class="text-subtitle-2 font-weight-semibold mb-4">Related Items</p>
        <AppEmptyState
          preset="no-records"
          title="No related items"
          description="Items linked to this record will appear here."
          flat
        />
      </v-card>
    </AppSection>
  </AppPageLayout>
</template>
