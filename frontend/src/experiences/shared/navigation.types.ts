/**
 * Experience Navigation — Type definitions.
 *
 * Each experience declares its own navigation items.
 * The shell renders whatever the active experience provides.
 */

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
  /** Absolute path — includes the tenant slug prefix when applicable. */
  to: string
}

export interface ExperienceNavigation {
  /** Items for the sidebar/primary nav. */
  items: NavItem[]
}
