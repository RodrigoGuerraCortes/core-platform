/**
 * Shared API response shapes.
 *
 * The Laravel backend always wraps responses in { data: ... }.
 * Paginated responses add `meta` and `links`.
 */

export interface ApiResponse<T> {
  data: T
}

export interface PaginatedMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export interface PaginatedLinks {
  first: string
  last: string
  prev: string | null
  next: string | null
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: PaginatedMeta
  links: PaginatedLinks
}

/**
 * Laravel validation error shape — 422 Unprocessable Content.
 */
export interface ValidationErrors {
  message: string
  errors: Record<string, string[]>
}

/**
 * Generic API error thrown by the Axios response interceptor.
 * Consumers should use `axios.isAxiosError(e)` to narrow first.
 */
export interface ApiError {
  status: number
  message: string
  errors?: Record<string, string[]>
}
