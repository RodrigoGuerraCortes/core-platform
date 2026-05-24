import { describe, it, expect, beforeEach } from 'vitest'
import { createRouter, createWebHistory } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'
import { defineComponent } from 'vue'
import { server, mockAuthUser } from '@/tests/mocks/server'
import { http, HttpResponse } from 'msw'
import { useAuthStore } from '@/stores/auth'

const StubPage = defineComponent({ template: '<div />' })

function makeRouter() {
  const router = createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/login', name: 'login', component: StubPage, meta: { guestOnly: true } },
      { path: '/home',  name: 'home',  component: StubPage },
      { path: '/protected', name: 'protected', component: StubPage, meta: { requiresAuth: true } },
    ],
  })

  router.beforeEach((to) => {
    const authStore = useAuthStore()
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
      return { name: 'login', query: { redirect: to.fullPath } }
    }
    if (to.meta.guestOnly && authStore.isAuthenticated) {
      return { name: 'home' }
    }
  })

  return router
}

describe('router auth guard', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('redirects unauthenticated users to login when accessing protected routes', async () => {
    const store = useAuthStore()
    await store.bootstrapCurrentUser() // default 401 -> unauthenticated

    const router = makeRouter()
    await router.push({ name: 'protected' })

    expect(router.currentRoute.value.name).toBe('login')
    expect(router.currentRoute.value.query.redirect).toBe('/protected')
  })

  it('allows authenticated users to access protected routes', async () => {
    const store = useAuthStore()
    await store.login(mockAuthUser.email, 'password')

    const router = makeRouter()
    await router.push({ name: 'protected' })

    expect(router.currentRoute.value.name).toBe('protected')
  })

  it('redirects authenticated users away from guest-only routes', async () => {
    const store = useAuthStore()
    await store.login(mockAuthUser.email, 'password')

    const router = makeRouter()
    await router.push({ name: 'login' })

    expect(router.currentRoute.value.name).toBe('home')
  })

  it('allows unauthenticated users to access guest-only routes', async () => {
    const store = useAuthStore()
    await store.bootstrapCurrentUser()

    const router = makeRouter()
    await router.push({ name: 'login' })

    expect(router.currentRoute.value.name).toBe('login')
  })

  it('preserves redirect query param on protected route redirect', async () => {
    const store = useAuthStore()
    await store.bootstrapCurrentUser()

    const router = makeRouter()
    await router.push('/protected?foo=bar')

    expect(router.currentRoute.value.name).toBe('login')
    expect(router.currentRoute.value.query.redirect).toBe('/protected?foo=bar')
  })

  it('after 401 event guard blocks access to protected routes', async () => {
    server.use(http.get('/api/auth/me', () => HttpResponse.json({ data: mockAuthUser })))
    const store = useAuthStore()
    await store.bootstrapCurrentUser()
    expect(store.isAuthenticated).toBe(true)

    window.dispatchEvent(new CustomEvent('auth:unauthorized'))
    expect(store.isAuthenticated).toBe(false)

    const router = makeRouter()
    await router.push({ name: 'protected' })
    expect(router.currentRoute.value.name).toBe('login')
  })
})
