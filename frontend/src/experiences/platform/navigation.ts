/**
 * Platform Experience — Navigation items.
 *
 * These are the sidebar entries shown when the user is inside
 * the Platform experience (admin dashboard, forms, reference, etc.)
 */

import type { NavItem } from '../shared/navigation.types'

export function getPlatformNavigation(tenantSlug: string): NavItem[] {
  const base = `/t/${tenantSlug}`

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
    {
      label: 'Reference',
      icon: 'mdi-book-open-page-variant-outline',
      name: 'reference',
      to: `${base}/reference`,
    },
  ]
}
