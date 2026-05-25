/**
 * CondoFlow module — composable integration tests.
 *
 * Exercises the query → MSW handler → response pipeline for all CondoFlow
 * composables. Validates that TanStack Query properly caches and returns data.
 */
import { describe, it, expect } from 'vitest'
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query'
import { defineComponent } from 'vue'
import { mount } from '@vue/test-utils'
import {
  useBuildingsQuery,
  useUnitsQuery,
  useResidentsQuery,
  useTicketsQuery,
  useTicketDetailQuery,
  useCondoDashboardQuery,
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

// ── Buildings ─────────────────────────────────────────────────────────────────

describe('useBuildingsQuery', () => {
  it('fetches buildings list', async () => {
    const { queryClient } = mountWithQuery(() => {
      const result = useBuildingsQuery()
      return { result }
    })
    await waitForLoading(queryClient)
    const cache = queryClient.getQueryData(['condoflow', 'buildings', { page: 1, per_page: 15 }]) as any
    expect(cache).toBeDefined()
    expect(cache.data.length).toBeGreaterThan(0)
    expect(cache.data[0]).toHaveProperty('name')
  })
})

// ── Units ─────────────────────────────────────────────────────────────────────

describe('useUnitsQuery', () => {
  it('fetches units list', async () => {
    const { queryClient } = mountWithQuery(() => {
      const result = useUnitsQuery()
      return { result }
    })
    await waitForLoading(queryClient)
    const cache = queryClient.getQueryData(['condoflow', 'units', { page: 1, per_page: 15 }]) as any
    expect(cache).toBeDefined()
    expect(cache.data.length).toBeGreaterThan(0)
    expect(cache.data[0]).toHaveProperty('number')
  })
})

// ── Residents ─────────────────────────────────────────────────────────────────

describe('useResidentsQuery', () => {
  it('fetches residents list', async () => {
    const { queryClient } = mountWithQuery(() => {
      const result = useResidentsQuery()
      return { result }
    })
    await waitForLoading(queryClient)
    const cache = queryClient.getQueryData(['condoflow', 'residents', { page: 1, per_page: 15 }]) as any
    expect(cache).toBeDefined()
    expect(cache.data.length).toBeGreaterThan(0)
    expect(cache.data[0]).toHaveProperty('name')
  })
})

// ── Tickets ───────────────────────────────────────────────────────────────────

describe('useTicketsQuery', () => {
  it('fetches tickets list', async () => {
    const { queryClient } = mountWithQuery(() => {
      const result = useTicketsQuery()
      return { result }
    })
    await waitForLoading(queryClient)
    const cache = queryClient.getQueryData(['condoflow', 'tickets', { page: 1, per_page: 15 }]) as any
    expect(cache).toBeDefined()
    expect(cache.data.length).toBeGreaterThan(0)
    expect(cache.data[0]).toHaveProperty('title')
  })
})

describe('useTicketDetailQuery', () => {
  it('fetches a single ticket', async () => {
    const { queryClient } = mountWithQuery(() => {
      const result = useTicketDetailQuery(1)
      return { result }
    })
    await waitForLoading(queryClient)
    const cache = queryClient.getQueryData(['condoflow', 'tickets', 1]) as any
    expect(cache).toBeDefined()
    expect(cache.data.title).toBe('Fuga de agua en baño')
  })
})

// ── Dashboard ─────────────────────────────────────────────────────────────────

describe('useCondoDashboardQuery', () => {
  it('fetches dashboard data', async () => {
    const { queryClient } = mountWithQuery(() => {
      const result = useCondoDashboardQuery()
      return { result }
    })
    await waitForLoading(queryClient)
    const cache = queryClient.getQueryData(['condoflow', 'dashboard']) as any
    expect(cache).toBeDefined()
    expect(cache.data).toHaveProperty('buildings_count')
    expect(cache.data).toHaveProperty('open_tickets_count')
    expect(cache.data).toHaveProperty('tickets_by_priority')
  })
})
