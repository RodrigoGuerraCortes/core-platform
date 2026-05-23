import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { publishForm } from '../api/forms'

/**
 * Publishes the latest version of a form.
 * The backend selects the most recent version, marks it published,
 * and sets it as the form's active_version.
 *
 * On success, invalidates the form cache and the list (status changes draft→active).
 * Throws 422 if the form has no versions or no renderable fields.
 */
export function usePublishFormMutation(formId: number) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: () => publishForm(formId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['forms', formId] })
      queryClient.invalidateQueries({ queryKey: ['forms', formId, 'versions'] })
      queryClient.invalidateQueries({ queryKey: ['forms', 'list'] })
    },
  })
}
