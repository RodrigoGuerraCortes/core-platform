import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { updateForm } from '../api/forms'

/**
 * Updates form metadata (name, description).
 * On success, invalidates the specific form cache and the list.
 */
export function useUpdateFormMutation(formId: number) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: { name?: string; description?: string | null }) =>
      updateForm(formId, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['forms', formId] })
      queryClient.invalidateQueries({ queryKey: ['forms', 'list'] })
    },
  })
}
