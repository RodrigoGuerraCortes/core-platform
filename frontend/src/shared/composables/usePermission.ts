/**
 * usePermission — lightweight permission visibility composable.
 *
 * NOT a full RBAC engine. Provides simple boolean checks against the
 * current user's roles/permissions for conditional UI rendering.
 *
 * The platform uses a flat permission list attached to the current user.
 * Each permission is a dot-namespaced string: 'users.delete', 'forms.publish'.
 *
 * Usage:
 *   const { can, canAny, canAll, is } = usePermission()
 *
 *   can('users.delete')             // true if user has this permission
 *   canAny(['users.edit', 'admin']) // true if user has at least one
 *   canAll(['users.edit', 'admin']) // true if user has all
 *   is('admin')                     // true if user has the 'admin' role
 *
 * In templates, use AppPermissionBoundary for component-level gating.
 */
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function usePermission() {
  const authStore = useAuthStore()

  /** Resolved permission list from the current user. Empty if not authenticated. */
  const permissions = computed<string[]>(
    () => (authStore.currentUser as { permissions?: string[] } | null)?.permissions ?? [],
  )

  const roles = computed<string[]>(
    () => (authStore.currentUser as { roles?: string[] } | null)?.roles ?? [],
  )

  const isPlatformAdmin = computed(
    () => (authStore.currentUser as { is_platform_admin?: boolean } | null)?.is_platform_admin ?? false,
  )

  /** True if the user holds the exact permission string. */
  function can(permission: string): boolean {
    if (isPlatformAdmin.value) return true
    return permissions.value.includes(permission)
  }

  /** True if the user holds at least one of the given permissions. */
  function canAny(list: string[]): boolean {
    if (isPlatformAdmin.value) return true
    return list.some((p) => permissions.value.includes(p))
  }

  /** True if the user holds ALL of the given permissions. */
  function canAll(list: string[]): boolean {
    if (isPlatformAdmin.value) return true
    return list.every((p) => permissions.value.includes(p))
  }

  /** True if the user has the given role. */
  function is(role: string): boolean {
    if (isPlatformAdmin.value) return true
    return roles.value.includes(role)
  }

  return { can, canAny, canAll, is, isPlatformAdmin }
}
