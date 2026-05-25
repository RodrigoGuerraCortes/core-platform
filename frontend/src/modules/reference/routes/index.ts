import type { RouteRecordRaw } from 'vue-router'

export const referenceRoutes: RouteRecordRaw[] = [
  {
    path: 'reference',
    name: 'reference',
    component: () => import('../pages/ReferenceDashboardPage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
  {
    path: 'reference/users',
    name: 'reference-users',
    component: () => import('../pages/UsersExamplePage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
  {
    path: 'reference/users/:id',
    name: 'reference-user-detail',
    component: () => import('../pages/UserDetailPage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
  {
    path: 'reference/approvals',
    name: 'reference-approvals',
    component: () => import('../pages/ApprovalWorkflowPage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
  {
    path: 'reference/approvals/:id',
    name: 'reference-approval-detail',
    component: () => import('../pages/ApprovalDetailPage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
  {
    path: 'reference/upload',
    name: 'reference-upload',
    component: () => import('../pages/UploadExamplePage.vue'),
    meta: { requiresAuth: true, requiresTenant: true },
  },
]
