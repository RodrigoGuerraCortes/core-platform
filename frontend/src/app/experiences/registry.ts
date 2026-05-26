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
  auth: {
    loginRoute: '/condoflow/login',
    authenticatedRedirect: '/t/:tenantSlug/condoflow',
    logoutRedirect: '/condoflow/login',
  },
}

/**
 * Platform experience — implicit default for unresolved routes.
 * Exposed here for auth redirect resolution when no experience matches.
 */
export const platformExperience: ExperienceDefinition = {
  key: 'platform',
  guestEntryRoute: '/login',
  authenticatedEntryRoute: '/t/:tenantSlug/dashboard',
  routePrefixes: [], // Platform is the default — doesn't need prefix matching
  navigationScope: 'tenant',
  branding: {
    label: 'Core Platform',
    icon: 'mdi-domain',
    color: '#6200ea',
  },
  auth: {
    loginRoute: '/login',
    authenticatedRedirect: '/t/:tenantSlug/dashboard',
    logoutRedirect: '/',
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
