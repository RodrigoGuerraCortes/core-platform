/**
 * Reference module types.
 *
 * These are synthetic domain types used exclusively inside the Reference
 * module to demonstrate canonical platform patterns.
 *
 * They are NOT connected to real backend models. The Reference module uses
 * MSW to simulate realistic server responses.
 *
 * Naming follows the same conventions as real domain types:
 *   - PascalCase interfaces
 *   - Discriminated union for status fields
 *   - Aligned with PaginatedResponse<T> from shared/types
 */

// ─── Users ─────────────────────────────────────────────────────────────────

export type UserStatus = 'active' | 'inactive' | 'pending'
export type UserRole = 'owner' | 'admin' | 'member'

export interface ReferenceUser {
  id: number
  name: string
  email: string
  role: UserRole
  status: UserStatus
  joined_at: string  // ISO 8601
  last_active_at: string | null
}

// ─── Approvals ──────────────────────────────────────────────────────────────

export type ApprovalStatus = 'pending' | 'approved' | 'rejected'

export interface ApprovalComment {
  id: number
  author: string
  body: string
  created_at: string
}

export interface ReferenceApproval {
  id: number
  title: string
  description: string
  requester: string
  status: ApprovalStatus
  submitted_at: string
  resolved_at: string | null
  comments: ApprovalComment[]
}

// ─── Uploads ────────────────────────────────────────────────────────────────

export type UploadStatus = 'idle' | 'uploading' | 'done' | 'error'

export interface UploadItem {
  /** Browser-generated ID — not a server ID. */
  localId: string
  file: File
  progress: number        // 0–100
  status: UploadStatus
  errorMessage?: string
  serverUrl?: string      // populated when status === 'done'
}

// ─── Dashboard metrics ───────────────────────────────────────────────────────

export interface MetricCard {
  label: string
  value: number | string
  unit?: string
  trend?: 'up' | 'down' | 'flat'
  trendValue?: string
  icon: string
  color: string
}
