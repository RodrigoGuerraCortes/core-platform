/**
 * Reference module — composable integration tests.
 *
 * Each test exercises the full query → MSW handler → response pipeline.
 * Pattern mirrors the dynamic-forms query tests.
 *
 * Demonstrates the canonical composable test structure:
 *   1. Wrap in VueQueryWrapper (QueryClient provider)
 *   2. Mount with useQuery/useMutation composable
 *   3. waitForLoading → assert results
 *   4. Assert cache invalidation on mutation success
 */
import { describe, it, expect, beforeEach } from 'vitest'
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query'
import { defineComponent, ref } from 'vue'
import { mount } from '@vue/test-utils'
import { resetReferenceHandlerState } from '../mocks/handlers'
import {
  useReferenceUsersQuery,
  useDeleteUserMutation,
  useReferenceApprovalsQuery,
  useUpdateApprovalStatusMutation,
} from '../composables'

// ── Test helpers ──────────────────────────────────────────────────────────────

function makeQueryClient() {
  return new QueryClient({ defaultOptions: { queries: { retry: false } } })
}

function mountWithQuery<T>(setup: () => T) {
  const queryClient = makeQueryClient()
  const Wrapper = defineComponent({ setup, template: '<div />' })
  const wrapper = mount(Wrapper, {
    global: { plugins: [[VueQueryPlugin, { queryClient }]] },
  })
  return { wrapper, queryClient }
}

async function waitForLoading(queryClient: QueryClient, timeout = 2000) {
  const start = Date.now()
  while (queryClient.isFetching() && Date.now() - start < timeout) {
    await new Promise((r) => setTimeout(r, 20))
  }
}

// ── Reset fixture state between tests ─────────────────────────────────────────

beforeEach(() => {
  resetReferenceHandlerState()
})

// ── useReferenceUsersQuery ────────────────────────────────────────────────────

describe('useReferenceUsersQuery', () => {
  it('returns paginated user list from MSW', async () => {
    let result: ReturnType<typeof useReferenceUsersQuery>

    const { queryClient } = mountWithQuery(() => {
      result = useReferenceUsersQuery()
      return result
    })

    await waitForLoading(queryClient)

    expect(result!.data.value).toBeDefined()
    expect(result!.data.value!.data.length).toBeGreaterThan(0)
    expect(result!.data.value!.meta).toMatchObject({
      current_page: 1,
      per_page: expect.any(Number),
      total: expect.any(Number),
    })
  })

  it('filters by status when params include status', async () => {
    const params = ref({ page: 1, per_page: 15, status: 'active' })
    let result: ReturnType<typeof useReferenceUsersQuery>

    const { queryClient } = mountWithQuery(() => {
      result = useReferenceUsersQuery(params)
      return result
    })

    await waitForLoading(queryClient)

    const users = result!.data.value?.data ?? []
    expect(users.every((u) => u.status === 'active')).toBe(true)
  })

  it('filters by search term', async () => {
    const params = ref({ page: 1, per_page: 15, search: 'alice' })
    let result: ReturnType<typeof useReferenceUsersQuery>

    const { queryClient } = mountWithQuery(() => {
      result = useReferenceUsersQuery(params)
      return result
    })

    await waitForLoading(queryClient)

    const users = result!.data.value?.data ?? []
    expect(users.length).toBeGreaterThan(0)
    users.forEach((u) => {
      const haystack = `${u.name} ${u.email}`.toLowerCase()
      expect(haystack).toContain('alice')
    })
  })

  it('respects per_page param', async () => {
    const params = ref({ page: 1, per_page: 5 })
    let result: ReturnType<typeof useReferenceUsersQuery>

    const { queryClient } = mountWithQuery(() => {
      result = useReferenceUsersQuery(params)
      return result
    })

    await waitForLoading(queryClient)

    expect(result!.data.value?.data.length).toBeLessThanOrEqual(5)
  })
})

// ── useDeleteUserMutation ─────────────────────────────────────────────────────

describe('useDeleteUserMutation', () => {
  it('removes a user and invalidates the user query cache', async () => {
    // First load users to populate cache
    let users: ReturnType<typeof useReferenceUsersQuery>
    let del: ReturnType<typeof useDeleteUserMutation>

    const { queryClient } = mountWithQuery(() => {
      users = useReferenceUsersQuery()
      del = useDeleteUserMutation()
      return { users, del }
    })

    await waitForLoading(queryClient)

    const firstUser = users!.data.value!.data[0]
    expect(firstUser).toBeDefined()

    await del!.mutateAsync(firstUser.id)

    // After deletion the cache for ['reference', 'users'] should be invalidated.
    // getQueryCache().findAll() matches on partial key prefix.
    const staleQueries = queryClient
      .getQueryCache()
      .findAll({ queryKey: ['reference', 'users'], exact: false })
      .filter((q) => q.state.isInvalidated)
    expect(staleQueries.length).toBeGreaterThan(0)
  })
})

// ── useReferenceApprovalsQuery ────────────────────────────────────────────────

describe('useReferenceApprovalsQuery', () => {
  it('returns all approvals with no filter', async () => {
    let result: ReturnType<typeof useReferenceApprovalsQuery>

    const { queryClient } = mountWithQuery(() => {
      result = useReferenceApprovalsQuery('')
      return result
    })

    await waitForLoading(queryClient)

    expect(result!.data.value).toBeInstanceOf(Array)
    expect(result!.data.value!.length).toBeGreaterThan(0)
  })

  it('returns only pending approvals when filtered', async () => {
    const status = ref<'pending' | ''>('pending')
    let result: ReturnType<typeof useReferenceApprovalsQuery>

    const { queryClient } = mountWithQuery(() => {
      result = useReferenceApprovalsQuery(status)
      return result
    })

    await waitForLoading(queryClient)

    const items = result!.data.value ?? []
    expect(items.length).toBeGreaterThan(0)
    expect(items.every((a) => a.status === 'pending')).toBe(true)
  })
})

// ── useUpdateApprovalStatusMutation ──────────────────────────────────────────

describe('useUpdateApprovalStatusMutation', () => {
  it('approves a pending item and invalidates approval cache', async () => {
    let approvals: ReturnType<typeof useReferenceApprovalsQuery>
    let update: ReturnType<typeof useUpdateApprovalStatusMutation>

    const { queryClient } = mountWithQuery(() => {
      approvals = useReferenceApprovalsQuery('')
      update = useUpdateApprovalStatusMutation()
      return { approvals, update }
    })

    await waitForLoading(queryClient)

    const pending = approvals!.data.value!.find((a) => a.status === 'pending')
    expect(pending).toBeDefined()

    const updated = await update!.mutateAsync({
      approvalId: pending!.id,
      status: 'approved',
      comment: 'LGTM',
    })

    expect(updated.status).toBe('approved')
    expect(updated.comments.at(-1)?.body).toBe('LGTM')

    // Approval cache invalidated
    const staleQueries = queryClient
      .getQueryCache()
      .findAll({ queryKey: ['reference', 'approvals'], exact: false })
      .filter((q) => q.state.isInvalidated)
    expect(staleQueries.length).toBeGreaterThan(0)
  })

  it('rejects a pending item with a comment', async () => {
    let approvals: ReturnType<typeof useReferenceApprovalsQuery>
    let update: ReturnType<typeof useUpdateApprovalStatusMutation>

    const { queryClient } = mountWithQuery(() => {
      approvals = useReferenceApprovalsQuery('')
      update = useUpdateApprovalStatusMutation()
      return { approvals, update }
    })

    await waitForLoading(queryClient)

    const pending = approvals!.data.value!.find((a) => a.status === 'pending')
    expect(pending).toBeDefined()

    const updated = await update!.mutateAsync({
      approvalId: pending!.id,
      status: 'rejected',
      comment: 'Needs more info',
    })

    expect(updated.status).toBe('rejected')
    expect(updated.resolved_at).not.toBeNull()
  })
})
