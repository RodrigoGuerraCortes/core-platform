import { defineStore } from 'pinia'
import { ref } from 'vue'

export type NotificationType = 'success' | 'error' | 'warning' | 'info'

export interface Notification {
  id: string
  type: NotificationType
  message: string
  /** Auto-dismiss after this many ms. Undefined = persist until manually dismissed. */
  timeout?: number
}

export const useNotificationsStore = defineStore('notifications', () => {
  const items = ref<Notification[]>([])

  function push(notification: Omit<Notification, 'id'>): string {
    const id = crypto.randomUUID()
    items.value.push({ ...notification, id })
    return id
  }

  function dismiss(id: string): void {
    items.value = items.value.filter((n) => n.id !== id)
  }

  function success(message: string, timeout = 4000): string {
    return push({ type: 'success', message, timeout })
  }

  function error(message: string): string {
    return push({ type: 'error', message })
  }

  return { items, push, dismiss, success, error }
})
