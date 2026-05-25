import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { dynamicFormsRoutes } from '@/modules/dynamic-forms/routes'
import { condoflowRoutes, condoflowPublicRoutes } from '@/modules/condoflow/routes'
import { referenceRoutes } from '@/modules/reference/routes'
import { useAuthStore } from '@/stores/auth'
import { resolveExperience, getGuestEntryRoute, getAuthenticatedEntryRoute } from '@/app/experiences'

/**
 * Route records are registered here at the top level.
 * Each module appends its own routes via the `routes` array or by calling
 * router.addRoute() from its service provider / plugin.
 *
 * Convention: module routes are lazy-loaded and namespaced under a shared
 * parent path. Example: /t/:tenantId/forms → DynamicForms module.
 *
 * Route meta:
 *   requiresAuth  — redirect to /login if unauthenticated
 *   guestOnly     — redirect to /home if already authenticated
 */
const routes: RouteRecordRaw[] = [
  {
    // Tenant-scoped shell — all authenticated, tenant-aware pages nest here.
    path: '/t/:tenantSlug',
    component: () => import('@/layouts/TenantLayout.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
    children: [
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('@/app/pages/DashboardPage.vue'),
        meta: { requiresAuth: true, requiresTenant: true },
      },
      ...dynamicFormsRoutes,
      ...condoflowRoutes,
      ...referenceRoutes,
      // Additional module routes are added here as modules are built.
    ],
  },

  {
    // Unauthenticated shell — login, password reset, etc.
    path: '/',
    component: () => import('@/layouts/GuestLayout.vue'),
    children: [
      {
        path: '',
        name: 'home',
        component: () => import('@/app/pages/HomePage.vue'),
      },
      {
        path: 'login',
        name: 'login',
        component: () => import('@/app/pages/LoginPage.vue'),
        meta: { guestOnly: true },
      },
    ],
  },

  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/app/pages/NotFoundPage.vue'),
  },

  // ─── CondoFlow independent login ────────────────────────────────────────────
  ...condoflowPublicRoutes,
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(_to, _from, savedPosition) {
    return savedPosition ?? { top: 0 }
  },
})

// ─── Navigation guard ─────────────────────────────────────────────────────────
// Experience-aware auth redirects. The guard resolves which experience owns
// the target route and redirects to the appropriate entry point.
// NO hardcoded vertical names — only the experience layer.

router.beforeEach((to) => {
  const authStore = useAuthStore()
  const resolved = resolveExperience(to)

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // Guest hitting a protected route → redirect to owning experience's login.
    if (resolved) {
      const guestEntry = getGuestEntryRoute(resolved.experience)
      return { path: guestEntry, query: { redirect: to.fullPath } }
    }
    // Platform core → default login
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    // Authenticated user hitting a guest-only route → redirect to owning experience's dashboard.
    if (resolved) {
      const tenantSlug = (to.params as Record<string, string>).tenantSlug ?? 'default'
      const authEntry = getAuthenticatedEntryRoute(resolved.experience, { tenantSlug })
      return { path: authEntry }
    }
    // Platform core → home
    return { name: 'home' }
  }
})

export default router
