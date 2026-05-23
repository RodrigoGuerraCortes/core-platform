import type { RouteRecordRaw } from 'vue-router'

/**
 * DynamicForms module routes.
 *
 * These routes are nested under /t/:tenantSlug (TenantLayout).
 * Register them by spreading into the TenantLayout's children array.
 */
export const dynamicFormsRoutes: RouteRecordRaw[] = [
  // ─── Authoring ──────────────────────────────────────────────────────────────
  {
    path: 'forms',
    name: 'forms.index',
    component: () => import('../pages/FormsIndexPage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
  {
    path: 'forms/create',
    name: 'forms.create',
    component: () => import('../pages/FormCreatePage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
  {
    path: 'forms/:formId/edit',
    name: 'forms.edit',
    component: () => import('../pages/FormEditorPage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
  {
    path: 'forms/:formId/preview',
    name: 'forms.preview',
    component: () => import('../pages/FormPreviewPage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
  // ─── Runtime (filling / submission) ─────────────────────────────────────────
  {
    path: 'forms/:formId/fill',
    name: 'dynamic-forms.fill',
    component: () => import('../pages/FormFillPage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
]
