<script setup lang="ts">
import { watch } from 'vue'
import { useNotificationsStore } from '@/stores/notifications'
import type { NotificationType } from '@/stores/notifications'

const notifications = useNotificationsStore()

/**
 * Auto-dismiss notifications that carry a timeout.
 * We watch for new items and schedule dismissal via setTimeout.
 * The store's dismiss() is idempotent so double-calls are safe.
 */
watch(
  () => notifications.items.map((n) => n.id),
  (newIds, oldIds) => {
    const prevIds = new Set(oldIds ?? [])
    for (const item of notifications.items) {
      if (!prevIds.has(item.id) && item.timeout && item.timeout > 0) {
        setTimeout(() => notifications.dismiss(item.id), item.timeout)
      }
    }
  },
)

const COLOR_MAP: Record<NotificationType, string> = {
  success: 'success',
  error: 'error',
  warning: 'warning',
  info: 'info',
}
</script>

<template>
  <!-- Fixed bottom-right stack — independent of layout context. -->
  <div class="notification-stack" role="alert" aria-live="polite">
    <v-alert
      v-for="n in notifications.items"
      :key="n.id"
      :type="n.type"
      :color="COLOR_MAP[n.type]"
      :text="n.message"
      closable
      density="compact"
      variant="tonal"
      class="notification-item"
      @click:close="notifications.dismiss(n.id)"
    />
  </div>
</template>

<style scoped>
.notification-stack {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  width: min(400px, calc(100vw - 3rem));
}

.notification-item {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
