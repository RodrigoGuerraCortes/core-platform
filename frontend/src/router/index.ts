import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { dynamicFormsRoutes } from '@/modules/dynamic-forms/routes'

/**
 * Route records are registered here at the top level.
 * Each module appends its own routes via the `routes` array or by calling
 * router.addRoute() from its service provider / plugin.
 *
 * Convention: module routes are lazy-loaded and namespaced under a shared
 * parent path. Example: /t/:tenantId/forms → DynamicForms module.
 */
const routes: RouteRecordRaw[] = [
  {
    // Tenant-scoped shell — all authenticated, tenant-aware pages nest here.
    path: '/t/:tenantSlug',
    component: () => import('@/layouts/TenantLayout.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
    children: [
      ...dynamicFormsRoutes,
      // Additional module routes are added here as modules are built.
    ],
  },

  {
    // Unauthenticated shell — login, register, etc.
    path: '/',
    component: () => import('@/layouts/GuestLayout.vue'),
    children: [
      {
        path: '',
        name: 'home',
        component: () => import('@/app/pages/HomePage.vue'),
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

export default router
