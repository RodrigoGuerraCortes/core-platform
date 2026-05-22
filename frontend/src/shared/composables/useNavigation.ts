import { computed } from 'vue'
import type { MaybeRefOrGetter } from 'vue'
import { toValue } from 'vue'

export interface NavItem {
  /** Display label shown in the sidebar. */
  label: string
  /** Material Design Icon name (mdi-*). */
  icon: string
  /**
   * Vue Router route name used for active-state detection.
   * An item is considered active when the current route name starts with this value.
   */
  name: string
  /** Absolute path — includes the tenant slug prefix. */
  to: string
}

/**
 * Returns a reactive list of top-level navigation items for the given tenant.
 *
 * Accepts any reactive source (ref, computed, getter) so callers are not
 * forced to unwrap first.
 *
 * Future modules register their entry here (or via an injected registry).
 */
export function useNavigation(tenantSlug: MaybeRefOrGetter<string | null>) {
  const items = computed<NavItem[]>(() => {
    const slug = toValue(tenantSlug)
    if (!slug) return []

    const base = `/t/${slug}`

    return [
      {
        label: 'Dashboard',
        icon: 'mdi-view-dashboard-outline',
        name: 'dashboard',
        to: `${base}/dashboard`,
      },
      {
        label: 'Forms',
        icon: 'mdi-file-document-multiple-outline',
        name: 'forms.index',
        to: `${base}/forms`,
      },
      // Future modules append items here (or use a plugin registry).
    ]
  })

  return { items }
}
