import { computed } from 'vue'
import type { MaybeRefOrGetter } from 'vue'
import { toValue } from 'vue'
import { getPlatformNavigation } from '@/experiences/platform/navigation'

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
 * @deprecated Use `useExperienceNavigation` from `@/experiences/shared/useExperienceNavigation`
 * for experience-aware navigation. This composable returns Platform navigation only.
 */
export function useNavigation(tenantSlug: MaybeRefOrGetter<string | null>) {
  const items = computed<NavItem[]>(() => {
    const slug = toValue(tenantSlug)
    if (!slug) return []

    return getPlatformNavigation(slug)
  })

  return { items }
}
