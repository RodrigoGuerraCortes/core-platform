import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { createFormVersion } from '../api/forms'
import type { FormSchema } from '../types'

/**
 * Creates a new draft version for a form.
 * FormVersions are immutable — every save produces a new version record.
 * On success, invalidates the versions cache and the form cache (version_number changes).
 */
export function useCreateVersionMutation(formId: number) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: { schema: FormSchema; label?: string }) =>
      createFormVersion(formId, payload.schema, payload.label),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['forms', formId, 'versions'] })
      queryClient.invalidateQueries({ queryKey: ['forms', formId] })
    },
  })
}
