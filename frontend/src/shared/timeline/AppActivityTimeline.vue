<script setup lang="ts">
/**
 * AppActivityTimeline — canonical static activity / audit feed.
 *
 * Renders a chronological list of AppTimelineItem slots.
 * Not realtime — data is passed as a static array.
 *
 * Slots:
 *   default — one or more <AppTimelineItem> elements
 *
 * Usage:
 *   <AppActivityTimeline title="Activity">
 *     <AppTimelineItem
 *       v-for="event in events"
 *       :key="event.id"
 *       :icon="event.icon"
 *       :label="event.label"
 *       :timestamp="event.created_at"
 *       :actor="event.actor"
 *     />
 *   </AppActivityTimeline>
 */
withDefaults(
  defineProps<{
    title?: string
    /** Show a loading skeleton row while data is being fetched. */
    loading?: boolean
    /** Show "No activity yet" when there are no items. */
    empty?: boolean
    emptyMessage?: string
  }>(),
  {
    title: 'Activity',
    loading: false,
    empty: false,
    emptyMessage: 'No activity recorded yet.',
  },
)
</script>

<template>
  <div class="app-activity-timeline">
    <p v-if="title" class="text-subtitle-2 font-weight-semibold text-medium-emphasis mb-3 text-uppercase tracking-wide">
      {{ title }}
    </p>

    <!-- Loading skeleton -->
    <div v-if="loading" class="d-flex flex-column gap-3">
      <div v-for="n in 3" :key="n" class="d-flex gap-3 align-start">
        <v-skeleton-loader type="avatar" width="32" height="32" class="flex-shrink-0" />
        <div class="flex-grow-1">
          <v-skeleton-loader type="text" max-width="60%" />
          <v-skeleton-loader type="text" max-width="40%" class="mt-1" />
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <p v-else-if="empty" class="text-body-2 text-medium-emphasis text-center py-4">
      {{ emptyMessage }}
    </p>

    <!-- Items via default slot -->
    <div v-else class="timeline-track">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.timeline-track {
  display: flex;
  flex-direction: column;
  position: relative;
}

/* Vertical connector line drawn via pseudo-element on each item's icon column */
.timeline-track > * + * {
  margin-top: 0;
}
</style>
