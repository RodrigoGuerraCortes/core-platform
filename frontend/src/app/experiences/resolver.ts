/**
 * Experience Resolver — Determines which experience owns a given route.
 *
 * Used by the router guard to make experience-aware redirect decisions
 * without hardcoding vertical-specific path checks.
 */

import type { RouteLocationNormalized } from 'vue-router'
import type { ExperienceDefinition, ResolvedExperience } from './types'
import { experiences } from './registry'

/**
 * Normalize a route prefix pattern for comparison.
 * Replaces dynamic segments like `:tenantSlug` with a wildcard-like
 * matching strategy (we check each static segment).
 */
function pathMatchesPrefix(path: string, prefix: string): boolean {
  // Fast path: literal prefix match (covers /condoflow/*)
  if (path.startsWith(prefix)) {
    return true
  }

  // Handle parameterized prefixes like /t/:tenantSlug/condoflow
  const pathSegments = path.split('/').filter(Boolean)
  const prefixSegments = prefix.split('/').filter(Boolean)

  if (pathSegments.length < prefixSegments.length) {
    return false
  }

  for (let i = 0; i < prefixSegments.length; i++) {
    const seg = prefixSegments[i]
    // Dynamic segment (starts with :) matches anything
    if (seg.startsWith(':')) {
      continue
    }
    if (pathSegments[i] !== seg) {
      return false
    }
  }

  return true
}

/**
 * Resolve which experience owns a route location.
 * Returns null if the route belongs to the platform core.
 */
export function resolveExperience(
  to: Pick<RouteLocationNormalized, 'path'>,
): ResolvedExperience | null {
  for (const experience of experiences) {
    for (const prefix of experience.routePrefixes) {
      if (pathMatchesPrefix(to.path, prefix)) {
        return { experience }
      }
    }
  }
  return null
}

/**
 * Get the guest entry route for an experience, with tenant slug substitution.
 */
export function getGuestEntryRoute(experience: ExperienceDefinition): string {
  return experience.guestEntryRoute
}

/**
 * Get the authenticated entry route for an experience.
 * Substitutes :tenantSlug if provided.
 */
export function getAuthenticatedEntryRoute(
  experience: ExperienceDefinition,
  params?: { tenantSlug?: string },
): string {
  let route = experience.authenticatedEntryRoute
  if (params?.tenantSlug) {
    route = route.replace(':tenantSlug', params.tenantSlug)
  }
  return route
}
