/**
 * Experience Layer — Type definitions.
 *
 * An "experience" is a self-contained vertical product (CondoFlow, MiniHIS, ERP)
 * that owns a set of routes, branding, and entry points within the platform.
 *
 * The experience layer decouples the core router from vertical-specific knowledge.
 */

/** Navigation scope determines how the experience integrates with the platform shell. */
export type NavigationScope = 'tenant' | 'standalone' | 'hybrid'

export interface ExperienceBranding {
  /** Display name shown in login pages & nav. */
  label: string
  /** MDI icon key. */
  icon?: string
  /** Primary color for branding. */
  color?: string
}

/**
 * Each vertical module registers an ExperienceDefinition.
 * The core router uses these to resolve ownership and redirect logic.
 */
export interface ExperienceDefinition {
  /** Unique experience key (e.g. 'condoflow', 'his', 'erp'). */
  key: string

  /**
   * Route name or path for unauthenticated entry (login page).
   * Used when a guest hits a protected route owned by this experience.
   */
  guestEntryRoute: string

  /**
   * Route name or path for authenticated entry (dashboard/landing).
   * Used when an authenticated user hits a guest-only route owned by this experience.
   */
  authenticatedEntryRoute: string

  /**
   * Route path prefixes that this experience owns.
   * The resolver matches `to.path` against these (in order) to determine ownership.
   * Supports literal prefixes — no regex, no globs.
   */
  routePrefixes: string[]

  /**
   * How the experience relates to the platform shell.
   * - 'tenant': nested under /t/:tenantSlug (uses TenantLayout)
   * - 'standalone': fully independent layout (own login, own shell)
   * - 'hybrid': has both tenant-scoped and standalone routes
   */
  navigationScope: NavigationScope

  /** Optional branding metadata for login pages and navigation. */
  branding?: ExperienceBranding
}

/**
 * Result of resolving a route to its owning experience.
 * `null` means the route belongs to the platform core (not a vertical).
 */
export interface ResolvedExperience {
  experience: ExperienceDefinition
}
