import type { RouteRecordRaw } from 'vue-router'

/**
 * CondoFlow module routes.
 *
 * Tenant-scoped routes are nested under /t/:tenantSlug (TenantLayout).
 * The module also exposes an independent login route for resident-facing access.
 */
export const condoflowRoutes: RouteRecordRaw[] = [
  {
    path: 'condoflow',
    name: 'condoflow.dashboard',
    component: () => import('./pages/CondoDashboardPage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
  {
    path: 'condoflow/buildings',
    name: 'condoflow.buildings.index',
    component: () => import('./pages/BuildingsIndexPage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
  {
    path: 'condoflow/units',
    name: 'condoflow.units.index',
    component: () => import('./pages/UnitsIndexPage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
  {
    path: 'condoflow/residents',
    name: 'condoflow.residents.index',
    component: () => import('./pages/ResidentsIndexPage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
  {
    path: 'condoflow/tickets',
    name: 'condoflow.tickets.index',
    component: () => import('./pages/TicketsIndexPage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
  {
    path: 'condoflow/tickets/:id',
    name: 'condoflow.tickets.detail',
    component: () => import('./pages/TicketDetailPage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
]

/**
 * CondoFlow independent login — separate from the main platform login.
 * Allows residents to access a simplified portal without the full admin shell.
 * Register at the top-level router (outside TenantLayout).
 */
export const condoflowPublicRoutes: RouteRecordRaw[] = [
  {
    path: '/condoflow/login',
    name: 'condoflow.login',
    component: () => import('./pages/CondoFlowLoginPage.vue'),
    meta: { guestOnly: true },
  },
]
