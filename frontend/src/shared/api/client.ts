import axios from 'axios'
import type { AxiosInstance, InternalAxiosRequestConfig } from 'axios'

// ─── Module-level state (avoids circular Pinia dependency at import time) ─────
// Stores call setActiveTenant() after Pinia is ready.

let _tenantId: string | null = null

export function setActiveTenant(id: string | null): void {
  _tenantId = id
}

// ─── Axios instance ────────────────────────────────────────────────────────────
// withCredentials: true  — sends the laravel_session + XSRF-TOKEN cookies
//                          with every API request (required for Sanctum SPA).
// xsrfCookieName/Header — Axios reads XSRF-TOKEN cookie and attaches it as
//                          X-XSRF-TOKEN header automatically on mutating methods.

const apiClient: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? '/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
})

// ─── Request interceptor ───────────────────────────────────────────────────────

apiClient.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  if (_tenantId) {
    config.headers['X-Tenant-Id'] = _tenantId
  }

  return config
})

// ─── Response interceptor ─────────────────────────────────────────────────────

apiClient.interceptors.response.use(
  (response) => response,
  (error: unknown) => {
    if (axios.isAxiosError(error) && error.response?.status === 401) {
      // Emit a DOM event so the auth store can react without a direct import.
      window.dispatchEvent(new CustomEvent('auth:unauthorized'))
    }
    return Promise.reject(error)
  },
)

export default apiClient
