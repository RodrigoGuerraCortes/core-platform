/**
 * Reference module — static fixture data.
 *
 * Used by MSW handlers to simulate realistic server responses without a
 * real backend. Fixtures intentionally cover edge cases:
 *   - All status variants
 *   - Various role combinations
 *   - Null fields (last_active_at = null for pending users)
 *   - Pagination-realistic volumes (20+ records)
 */

import type { ReferenceUser, ReferenceApproval } from '../types'

// ─── Users ──────────────────────────────────────────────────────────────────

export const REFERENCE_USERS: ReferenceUser[] = [
  { id: 1,  name: 'Alice Merchant',   email: 'alice@acme.com',   role: 'owner',  status: 'active',   joined_at: '2024-01-10T09:00:00Z', last_active_at: '2026-05-24T08:00:00Z' },
  { id: 2,  name: 'Bob Hensley',      email: 'bob@acme.com',     role: 'admin',  status: 'active',   joined_at: '2024-02-14T10:00:00Z', last_active_at: '2026-05-23T17:30:00Z' },
  { id: 3,  name: 'Carol Finch',      email: 'carol@acme.com',   role: 'member', status: 'active',   joined_at: '2024-03-01T08:30:00Z', last_active_at: '2026-05-22T12:00:00Z' },
  { id: 4,  name: 'David Ortega',     email: 'david@acme.com',   role: 'member', status: 'inactive', joined_at: '2024-03-15T14:00:00Z', last_active_at: '2025-12-01T09:00:00Z' },
  { id: 5,  name: 'Eve Nakamura',     email: 'eve@acme.com',     role: 'member', status: 'pending',  joined_at: '2026-05-20T11:00:00Z', last_active_at: null },
  { id: 6,  name: 'Frank Delacroix',  email: 'frank@acme.com',   role: 'admin',  status: 'active',   joined_at: '2024-04-10T09:00:00Z', last_active_at: '2026-05-24T07:45:00Z' },
  { id: 7,  name: 'Grace Okonkwo',    email: 'grace@acme.com',   role: 'member', status: 'active',   joined_at: '2024-05-01T10:00:00Z', last_active_at: '2026-05-21T16:00:00Z' },
  { id: 8,  name: 'Hiro Tanaka',      email: 'hiro@acme.com',    role: 'member', status: 'inactive', joined_at: '2024-06-20T09:00:00Z', last_active_at: '2026-01-10T10:00:00Z' },
  { id: 9,  name: 'Isabel Ferreira',  email: 'isabel@acme.com',  role: 'member', status: 'pending',  joined_at: '2026-05-23T14:00:00Z', last_active_at: null },
  { id: 10, name: 'James Whitfield',  email: 'james@acme.com',   role: 'member', status: 'active',   joined_at: '2024-07-08T11:00:00Z', last_active_at: '2026-05-20T09:30:00Z' },
  { id: 11, name: 'Kai Andersen',     email: 'kai@acme.com',     role: 'member', status: 'active',   joined_at: '2024-08-01T10:00:00Z', last_active_at: '2026-05-18T14:00:00Z' },
  { id: 12, name: 'Laura Spence',     email: 'laura@acme.com',   role: 'admin',  status: 'active',   joined_at: '2024-09-15T09:00:00Z', last_active_at: '2026-05-24T09:00:00Z' },
  { id: 13, name: 'Marco Ricci',      email: 'marco@acme.com',   role: 'member', status: 'inactive', joined_at: '2024-10-01T10:00:00Z', last_active_at: '2025-11-15T10:00:00Z' },
  { id: 14, name: 'Nina Kowalski',    email: 'nina@acme.com',    role: 'member', status: 'active',   joined_at: '2024-11-20T11:00:00Z', last_active_at: '2026-05-19T13:00:00Z' },
  { id: 15, name: 'Omar Hassan',      email: 'omar@acme.com',    role: 'member', status: 'pending',  joined_at: '2026-05-22T08:00:00Z', last_active_at: null },
]

// ─── Approvals ──────────────────────────────────────────────────────────────

export const REFERENCE_APPROVALS: ReferenceApproval[] = [
  {
    id: 1,
    title: 'Budget increase for Q3 marketing',
    description: 'Request to increase the Q3 marketing budget by $12,000 to cover additional digital campaigns.',
    requester: 'Alice Merchant',
    status: 'pending',
    submitted_at: '2026-05-23T09:00:00Z',
    resolved_at: null,
    comments: [
      { id: 1, author: 'Bob Hensley',  body: 'Looks reasonable. Needs finance sign-off first.', created_at: '2026-05-23T10:00:00Z' },
    ],
  },
  {
    id: 2,
    title: 'New vendor onboarding — CloudOps Ltd',
    description: 'Onboard CloudOps Ltd as an approved infrastructure vendor for CI/CD tooling.',
    requester: 'Frank Delacroix',
    status: 'approved',
    submitted_at: '2026-05-15T11:00:00Z',
    resolved_at: '2026-05-17T14:00:00Z',
    comments: [
      { id: 2, author: 'Laura Spence', body: 'Vendor check passed. Approved.', created_at: '2026-05-17T14:00:00Z' },
    ],
  },
  {
    id: 3,
    title: 'Access request — production read-only credentials',
    description: 'Developer access to production read-only database for incident debugging.',
    requester: 'Carol Finch',
    status: 'rejected',
    submitted_at: '2026-05-20T08:30:00Z',
    resolved_at: '2026-05-21T09:00:00Z',
    comments: [
      { id: 3, author: 'Bob Hensley',  body: 'Scope too broad. Use the analytics replica instead.', created_at: '2026-05-21T09:00:00Z' },
    ],
  },
  {
    id: 4,
    title: 'Workspace plan upgrade to Business Pro',
    description: 'Upgrade tenant subscription from Starter to Business Pro to unlock SSO and advanced reporting.',
    requester: 'Alice Merchant',
    status: 'pending',
    submitted_at: '2026-05-24T07:00:00Z',
    resolved_at: null,
    comments: [],
  },
  {
    id: 5,
    title: 'Remote work equipment reimbursement',
    description: 'Reimbursement of $840 for ergonomic home office equipment per remote work policy.',
    requester: 'Grace Okonkwo',
    status: 'approved',
    submitted_at: '2026-05-10T10:00:00Z',
    resolved_at: '2026-05-12T16:00:00Z',
    comments: [
      { id: 4, author: 'Laura Spence', body: 'Within policy limits. Approved.', created_at: '2026-05-12T16:00:00Z' },
    ],
  },
]
