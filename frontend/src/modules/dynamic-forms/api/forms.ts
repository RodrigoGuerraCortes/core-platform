import apiClient from '@/shared/api/client'
import type { ApiResponse, PaginatedResponse } from '@/shared/types'
import type { FormDetail, FormSchema, FormVersionDetail, FormSubmissionDetail } from '../types'

// ─── Guards ───────────────────────────────────────────────────────────────────

/**
 * Asserts that a value intended to be a form ID is a finite number.
 * Throws at runtime if a Ref object or other non-numeric value slips through.
 * This guard exists because queryFn receives MaybeRef arguments — forgetting
 * toValue() would silently serialise the ref as "[object Object]" in the URL.
 */
function assertNumericId(id: unknown, label = 'formId'): asserts id is number {
  if (typeof id !== 'number' || !Number.isFinite(id)) {
    throw new TypeError(
      `[forms API] ${label} must be a finite number, got: ${JSON.stringify(id)} (${typeof id}). ` +
        `Did you forget toValue() when unwrapping a MaybeRef?`,
    )
  }
}

// ─── Forms ────────────────────────────────────────────────────────────────────

/** Fetch paginated list of forms for the current tenant. */
export async function fetchForms(params: {
  page?: number
  per_page?: number
  search?: string | null
  status?: string | null
  sort_by?: string
  sort_dir?: 'asc' | 'desc'
} = {}): Promise<PaginatedResponse<FormDetail>> {
  const { data } = await apiClient.get<PaginatedResponse<FormDetail>>('/forms', {
    params: Object.fromEntries(
      Object.entries(params).filter(([, v]) => v !== null && v !== undefined && v !== ''),
    ),
  })
  return data
}

/** Fetch a single form. Includes active_version when available. */
export async function fetchForm(formId: number): Promise<FormDetail> {
  assertNumericId(formId)
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
  assertNumericId(formId)
  const { data } = await apiClient.patch<ApiResponse<FormDetail>>(`/forms/${formId}`, payload)
  return data.data
}

/**
 * Publish the latest version of a form.
 * The backend validates that at least one non-section field exists.
 * Throws 422 if the form has no versions or no renderable fields.
 */
export async function publishForm(formId: number): Promise<FormDetail> {
  assertNumericId(formId)
  const { data } = await apiClient.post<ApiResponse<FormDetail>>(`/forms/${formId}/publish`)
  return data.data
}

// ─── Form Versions ─────────────────────────────────────────────────────────────

/** Fetch all versions for a form (newest first). */
export async function fetchFormVersions(formId: number): Promise<PaginatedResponse<FormVersionDetail>> {
  assertNumericId(formId)
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
  assertNumericId(formId)
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
  assertNumericId(formId)
  const { data } = await apiClient.post<ApiResponse<FormSubmissionDetail>>(
    `/forms/${formId}/submit`,
    { payload },
  )
  return data.data
}
