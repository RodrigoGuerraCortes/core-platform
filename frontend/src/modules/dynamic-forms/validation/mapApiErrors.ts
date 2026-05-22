import axios from 'axios'
import type { FieldErrors } from './buildZodSchema'

/**
 * Extracts field-level errors from a Laravel 422 Unprocessable Content response.
 * Returns null if the error is not a 422 validation response.
 */
export function mapApiErrors(error: unknown): FieldErrors | null {
  if (!axios.isAxiosError(error) || error.response?.status !== 422) {
    return null
  }
  const apiErrors = error.response.data?.errors as Record<string, string[]> | undefined
  if (!apiErrors) return null

  return Object.fromEntries(
    Object.entries(apiErrors).map(([key, messages]) => [
      key,
      messages[0] ?? 'Invalid value',
    ]),
  )
}

/** Returns true if the error is HTTP 410 Gone (form no longer accepting submissions). */
export function isGoneError(error: unknown): boolean {
  return axios.isAxiosError(error) && error.response?.status === 410
}

/** Returns true if the error is HTTP 409 Conflict (duplicate submission). */
export function isConflictError(error: unknown): boolean {
  return axios.isAxiosError(error) && error.response?.status === 409
}
