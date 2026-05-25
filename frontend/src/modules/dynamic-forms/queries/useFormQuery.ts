import { useQuery } from '@tanstack/vue-query'
import { toValue, type MaybeRef } from 'vue'
import { fetchForm } from '../api/forms'

/**
 * Fetches a single form including its active version and schema.
 *
 * Accepts a plain number or a Ref/ComputedRef<number | null>.
 * toValue() is used in queryFn to unwrap the ref — passing the ref object
 * directly would produce "[object Object]" in the API URL.
 *
 * Cache key: ['forms', formId]
 * The query is disabled when formId is falsy (e.g., route param not yet resolved).
 */
export function useFormQuery(formId: MaybeRef<number | null>) {
  return useQuery({
    queryKey: ['forms', formId] as const,
    queryFn: () => fetchForm(toValue(formId)!),
    enabled: () => Boolean(toValue(formId)),
    staleTime: 60_000,
    retry: 1,
  })
}
