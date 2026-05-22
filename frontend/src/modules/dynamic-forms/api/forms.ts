import apiClient from '@/shared/api/client'
import type { ApiResponse } from '@/shared/types'
import type { FormDetail, FormSubmissionDetail } from '../types'

/**
 * Fetch a form by ID.
 * The response includes the active_version with its full schema.
 */
export async function fetchForm(formId: number): Promise<FormDetail> {
  const { data } = await apiClient.get<ApiResponse<FormDetail>>(`/forms/${formId}`)
  return data.data
}

/**
 * Submit a payload against a form's active version.
 * Returns the created submission on success.
 * Throws on 422 (validation), 409 (duplicate), 410 (inactive form).
 */
export async function submitForm(
  formId: number,
  payload: Record<string, unknown>,
): Promise<FormSubmissionDetail> {
  const { data } = await apiClient.post<ApiResponse<FormSubmissionDetail>>(
    `/forms/${formId}/submit`,
    { payload },
  )
  return data.data
}
