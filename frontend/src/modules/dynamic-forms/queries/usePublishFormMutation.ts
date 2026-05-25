import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { toValue, type MaybeRef } from 'vue'
import { publishForm } from '../api/forms'

/**
 * Publishes the latest version of a form.
 * The backend selects the most recent version, marks it published,
 * and sets it as the form's active_version.
 *
 * On success, invalidates the form cache and the list (status changes draft→active).
 * Throws 422 if the form has no versions or no renderable fields.
 *
 * Accepts MaybeRef<number> so the caller can pass a ComputedRef from the route
 * rather than snapshotting formId.value at setup time.
 */
export function usePublishFormMutation(formId: MaybeRef<number>) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: () => publishForm(toValue(formId)),
    onSuccess: () => {
      const id = toValue(formId)
      queryClient.invalidateQueries({ queryKey: ['forms', id] })
      queryClient.invalidateQueries({ queryKey: ['forms', id, 'versions'] })
      queryClient.invalidateQueries({ queryKey: ['forms', 'list'] })
    },
  })
}
