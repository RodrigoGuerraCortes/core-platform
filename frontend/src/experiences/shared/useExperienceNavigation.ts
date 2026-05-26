/**
 * useExperienceNavigation — Experience-aware navigation composable.
 *
 * Resolves which experience owns the current route and returns the
 * appropriate navigation items. Replaces the old hardcoded useNavigation.
 *
 * Usage:
 *   const { items, branding } = useExperienceNavigation()
 */

import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useTenantStore } from '@/stores/tenant'
import { resolveExperience } from '@/app/experiences'
import { getPlatformNavigation } from '@/experiences/platform/navigation'
import { getCondoflowNavigation } from '@/experiences/condoflow/navigation'
import { platformBranding } from '@/experiences/platform/branding'
import { condoflowBranding } from '@/experiences/condoflow/branding'
import type { NavItem } from '@/experiences/shared/navigation.types'
import type { ExperienceBranding } from '@/app/experiences/types'

/**
 * Navigation provider registry.
 * Each experience key maps to its navigation factory function.
 */
const navigationProviders: Record<string, (tenantSlug: string) => NavItem[]> = {
  condoflow: getCondoflowNavigation,
  // Future: his: getHisNavigation,
}

const brandingProviders: Record<string, ExperienceBranding> = {
  condoflow: condoflowBranding,
  // Future: his: hisBranding,
}

export function useExperienceNavigation() {
  const route = useRoute()
  const tenantStore = useTenantStore()

  const experienceKey = computed<string | null>(() => {
    const resolved = resolveExperience(route)
    return resolved?.experience.key ?? null
  })

  const items = computed<NavItem[]>(() => {
    const slug = tenantStore.tenantSlug
    if (!slug) return []

    const key = experienceKey.value
    if (key && navigationProviders[key]) {
      return navigationProviders[key](slug)
    }

    // Default: platform navigation
    return getPlatformNavigation(slug)
  })

  const branding = computed<ExperienceBranding>(() => {
    const key = experienceKey.value
    if (key && brandingProviders[key]) {
      return brandingProviders[key]
    }
    return platformBranding
  })

  return { items, branding, experienceKey }
}
