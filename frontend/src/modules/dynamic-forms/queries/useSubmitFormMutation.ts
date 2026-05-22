import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { submitForm } from '../api/forms'

/**
 * TanStack Query mutation for submitting a form payload.
 *
 * On success, invalidates the form query so the form's submission count
 * reflects the new state if the UI displays it.
 *
 * Error handling (422, 409, 410) is performed by the caller — the mutation
 * itself just propagates the error so the renderer can map it.
 */
export function useSubmitFormMutation(formId: number) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: Record<string, unknown>) => submitForm(formId, payload),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['forms', formId] })
    },
  })
}
