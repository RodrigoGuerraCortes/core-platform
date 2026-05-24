import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { server, mockAuthUser } from '@/tests/mocks/server'
import { http, HttpResponse } from 'msw'
import { useAuthStore } from '@/stores/auth'

function authenticatedMeHandler() {
  return http.get('/api/auth/me', () => HttpResponse.json({ data: mockAuthUser }))
}

describe('useAuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('starts unauthenticated with isBootstrapping true', () => {
    const store = useAuthStore()
    expect(store.isAuthenticated).toBe(false)
    expect(store.user).toBeNull()
    expect(store.isBootstrapping).toBe(true)
  })

  it('bootstrap: sets user when a valid session exists', async () => {
    server.use(authenticatedMeHandler())
    const store = useAuthStore()

    await store.bootstrapCurrentUser()

    expect(store.isAuthenticated).toBe(true)
    expect(store.user?.email).toBe(mockAuthUser.email)
    expect(store.isBootstrapping).toBe(false)
  })

  it('bootstrap: stays unauthenticated when no session (401)', async () => {
    const store = useAuthStore()

    await store.bootstrapCurrentUser()

    expect(store.isAuthenticated).toBe(false)
    expect(store.user).toBeNull()
    expect(store.isBootstrapping).toBe(false)
  })

  it('bootstrap: stays unauthenticated when API is unreachable', async () => {
    server.use(http.get('/api/auth/me', () => HttpResponse.error()))
    const store = useAuthStore()

    await store.bootstrapCurrentUser()

    expect(store.isAuthenticated).toBe(false)
    expect(store.isBootstrapping).toBe(false)
  })

  it('login: sets user on valid credentials', async () => {
    const store = useAuthStore()

    await store.login(mockAuthUser.email, 'password')

    expect(store.isAuthenticated).toBe(true)
    expect(store.user?.email).toBe(mockAuthUser.email)
    expect(store.isLoading).toBe(false)
  })

  it('login: throws and leaves user null on invalid credentials', async () => {
    const store = useAuthStore()

    await expect(store.login('bad@example.com', 'wrong')).rejects.toThrow()

    expect(store.isAuthenticated).toBe(false)
    expect(store.user).toBeNull()
    expect(store.isLoading).toBe(false)
  })

  it('login: resets isLoading even when the request fails', async () => {
    server.use(http.post('/api/auth/login', () => HttpResponse.error()))
    const store = useAuthStore()

    await expect(store.login('test@example.com', 'password')).rejects.toThrow()

    expect(store.isLoading).toBe(false)
  })

  it('logout: clears user state after successful logout', async () => {
    const store = useAuthStore()
    await store.login(mockAuthUser.email, 'password')
    expect(store.isAuthenticated).toBe(true)

    await store.logout()

    expect(store.isAuthenticated).toBe(false)
    expect(store.user).toBeNull()
    expect(store.isLoading).toBe(false)
  })

  it('logout: clears user state even if API call fails', async () => {
    const store = useAuthStore()
    await store.login(mockAuthUser.email, 'password')

    server.use(http.post('/api/auth/logout', () => HttpResponse.error()))

    await store.logout()

    expect(store.isAuthenticated).toBe(false)
    expect(store.user).toBeNull()
  })

  it('auth:unauthorized event clears authenticated user', async () => {
    server.use(authenticatedMeHandler())
    const store = useAuthStore()
    await store.bootstrapCurrentUser()
    expect(store.isAuthenticated).toBe(true)

    window.dispatchEvent(new CustomEvent('auth:unauthorized'))

    expect(store.isAuthenticated).toBe(false)
    expect(store.user).toBeNull()
  })
})
