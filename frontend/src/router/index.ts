import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { dynamicFormsRoutes } from '@/modules/dynamic-forms/routes'
import { referenceRoutes } from '@/modules/reference/routes'
import { useAuthStore } from '@/stores/auth'

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
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(_to, _from, savedPosition) {
    return savedPosition ?? { top: 0 }
  },
})

// ─── Navigation guard ─────────────────────────────────────────────────────────
// bootstrapCurrentUser() in main.ts awaits completion before app.use(router),
// so isBootstrapping will always be false when this guard first runs.

router.beforeEach((to) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // Preserve the intended destination so LoginPage can redirect back.
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    // Already authenticated — send to the home page (tenant selection next).
    return { name: 'home' }
  }
})

export default router
