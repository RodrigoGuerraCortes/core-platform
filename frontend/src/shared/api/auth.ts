/**
 * Auth API — thin wrappers around Sanctum SPA session authentication.
 *
 * Flow:
 *   1. GET  /sanctum/csrf-cookie  → sets XSRF-TOKEN cookie
 *   2. POST /api/auth/login       → establishes laravel_session
 *   3. GET  /api/auth/me          → returns the authenticated user
 *   4. POST /api/auth/logout      → invalidates session
 *
 * Axios is configured with withCredentials: true and reads the XSRF-TOKEN
 * cookie automatically, so no manual header attachment is needed.
 */

import axios from 'axios'
import type { AuthUser } from '@/stores/auth'
import apiClient from './client'

/**
 * Fetch the Sanctum CSRF cookie.
 *
 * Must be called before the first mutating request from an unauthenticated
 * state (i.e. before login). Subsequent requests reuse the same XSRF token
 * for the lifetime of the session.
 */
export async function fetchCsrfCookie(): Promise<void> {
  // Plain axios — no /api baseURL, no tenant headers. Just the CSRF handshake.
  await axios.get('/sanctum/csrf-cookie', { withCredentials: true })
}

/**
 * Authenticate via Sanctum SPA session (no tokens issued).
 * Fetches the CSRF cookie first, then POSTs credentials.
 *
 * @throws AxiosError 401 on invalid credentials, 422 on validation failure.
 */
export async function apiLogin(email: string, password: string): Promise<AuthUser> {
  await fetchCsrfCookie()
  const response = await apiClient.post<{ data: AuthUser }>('/auth/login', { email, password })
  return response.data.data
}

/**
 * Invalidate the current session.
 * The backend destroys the session and regenerates the CSRF token.
 */
export async function apiLogout(): Promise<void> {
  await apiClient.post('/auth/logout')
}

/**
 * Fetch the currently authenticated user.
 *
 * @throws AxiosError 401 when no valid session exists.
 */
export async function fetchCurrentUser(): Promise<AuthUser> {
  const response = await apiClient.get<{ data: AuthUser }>('/auth/me')
  return response.data.data
}
