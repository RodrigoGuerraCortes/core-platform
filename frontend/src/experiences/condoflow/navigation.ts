/**
 * CondoFlow Experience — Navigation items.
 *
 * These are the sidebar entries shown when the user is inside
 * the CondoFlow experience. No Platform modules leak here.
 */

import type { NavItem } from '../shared/navigation.types'

export function getCondoflowNavigation(tenantSlug: string): NavItem[] {
  const base = `/t/${tenantSlug}`

  return [
    {
      label: 'Dashboard',
      icon: 'mdi-view-dashboard-outline',
      name: 'condoflow.dashboard',
      to: `${base}/condoflow`,
    },
    {
      label: 'Buildings',
      icon: 'mdi-office-building-outline',
      name: 'condoflow.buildings',
      to: `${base}/condoflow/buildings`,
    },
    {
      label: 'Units',
      icon: 'mdi-door',
      name: 'condoflow.units',
      to: `${base}/condoflow/units`,
    },
    {
      label: 'Residents',
      icon: 'mdi-account-group-outline',
      name: 'condoflow.residents',
      to: `${base}/condoflow/residents`,
    },
    {
      label: 'Tickets',
      icon: 'mdi-ticket-outline',
      name: 'condoflow.tickets',
      to: `${base}/condoflow/tickets`,
    },
  ]
}
