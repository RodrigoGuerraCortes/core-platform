import { VueQueryPlugin, QueryClient } from '@tanstack/vue-query'
import type { App } from 'vue'

/**
 * A single QueryClient instance shared across the app.
 * Exported for use in tests and in components that need direct cache access.
 */
export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      // Data is considered fresh for 30 s before a background refetch.
      staleTime: 30_000,
      // One automatic retry on transient errors.
      retry: 1,
      // Don't spam the API when the user tabs back in.
      refetchOnWindowFocus: false,
    },
    mutations: {
      // Mutations do not retry by default — side-effects must be idempotent
      // for retries to be safe.
      retry: 0,
    },
  },
})

export function installQuery(app: App): void {
  app.use(VueQueryPlugin, { queryClient })
}
