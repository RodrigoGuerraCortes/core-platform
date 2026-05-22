import type { RouteRecordRaw } from 'vue-router'

/**
 * DynamicForms module routes.
 *
 * These routes are nested under /t/:tenantSlug (TenantLayout).
 * Register them by spreading into the TenantLayout's children array.
 */
export const dynamicFormsRoutes: RouteRecordRaw[] = [
  {
    path: 'forms/:formId/fill',
    name: 'dynamic-forms.fill',
    component: () => import('../pages/FormFillPage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
]
