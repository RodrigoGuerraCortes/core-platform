import apiClient from '@/shared/api/client'
import type { ApiResponse, PaginatedResponse } from '@/shared/types'
import type { FormDetail, FormSchema, FormVersionDetail, FormSubmissionDetail } from '../types'

// ─── Forms ────────────────────────────────────────────────────────────────────

/** Fetch paginated list of forms for the current tenant. */
export async function fetchForms(page = 1): Promise<PaginatedResponse<FormDetail>> {
  const { data } = await apiClient.get<PaginatedResponse<FormDetail>>('/forms', {
    params: { page },
  })
  return data
}

/** Fetch a single form. Includes active_version when available. */
export async function fetchForm(formId: number): Promise<FormDetail> {
  const { data } = await apiClient.get<ApiResponse<FormDetail>>(`/forms/${formId}`)
  return data.data
}

/** Create a new form (draft status, no versions yet). */
export async function createForm(payload: {
  name: string
  description?: string | null
  slug?: string | null
}): Promise<FormDetail> {
  const { data } = await apiClient.post<ApiResponse<FormDetail>>('/forms', payload)
  return data.data
}

/** Update form metadata (name, description). Does not touch versions or schema. */
export async function updateForm(
  formId: number,
  payload: { name?: string; description?: string | null },
): Promise<FormDetail> {
  const { data } = await apiClient.patch<ApiResponse<FormDetail>>(`/forms/${formId}`, payload)
  return data.data
}

/**
 * Publish the latest version of a form.
 * The backend validates that at least one non-section field exists.
 * Throws 422 if the form has no versions or no renderable fields.
 */
export async function publishForm(formId: number): Promise<FormDetail> {
  const { data } = await apiClient.post<ApiResponse<FormDetail>>(`/forms/${formId}/publish`)
  return data.data
}

// ─── Form Versions ─────────────────────────────────────────────────────────────

/** Fetch all versions for a form (newest first). */
export async function fetchFormVersions(formId: number): Promise<PaginatedResponse<FormVersionDetail>> {
  const { data } = await apiClient.get<PaginatedResponse<FormVersionDetail>>(
    `/forms/${formId}/versions`,
  )
  return data
}

/**
 * Create a new draft version for a form.
 * FormVersions are immutable once created — edits always produce a new version.
 */
export async function createFormVersion(
  formId: number,
  schema: FormSchema,
  label?: string,
): Promise<FormVersionDetail> {
  const { data } = await apiClient.post<ApiResponse<FormVersionDetail>>(
    `/forms/${formId}/versions`,
    { schema, label: label ?? null },
  )
  return data.data
}

// ─── Submissions ────────────────────────────────────────────────────────────────

/** Submit a payload against a form's active version. */
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
