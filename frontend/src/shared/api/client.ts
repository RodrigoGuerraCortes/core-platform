import axios from 'axios'
import type { AxiosInstance, InternalAxiosRequestConfig } from 'axios'

// ─── Module-level state (avoids circular Pinia dependency at import time) ─────
// Stores call setActiveTenant() / setAuthToken() after Pinia is ready.

let _tenantId: string | null = null
let _authToken: string | null = null

export function setActiveTenant(id: string | null): void {
  _tenantId = id
}

export function setAuthToken(token: string | null): void {
  _authToken = token
}

// ─── Axios instance ────────────────────────────────────────────────────────────

const apiClient: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? '/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  // Set to true only if you switch to Sanctum SPA cookie auth.
  withCredentials: false,
})

// ─── Request interceptor ───────────────────────────────────────────────────────

apiClient.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  if (_authToken) {
    config.headers.Authorization = `Bearer ${_authToken}`
  }

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
