import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { createForm } from '../api/forms'

/**
 * Creates a new form (draft status, no versions).
 * On success, invalidates the forms list cache.
 */
export function useCreateFormMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: createForm,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['forms', 'list'] })
    },
  })
}
