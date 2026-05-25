/**
 * Reference module — API layer.
 *
 * Thin wrappers over apiClient. All functions accept typed params and return
 * typed responses aligned with PaginatedResponse<T> / ApiResponse<T>.
 *
 * In production these would point to real backend endpoints.
 * In the Reference module they are intercepted by MSW.
 */

import apiClient from '@/shared/api/client'
import type { PaginatedResponse, ApiResponse } from '@/shared/types'
import type { ReferenceUser, ReferenceApproval, ApprovalStatus, UserStatus } from '../types'
import type { TableQueryParams } from '@/shared/table'

// ─── Users ───────────────────────────────────────────────────────────────────

export async function fetchReferenceUsers(
  params: Partial<TableQueryParams> = {},
): Promise<PaginatedResponse<ReferenceUser>> {
  const { data } = await apiClient.get<PaginatedResponse<ReferenceUser>>(
    '/reference/users',
    {
      params: Object.fromEntries(
        Object.entries(params).filter(([, v]) => v !== null && v !== undefined && v !== ''),
      ),
    },
  )
  return data
}

export async function deleteReferenceUser(userId: number): Promise<void> {
  await apiClient.delete(`/reference/users/${userId}`)
}

export async function updateReferenceUserStatus(
  userId: number,
  status: UserStatus,
): Promise<ReferenceUser> {
  const { data } = await apiClient.patch<ApiResponse<ReferenceUser>>(
    `/reference/users/${userId}`,
    { status },
  )
  return data.data
}

// ─── Approvals ───────────────────────────────────────────────────────────────

export async function fetchReferenceApprovals(
  status?: ApprovalStatus | '',
): Promise<ReferenceApproval[]> {
  const { data } = await apiClient.get<{ data: ReferenceApproval[] }>(
    '/reference/approvals',
    { params: status ? { status } : {} },
  )
  return data.data
}

export async function updateApprovalStatus(
  approvalId: number,
  status: ApprovalStatus,
  comment?: string,
): Promise<ReferenceApproval> {
  const { data } = await apiClient.patch<ApiResponse<ReferenceApproval>>(
    `/reference/approvals/${approvalId}`,
    { status, comment },
  )
  return data.data
}

// ─── Metrics ─────────────────────────────────────────────────────────────────

export interface DashboardMetrics {
  total_users: number
  active_users: number
  pending_approvals: number
  forms_published: number
}

export async function fetchDashboardMetrics(): Promise<DashboardMetrics> {
  const { data } = await apiClient.get<ApiResponse<DashboardMetrics>>('/reference/metrics')
  return data.data
}
