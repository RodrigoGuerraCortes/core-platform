import { useQuery } from '@tanstack/vue-query'
import type { MaybeRef } from 'vue'
import { fetchForms } from '../api/forms'

/**
 * Paginated list of forms for the current tenant.
 * Cache key: ['forms', 'list', page]
 */
export function useFormsQuery(page: MaybeRef<number> = 1) {
  return useQuery({
    queryKey: ['forms', 'list', page] as const,
    queryFn: () => fetchForms(page as unknown as number),
    staleTime: 30_000,
    retry: 1,
  })
}
