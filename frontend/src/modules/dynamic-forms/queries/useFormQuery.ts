import { useQuery } from '@tanstack/vue-query'
import type { MaybeRef } from 'vue'
import { fetchForm } from '../api/forms'

/**
 * Fetches a single form including its active version and schema.
 *
 * Cache key: ['forms', formId]
 * The query is disabled when formId is falsy (e.g., route param not yet resolved).
 */
export function useFormQuery(formId: MaybeRef<number | null>) {
  return useQuery({
    queryKey: ['forms', formId] as const,
    queryFn: () => fetchForm(formId as unknown as number),
    enabled: () => Boolean(formId),
    staleTime: 60_000,
    retry: 1,
  })
}
