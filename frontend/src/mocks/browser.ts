/**
 * MSW browser worker — development-only mock API.
 *
 * Intercepts all /api/reference/* and /api/auth/* requests so the frontend
 * works without a running Laravel backend during local development.
 *
 * Started in main.ts when import.meta.env.DEV is true.
 * The service worker file (public/mockServiceWorker.js) was generated with:
 *   npx msw init public/ --save
 *
 * Adding new module mocks:
 *  1. Create handlers in `src/modules/<module>/mocks/handlers.ts`
 *  2. Import and spread the array below
 *  3. Do NOT import from msw/node — that is only for Vitest (src/tests/mocks/server.ts)
 */

import { setupWorker } from 'msw/browser'
import { http, HttpResponse } from 'msw'
import { referenceHandlers } from '@/modules/reference/mocks/handlers'
import { handlers as formsHandlers } from '@/modules/dynamic-forms/tests/mocks/handlers'
import { condoflowHandlers } from '@/modules/condoflow/mocks/handlers'

// ─── Dev auth handlers ────────────────────────────────────────────────────────
// Provide a pre-authenticated user so you can navigate the app without a
// real Laravel session.  Override by editing the mockDevUser object below.

const mockDevUser = {
  id: 1,
  name: 'Dev User',
  email: 'dev@example.com',
  email_verified_at: new Date().toISOString(),
  is_platform_admin: true,
}

const devAuthHandlers = [
  http.get('/sanctum/csrf-cookie', () => new HttpResponse(null, { status: 204 })),

  http.get('/api/auth/me', () => {
    // Respect "logged out" state so guest pages (e.g. /condoflow/login) can render.
    if (localStorage.getItem('msw:logged-out') === 'true') {
      return HttpResponse.json({ message: 'Unauthenticated.' }, { status: 401 })
    }
    return HttpResponse.json({ data: mockDevUser })
  }),

  http.post('/api/auth/login', () => {
    localStorage.removeItem('msw:logged-out')
    return HttpResponse.json({ data: mockDevUser })
  }),

  http.post('/api/auth/logout', () => {
    localStorage.setItem('msw:logged-out', 'true')
    return HttpResponse.json({ message: 'Logged out successfully.' })
  }),
]

// ─── Worker ───────────────────────────────────────────────────────────────────

export const worker = setupWorker(
  ...devAuthHandlers,
  ...referenceHandlers,
  ...formsHandlers,
  ...condoflowHandlers,
)
