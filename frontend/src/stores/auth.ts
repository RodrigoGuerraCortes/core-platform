import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { setAuthToken } from '@/shared/api/client'

export interface AuthUser {
  id: number
  name: string
  email: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const token = ref<string | null>(null)

  const isAuthenticated = computed(() => user.value !== null)

  function login(authenticatedUser: AuthUser, bearerToken: string): void {
    user.value = authenticatedUser
    token.value = bearerToken
    setAuthToken(bearerToken)
  }

  function logout(): void {
    user.value = null
    token.value = null
    setAuthToken(null)
  }

  // Listen for 401 responses from the API client and auto-logout.
  if (typeof window !== 'undefined') {
    window.addEventListener('auth:unauthorized', logout)
  }

  return { user, token, isAuthenticated, login, logout }
})
