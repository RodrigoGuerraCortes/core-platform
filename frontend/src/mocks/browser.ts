/**
 * MSW browser worker — development-only mock API.
 *
 * Intercepts /api/reference/*, /api/forms/*, and /api/auth/* requests so the
 * frontend works without a running Laravel backend for DEMO/REFERENCE modules.
 *
 * Business verticals (CondoFlow, MiniHIS, etc.) MUST use real backend APIs.
 * Their handlers live under tests/ and are only registered in Vitest.
 *
 * Started in main.ts when import.meta.env.DEV is true.
 * The service worker file (public/mockServiceWorker.js) was generated with:
 *   npx msw init public/ --save
 *
 * Adding new module mocks (reference/demo only):
 *  1. Create handlers in `src/modules/<module>/mocks/handlers.ts`
 *  2. Import and spread the array below
 *  3. Do NOT import from msw/node — that is only for Vitest (src/tests/mocks/server.ts)
 */

import { setupWorker } from 'msw/browser'
import { http, HttpResponse } from 'msw'
import { referenceHandlers } from '@/modules/reference/mocks/handlers'
import { handlers as formsHandlers } from '@/modules/dynamic-forms/tests/mocks/handlers'

// ─── Runtime mode ─────────────────────────────────────────────────────────────
// Only register handlers when the runtime mode is 'cookbook'.
// For 'vertical' mode (CondoFlow etc.) the worker is never started,
// so handlers are irrelevant. This guard prevents accidental handler
// registration even if the module is imported elsewhere.
const runtimeMode: string =
  import.meta.env.VITE_RUNTIME_MODE ?? 'vertical'

// ─── Dev auth handlers ────────────────────────────────────────────────────────
// Experience-aware mock users. Switch via localStorage 'msw:experience'.
// Supports: 'platform' (default), 'condoflow', 'his' (future).

interface MockUser {
  id: number
  name: string
  email: string
  email_verified_at: string
  is_platform_admin: boolean
  experience?: string
  role?: string
}

const mockUsers: Record<string, MockUser> = {
  platform: {
    id: 1,
    name: 'Dev User',
    email: 'dev@example.com',
    email_verified_at: new Date().toISOString(),
    is_platform_admin: true,
    experience: 'platform',
    role: 'platform_admin',
  },
  condoflow: {
    id: 2,
    name: 'María Residenta',
    email: 'maria@condoflow.dev',
    email_verified_at: new Date().toISOString(),
    is_platform_admin: false,
    experience: 'condoflow',
    role: 'resident',
  },
  condoflow_admin: {
    id: 3,
    name: 'Admin Condo',
    email: 'admin@condoflow.dev',
    email_verified_at: new Date().toISOString(),
    is_platform_admin: false,
    experience: 'condoflow',
    role: 'building_admin',
  },
}

function getActiveMockUser(): MockUser {
  const experience = localStorage.getItem('msw:experience') ?? 'platform'
  return mockUsers[experience] ?? mockUsers.platform
}

const devAuthHandlers = [
  http.get('/sanctum/csrf-cookie', () => new HttpResponse(null, { status: 204 })),

  http.get('/api/auth/me', () => {
    // Respect "logged out" state so guest pages (e.g. /condoflow/login) can render.
    if (localStorage.getItem('msw:logged-out') === 'true') {
      return HttpResponse.json({ message: 'Unauthenticated.' }, { status: 401 })
    }
    return HttpResponse.json({ data: getActiveMockUser() })
  }),

  http.post('/api/auth/login', () => {
    localStorage.removeItem('msw:logged-out')
    return HttpResponse.json({ data: getActiveMockUser() })
  }),

  http.post('/api/auth/logout', () => {
    localStorage.setItem('msw:logged-out', 'true')
    return HttpResponse.json({ message: 'Logged out successfully.' })
  }),
]

// ─── Worker ───────────────────────────────────────────────────────────────────
// Only create the worker with handlers when runtime mode is 'cookbook'.
// For 'vertical' mode the worker is never started (see main.ts), so we
// still export a worker instance but with an empty handler list.
const activeHandlers =
  runtimeMode === 'cookbook'
    ? [...devAuthHandlers, ...referenceHandlers, ...formsHandlers]
    : []

export const worker = setupWorker(...activeHandlers)
