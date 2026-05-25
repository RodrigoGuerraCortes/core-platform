/**
 * Reference module — MSW request handlers.
 *
 * Simulates a realistic REST API for the Reference module examples.
 * Handlers are stateful within a test run (mutations mutate a local copy).
 *
 * Endpoints:
 *   GET    /api/reference/users           — paginated + filterable list
 *   DELETE /api/reference/users/:id       — delete a user
 *   PATCH  /api/reference/users/:id       — update status
 *   GET    /api/reference/approvals       — list with status filter
 *   PATCH  /api/reference/approvals/:id   — update approval status + add comment
 *   GET    /api/reference/metrics         — dashboard metrics
 */

import { http, HttpResponse } from 'msw'
import { REFERENCE_USERS, REFERENCE_APPROVALS } from '../data/fixtures'
import type { ReferenceUser, ReferenceApproval, ApprovalStatus } from '../types'

// ─── Mutable local state (per server lifecycle) ──────────────────────────────
let users: ReferenceUser[] = [...REFERENCE_USERS]
let approvals: ReferenceApproval[] = [...REFERENCE_APPROVALS]

/** Reset state — call from beforeEach in tests. */
export function resetReferenceHandlerState(): void {
  users = [...REFERENCE_USERS]
  approvals = [...REFERENCE_APPROVALS]
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

function paginate<T>(items: T[], page: number, perPage: number) {
  const total = items.length
  const from = (page - 1) * perPage
  const data = items.slice(from, from + perPage)
  return {
    data,
    meta: {
      current_page: page,
      last_page: Math.ceil(total / perPage),
      per_page: perPage,
      total,
      from: data.length ? from + 1 : null,
      to: data.length ? from + data.length : null,
    },
    links: { first: '', last: '', prev: null, next: null },
  }
}

// ─── User handlers ───────────────────────────────────────────────────────────

const userHandlers = [
  http.get('/api/reference/users', ({ request }) => {
    const url = new URL(request.url)
    const page    = Number(url.searchParams.get('page')     ?? 1)
    const perPage = Number(url.searchParams.get('per_page') ?? 15)
    const search  = url.searchParams.get('search')?.toLowerCase() ?? ''
    const status  = url.searchParams.get('status') ?? ''
    const sortBy  = url.searchParams.get('sort_by') ?? ''
    const sortDir = (url.searchParams.get('sort_dir') ?? 'asc') as 'asc' | 'desc'

    let filtered = users

    if (search) {
      filtered = filtered.filter(
        (u) => u.name.toLowerCase().includes(search) || u.email.toLowerCase().includes(search),
      )
    }

    if (status) {
      filtered = filtered.filter((u) => u.status === status)
    }

    if (sortBy) {
      filtered = [...filtered].sort((a, b) => {
        const av = String(a[sortBy as keyof ReferenceUser] ?? '')
        const bv = String(b[sortBy as keyof ReferenceUser] ?? '')
        return sortDir === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av)
      })
    }

    return HttpResponse.json(paginate(filtered, page, perPage))
  }),

  http.delete('/api/reference/users/:id', ({ params }) => {
    const id = Number(params.id)
    const idx = users.findIndex((u) => u.id === id)
    if (idx === -1) return HttpResponse.json({ message: 'Not found' }, { status: 404 })
    users = users.filter((u) => u.id !== id)
    return new HttpResponse(null, { status: 204 })
  }),

  http.patch('/api/reference/users/:id', async ({ params, request }) => {
    const id = Number(params.id)
    const body = (await request.json()) as Partial<ReferenceUser>
    const idx = users.findIndex((u) => u.id === id)
    if (idx === -1) return HttpResponse.json({ message: 'Not found' }, { status: 404 })
    users[idx] = { ...users[idx], ...body }
    return HttpResponse.json({ data: users[idx] })
  }),
]

// ─── Approval handlers ───────────────────────────────────────────────────────

const approvalHandlers = [
  http.get('/api/reference/approvals', ({ request }) => {
    const url    = new URL(request.url)
    const status = url.searchParams.get('status') ?? ''

    const filtered = status
      ? approvals.filter((a) => a.status === status)
      : approvals

    return HttpResponse.json({ data: filtered })
  }),

  http.patch('/api/reference/approvals/:id', async ({ params, request }) => {
    const id   = Number(params.id)
    const body = (await request.json()) as { status: ApprovalStatus; comment?: string }
    const idx  = approvals.findIndex((a) => a.id === id)
    if (idx === -1) return HttpResponse.json({ message: 'Not found' }, { status: 404 })

    approvals[idx] = {
      ...approvals[idx],
      status: body.status,
      resolved_at: body.status !== 'pending' ? new Date().toISOString() : null,
      comments: body.comment
        ? [
            ...approvals[idx].comments,
            {
              id: Date.now(),
              author: 'You',
              body: body.comment,
              created_at: new Date().toISOString(),
            },
          ]
        : approvals[idx].comments,
    }

    return HttpResponse.json({ data: approvals[idx] })
  }),
]

// ─── Metrics handler ─────────────────────────────────────────────────────────

const metricsHandlers = [
  http.get('/api/reference/metrics', () => {
    return HttpResponse.json({
      data: {
        total_users:       users.length,
        active_users:      users.filter((u) => u.status === 'active').length,
        pending_approvals: approvals.filter((a) => a.status === 'pending').length,
        forms_published:   4,
      },
    })
  }),
]

export const referenceHandlers = [...userHandlers, ...approvalHandlers, ...metricsHandlers]
