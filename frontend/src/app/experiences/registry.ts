/**
 * Experience Registry — Central catalog of all vertical experiences.
 *
 * Each module registers its experience definition here.
 * The core router NEVER references vertical names directly — only this registry.
 *
 * To add a new vertical:
 *  1. Define its ExperienceDefinition
 *  2. Add it to the `experiences` array below
 *  3. Done — the router guard handles the rest automatically
 */

import type { ExperienceDefinition } from './types'

// ─── Platform Core ────────────────────────────────────────────────────────────
// The platform itself is the "default" experience. It doesn't need registration
// because the resolver returns null for unmatched routes (= platform owns them).

// ─── Vertical Experiences ─────────────────────────────────────────────────────

export const condoflowExperience: ExperienceDefinition = {
  key: 'condoflow',
  guestEntryRoute: '/condoflow/login',
  authenticatedEntryRoute: '/t/:tenantSlug/condoflow',
  routePrefixes: ['/condoflow', '/t/:tenantSlug/condoflow'],
  navigationScope: 'hybrid',
  branding: {
    label: 'CondoFlow',
    icon: 'mdi-office-building',
    color: '#1565c0',
  },
}

// ─── Registry ─────────────────────────────────────────────────────────────────

/**
 * All registered experiences. Order matters — first match wins in the resolver.
 * More specific prefixes should come before less specific ones.
 */
export const experiences: ExperienceDefinition[] = [
  condoflowExperience,
  // Future: hisExperience, erpExperience, etc.
]
