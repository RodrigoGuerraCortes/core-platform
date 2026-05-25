<script setup lang="ts">
/**
 * AppTimelineItem — single event row inside AppActivityTimeline.
 *
 * Renders: icon dot → label → optional body → timestamp + actor.
 *
 * Slots:
 *   body — optional extended description beneath the label
 *
 * Usage:
 *   <AppTimelineItem
 *     icon="mdi-check-circle-outline"
 *     icon-color="success"
 *     label="Approved by Admin"
 *     :timestamp="event.created_at"
 *     actor="Alice Chen"
 *   />
 */

withDefaults(
  defineProps<{
    /** MDI icon name. */
    icon?: string
    /** Vuetify colour for the icon dot. */
    iconColor?: string
    /** Primary action label. */
    label: string
    /** ISO timestamp string. */
    timestamp?: string | null
    /** Actor name / email. */
    actor?: string
    /** Whether this item is the last in the list (hides the connector). */
    last?: boolean
  }>(),
  {
    icon: 'mdi-circle-small',
    iconColor: 'default',
    timestamp: null,
    actor: undefined,
    last: false,
  },
)

function formatTimestamp(iso: string | null): string {
  if (!iso) return ''
  return new Date(iso).toLocaleString(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  })
}
</script>

<template>
  <div class="timeline-item d-flex gap-3" :class="{ 'timeline-item--last': last }">
    <!-- Icon column with connector line -->
    <div class="timeline-icon-col d-flex flex-column align-center">
      <v-avatar :color="iconColor" variant="tonal" size="28" class="flex-shrink-0">
        <v-icon :icon="icon" size="16" />
      </v-avatar>
      <!-- Vertical connector — hidden on last item -->
      <div v-if="!last" class="timeline-connector flex-grow-1 mt-1" />
    </div>

    <!-- Content column -->
    <div class="flex-grow-1 pb-5">
      <p class="text-body-2 font-weight-medium">{{ label }}</p>

      <div v-if="$slots.body" class="text-body-2 text-medium-emphasis mt-1">
        <slot name="body" />
      </div>

      <p class="text-caption text-medium-emphasis mt-1">
        <span v-if="actor">{{ actor }}</span>
        <span v-if="actor && timestamp"> · </span>
        <span v-if="timestamp">{{ formatTimestamp(timestamp) }}</span>
      </p>
    </div>
  </div>
</template>

<style scoped>
.timeline-icon-col {
  min-width: 28px;
}

.timeline-connector {
  width: 2px;
  background: rgba(var(--v-border-color), 0.3);
  min-height: 16px;
}
</style>
