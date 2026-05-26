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
  // ── Runtime mode ─────────────────────────────────────────────────────────
  // 'cookbook'  → uses MSW/browser for Reference & Forms modules
  // 'vertical'  → uses real Laravel backend via Vite proxy (CondoFlow, etc.)
  const runtimeMode: string =
    import.meta.env.VITE_RUNTIME_MODE ?? 'vertical'

  // ── Dev-only MSW browser worker ──────────────────────────────────────────
  // Intercepts /api/* requests ONLY for demo modules (Reference, Forms).
  // Business verticals (CondoFlow) use the real Laravel backend via Vite proxy.
  // MSW browser worker may ONLY start when VITE_RUNTIME_MODE === 'cookbook'.
  if (import.meta.env.DEV) {
    console.log(`[Runtime] Mode: "${runtimeMode}" | DEV: true`)

    if (runtimeMode === 'cookbook') {
      // Force service worker update on code changes
      const MSW_VERSION = '2026-05-25-gov4'
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
    } else {
      // Non-cookbook runtime (vertical, e2e, etc.) — ensure no stale SW controls this page.
      // A service worker remains active for the current page even after unregister().
      // We MUST reload once to fully release control.
      const registrations = await navigator.serviceWorker.getRegistrations()
      if (registrations.length > 0) {
        console.warn('[Runtime] Stale MSW service worker detected — unregistering and reloading')
        await Promise.all(registrations.map(r => r.unregister()))
        localStorage.removeItem('msw:version')
        globalThis.location.reload()
        return // Stop bootstrap — reload will restart cleanly without SW
      }
      console.log('[Runtime] No service worker active — proceeding with real backend')
    }
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
