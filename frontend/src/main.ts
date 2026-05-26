import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './app/App.vue'
import router from './router'
import { vuetify } from './plugins/vuetify'
import { installQuery } from './plugins/query'
import { useAuthStore } from './stores/auth'

/**
 * Bootstrap the SPA.
 *
 * Order matters:
 *  1. MSW worker (dev only) — must intercept fetch BEFORE any store calls
 *     so that /api/auth/me is mocked before bootstrapCurrentUser() fires.
 *  2. Pinia — must be installed before any store is accessed.
 *  3. Auth bootstrap — await session restore so the router guard has correct
 *     auth state on the very first navigation (prevents auth flicker).
 *  4. Router — installed after auth is resolved; beforeEach guard is safe.
 *  5. Mount — render after everything is ready.
 */
async function bootstrap(): Promise<void> {
  // ── Dev-only MSW browser worker ──────────────────────────────────────────
  // Intercepts /api/* requests for demo modules (Reference, Forms).
  // Business verticals (CondoFlow) use the real Laravel backend via Vite proxy.
  // onUnhandledRequest: 'bypass' lets real requests pass through to Laravel.
  if (import.meta.env.DEV) {
    // Force service worker update on code changes
    const MSW_VERSION = '2026-05-25-gov3'
    const storedVersion = localStorage.getItem('msw:version')
    
    if (storedVersion !== MSW_VERSION) {
      console.log('[MSW] Code version changed - unregistering old service worker')
      const registrations = await navigator.serviceWorker.getRegistrations()
      await Promise.all(registrations.map(r => r.unregister()))
      localStorage.setItem('msw:version', MSW_VERSION)
      console.log('[MSW] Service worker cleared. Reloading...')
      globalThis.location.reload()
      return
    }

    const { worker } = await import('./mocks/browser')
    await worker.start({
      onUnhandledRequest: 'bypass',
      serviceWorker: { options: { updateViaCache: 'none' } },
    })
    console.log('[MSW] Worker started - intercepting Reference and Forms APIs only')
  }
  const app = createApp(App)

  app.use(createPinia())
  app.use(vuetify)
  installQuery(app)

  // Restore existing session before the router processes the first URL.
  // A 401 from /api/auth/me is silently treated as "no session" — not an error.
  await useAuthStore().bootstrapCurrentUser()

  app.use(router)
  app.mount('#app')
}

bootstrap()
