/**
 * Reference module — TanStack Query composables.
 *
 * Each composable encapsulates one server resource.
 * They demonstrate the OFFICIAL platform query/mutation patterns:
 *
 *   useQuery  — read, with cache key aligned to useTableState.queryParams
 *   useMutation — write, with cache invalidation on success
 *
 * Cache key namespacing: ['reference', resource, ...params]
 *
 * These composables are the canonical template for any new module.
 */

import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { toValue, type MaybeRef } from 'vue'
import type { TableQueryParams } from '@/shared/table'
import type { ApprovalStatus, UserStatus, UploadItem } from '../types'
import {
  deleteReferenceUser,
  fetchReferenceApprovals,
  fetchReferenceUsers,
  updateApprovalStatus,
  updateReferenceUserStatus,
  fetchDashboardMetrics,
} from '../api'

// ─── Users ───────────────────────────────────────────────────────────────────

/**
 * Paginated + filtered user list.
 * queryParams is MaybeRef so it stays reactive when bound to useTableState().queryParams.
 */
export function useReferenceUsersQuery(
  queryParams: MaybeRef<Partial<TableQueryParams>> = {},
) {
  return useQuery({
    queryKey: ['reference', 'users', queryParams] as const,
    queryFn: () => fetchReferenceUsers(toValue(queryParams)),
    staleTime: 30_000,
  })
}

/**
 * Delete a user. Invalidates the user list on success.
 */
export function useDeleteUserMutation() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (userId: number) => deleteReferenceUser(userId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['reference', 'users'] })
      queryClient.invalidateQueries({ queryKey: ['reference', 'metrics'] })
    },
  })
}

/**
 * Update a user's status. Invalidates the user list on success.
 */
export function useUpdateUserStatusMutation() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ userId, status }: { userId: number; status: UserStatus }) =>
      updateReferenceUserStatus(userId, status),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['reference', 'users'] })
    },
  })
}

// ─── Approvals ───────────────────────────────────────────────────────────────

/**
 * Approval list — optionally filtered by status.
 */
export function useReferenceApprovalsQuery(
  status: MaybeRef<ApprovalStatus | '' | undefined> = '',
) {
  return useQuery({
    queryKey: ['reference', 'approvals', status] as const,
    queryFn: () => fetchReferenceApprovals(toValue(status) ?? ''),
    staleTime: 30_000,
  })
}

/**
 * Approve or reject an item (with optional comment).
 * Invalidates all approval queries.
 */
export function useUpdateApprovalStatusMutation() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({
      approvalId,
      status,
      comment,
    }: {
      approvalId: number
      status: ApprovalStatus
      comment?: string
    }) => updateApprovalStatus(approvalId, status, comment),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['reference', 'approvals'] })
      queryClient.invalidateQueries({ queryKey: ['reference', 'metrics'] })
    },
  })
}

// ─── Dashboard metrics ───────────────────────────────────────────────────────

/**
 * Dashboard summary metrics — counts and KPIs.
 */
export function useDashboardMetricsQuery() {
  return useQuery({
    queryKey: ['reference', 'metrics'] as const,
    queryFn: fetchDashboardMetrics,
    staleTime: 60_000,
  })
}

// ─── Upload manager ───────────────────────────────────────────────────────────

import { ref } from 'vue'

/**
 * useUploadManager — simulates multi-file upload with per-file progress.
 *
 * In production this would call an API; here it uses setTimeout to
 * demonstrate the UX state machine: pending → uploading → done/error.
 *
 * Demonstrates: local reactive state management without a server.
 */
export function useUploadManager() {
  const items = ref<UploadItem[]>([])
  let nextId = 1

  function _simulate(localId: string): void {
    const INTERVAL_MS = 100
    const TOTAL_STEPS = 20

    // ~30% chance of error for demo purposes
    const willError = Math.random() < 0.3

    let step = 0
    const timer = setInterval(() => {
      const item = items.value.find((i) => i.localId === localId)
      if (!item) { clearInterval(timer); return }

      step++
      const progress = Math.min(Math.round((step / TOTAL_STEPS) * 100), 100)

      if (willError && step === Math.floor(TOTAL_STEPS * 0.6)) {
        item.status = 'error'
        item.errorMessage = 'Network error — click Retry to try again'
        clearInterval(timer)
        return
      }

      item.progress = progress

      if (step >= TOTAL_STEPS) {
        item.status = 'done'
        item.serverUrl = `/uploads/${localId}/${item.file.name}`
        clearInterval(timer)
      }
    }, INTERVAL_MS)
  }

  function addFiles(files: File[]): void {
    for (const file of files) {
      const localId = `upload-${nextId++}`
      items.value.push({ localId, file, progress: 0, status: 'uploading' })
      _simulate(localId)
    }
  }

  function retry(localId: string): void {
    const item = items.value.find((i) => i.localId === localId)
    if (!item) return
    item.status = 'uploading'
    item.progress = 0
    item.errorMessage = undefined
    _simulate(localId)
  }

  function remove(localId: string): void {
    items.value = items.value.filter((i) => i.localId !== localId)
  }

  return { items, addFiles, retry, remove }
}
