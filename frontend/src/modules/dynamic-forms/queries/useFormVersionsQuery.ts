import { useQuery } from '@tanstack/vue-query'
import { toValue, type MaybeRef } from 'vue'
import { fetchFormVersions } from '../api/forms'

/**
 * Fetches all versions for a given form (newest first).
 *
 * Accepts a plain number or a Ref/ComputedRef<number | null>.
 * toValue() unwraps the ref in queryFn — never pass the ref object itself
 * into an API call or it will be serialised as "[object Object]".
 *
 * Cache key: ['forms', formId, 'versions']
 */
export function useFormVersionsQuery(formId: MaybeRef<number | null>) {
  return useQuery({
    queryKey: ['forms', formId, 'versions'] as const,
    queryFn: () => fetchFormVersions(toValue(formId)!),
    enabled: () => Boolean(toValue(formId)),
    staleTime: 30_000,
    retry: 1,
  })
}
