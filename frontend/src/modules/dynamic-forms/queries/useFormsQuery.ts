import { useQuery } from '@tanstack/vue-query'
import { toValue, type MaybeRef } from 'vue'
import { fetchForms } from '../api/forms'
import type { TableQueryParams } from '@/shared/table'

/**
 * Paginated + filtered list of forms for the current tenant.
 *
 * Accepts a MaybeRef<TableQueryParams> so it stays reactive when bound
 * to useTableState().queryParams.
 *
 * Cache key: ['forms', 'list', queryParams]
 */
export function useFormsQuery(queryParams: MaybeRef<TableQueryParams> = { page: 1, per_page: 15 }) {
  return useQuery({
    queryKey: ['forms', 'list', queryParams] as const,
    queryFn: () => fetchForms(toValue(queryParams)),
    staleTime: 30_000,
    retry: 1,
  })
}
