/**
 * Auth store — Sanctum SPA session state.
 *
 * Responsibilities:
 *  - Hold the currently authenticated user (or null).
 *  - Expose loading + bootstrapping state to prevent auth flicker.
 *  - Delegate all HTTP calls to the auth API layer (src/shared/api/auth.ts).
 *  - React to 401 events emitted by the Axios interceptor.
 *
 * NOT in scope:
 *  - Token storage (no bearer tokens, no localStorage auth).
 *  - Tenant resolution (handled by useTenantStore).
 *  - RBAC / permission checks (future module).
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { apiLogin, apiLogout, fetchCurrentUser } from '@/shared/api/auth'

export interface AuthUser {
  id: number
  name: string
  email: string
  email_verified_at: string | null
  is_platform_admin: boolean
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)

  /** True while login() / logout() is in flight. */
  const isLoading = ref(false)

  /**
   * True from store creation until the first bootstrapCurrentUser() call
   * completes. Guards the router so it never redirects during startup.
   */
  const isBootstrapping = ref(true)

  const isAuthenticated = computed(() => user.value !== null)

  // ─── Actions ──────────────────────────────────────────────────────────────

  /**
   * Attempt to restore the existing session on app start.
   * A 401 response means no session exists — that is not an error.
   * Must be awaited in main.ts before the router is installed.
   */
  async function bootstrapCurrentUser(): Promise<void> {
    isBootstrapping.value = true
    try {
      user.value = await fetchCurrentUser()
    } catch {
      // No session — start unauthenticated.
      user.value = null
    } finally {
      isBootstrapping.value = false
    }
  }

  /**
   * Authenticate with email + password.
   * Fetches the CSRF cookie first (see apiLogin), then establishes a session.
   *
   * @throws AxiosError — propagated to the UI for error display.
   */
  async function login(email: string, password: string): Promise<void> {
    isLoading.value = true
    try {
      user.value = await apiLogin(email, password)
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Invalidate the current session.
   * Always clears local state even if the network call fails.
   */
  async function logout(): Promise<void> {
    isLoading.value = true
    try {
      await apiLogout()
    } catch {
      // Best-effort — always clear local state.
    } finally {
      user.value = null
      isLoading.value = false
    }
  }

  // ─── Global 401 handler ───────────────────────────────────────────────────
  // The Axios response interceptor in client.ts emits 'auth:unauthorized'
  // on any 401. We clear user state so the router guard can redirect.
  if (typeof window !== 'undefined') {
    window.addEventListener('auth:unauthorized', () => {
      user.value = null
    })
  }

  return {
    user,
    isAuthenticated,
    isLoading,
    isBootstrapping,
    bootstrapCurrentUser,
    login,
    logout,
  }
})
