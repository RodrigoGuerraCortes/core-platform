import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { toValue, type MaybeRef } from 'vue'
import { createFormVersion } from '../api/forms'
import type { FormSchema } from '../types'

/**
 * Creates a new draft version for a form.
 * FormVersions are immutable — every save produces a new version record.
 * On success, invalidates the versions cache and the form cache (version_number changes).
 *
 * Accepts MaybeRef<number> so the caller can pass a ComputedRef from the route
 * rather than snapshotting formId.value at setup time.
 */
export function useCreateVersionMutation(formId: MaybeRef<number>) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: { schema: FormSchema; label?: string }) =>
      createFormVersion(toValue(formId), payload.schema, payload.label),
    onSuccess: () => {
      const id = toValue(formId)
      queryClient.invalidateQueries({ queryKey: ['forms', id, 'versions'] })
      queryClient.invalidateQueries({ queryKey: ['forms', id] })
    },
  })
}
