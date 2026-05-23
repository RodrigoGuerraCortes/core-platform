import { useQuery } from '@tanstack/vue-query'
import type { MaybeRef } from 'vue'
import { fetchFormVersions } from '../api/forms'

/**
 * Fetches all versions for a given form (newest first).
 * Cache key: ['forms', formId, 'versions']
 */
export function useFormVersionsQuery(formId: MaybeRef<number | null>) {
  return useQuery({
    queryKey: ['forms', formId, 'versions'] as const,
    queryFn: () => fetchFormVersions(formId as unknown as number),
    enabled: () => Boolean(formId),
    staleTime: 30_000,
    retry: 1,
  })
}
