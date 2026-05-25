<script setup lang="ts">
/**
 * AppEmptyState — standard empty-collection placeholder.
 *
 * Used when a data set is loaded successfully but contains zero items.
 * Provides a consistent visual pattern: icon → title → description → CTA.
 *
 * Preset scenarios via the `preset` prop eliminate ad-hoc copy in page code:
 *   'no-results'   — search returned nothing
 *   'no-records'   — collection is genuinely empty
 *   'permission'   — user lacks access
 *   'archived'     — items exist but are archived
 *   'disconnected' — service/integration offline
 *   'filtered'     — active filters produced no results
 *   'onboarding'   — first-time / getting-started prompt
 *
 * Slots:
 *   action — primary call-to-action (typically an AppButton)
 *
 * Forbidden patterns:
 *   <v-card><v-card-text class="text-center py-12">...</v-card-text></v-card>
 */

type Preset =
  | 'no-results'
  | 'no-records'
  | 'permission'
  | 'archived'
  | 'disconnected'
  | 'filtered'
  | 'onboarding'

interface PresetConfig {
  icon: string
  title: string
  description: string
}

const PRESETS: Record<Preset, PresetConfig> = {
  'no-results': {
    icon: 'mdi-magnify',
    title: 'No results found',
    description: 'Try adjusting your search term or filters.',
  },
  'no-records': {
    icon: 'mdi-inbox-outline',
    title: 'Nothing here yet',
    description: 'Get started by creating the first record.',
  },
  'permission': {
    icon: 'mdi-lock-outline',
    title: 'Access restricted',
    description: 'You don\'t have permission to view this content. Contact your administrator.',
  },
  'archived': {
    icon: 'mdi-archive-outline',
    title: 'All items archived',
    description: 'No active items. You can restore archived items from the archive view.',
  },
  'disconnected': {
    icon: 'mdi-cloud-off-outline',
    title: 'Service unavailable',
    description: 'Could not reach the service. Check your connection and try again.',
  },
  'filtered': {
    icon: 'mdi-filter-off-outline',
    title: 'No matching records',
    description: 'No records match the current filters. Try clearing or adjusting them.',
  },
  'onboarding': {
    icon: 'mdi-rocket-launch-outline',
    title: 'Let\'s get started',
    description: 'Complete the steps below to set up your first item.',
  },
}

const props = withDefaults(
  defineProps<{
    /** Preset scenario — drives icon, title, and description automatically. */
    preset?: Preset
    /** Material Design icon name (mdi-*). Overrides preset. */
    icon?: string
    /** Override or custom title. */
    title?: string
    /** Override description. */
    description?: string
    /** Use a flat card variant (no border). Default: false. */
    flat?: boolean
  }>(),
  {
    preset: undefined,
    icon: undefined,
    title: undefined,
    description: undefined,
    flat: false,
  },
)

const config = props.preset ? PRESETS[props.preset] : null
const resolvedIcon = props.icon ?? config?.icon ?? 'mdi-inbox-outline'
const resolvedTitle = props.title ?? config?.title ?? 'Nothing here'
const resolvedDescription = props.description ?? config?.description
</script>

<template>
  <v-card :variant="flat ? 'flat' : 'outlined'" rounded="lg">
    <v-card-text class="text-center py-12 px-8">
      <v-icon
        :icon="resolvedIcon"
        size="48"
        color="medium-emphasis"
        class="mb-4"
      />

      <p class="text-h6 font-weight-medium mb-2">{{ resolvedTitle }}</p>

      <p
        v-if="resolvedDescription"
        class="text-body-2 text-medium-emphasis mb-6"
      >
        {{ resolvedDescription }}
      </p>

      <div v-if="$slots.action">
        <slot name="action" />
      </div>
    </v-card-text>
  </v-card>
</template>
