/**
 * Global MSW server — handles auth + dynamic-forms endpoints.
 *
 * Used by src/tests/setup.ts for the test lifecycle (listen/reset/close).
 * Module tests can add per-test handlers via server.use() — those are
 * cleared automatically by afterEach(() => server.resetHandlers()) in setup.
 *
 * Auth handlers are stateless by default (GET /api/auth/me → 401).
 * Tests that need an authenticated user should add a server.use() override.
 */

import { setupServer } from 'msw/node'
import { http, HttpResponse } from 'msw'
import { handlers as formsHandlers } from '@/modules/dynamic-forms/tests/mocks/handlers'

// ─── Canonical mock user ──────────────────────────────────────────────────────
// Exported so tests can reference the expected shape without duplicating it.

export const mockAuthUser = {
  id: 1,
  name: 'Test User',
  email: 'test@example.com',
  email_verified_at: '2024-01-01T00:00:00.000Z',
  is_platform_admin: false,
}

// ─── Auth handlers (stateless) ────────────────────────────────────────────────
// GET /api/auth/me returns 401 by default.
// Tests that need an authenticated user do:
//   server.use(http.get('/api/auth/me', () => HttpResponse.json({ data: mockAuthUser })))

const authHandlers = [
  http.get('/sanctum/csrf-cookie', () => new HttpResponse(null, { status: 204 })),

  http.post('/api/auth/login', async ({ request }) => {
    const body = (await request.json()) as { email: string; password: string }
    if (body.email === mockAuthUser.email && body.password === 'password') {
      return HttpResponse.json({ data: mockAuthUser })
    }
    return HttpResponse.json({ message: 'Invalid credentials', errors: [] }, { status: 401 })
  }),

  http.post('/api/auth/logout', () =>
    HttpResponse.json({ message: 'Logged out successfully.' }),
  ),

  // Default: unauthenticated — override per test with server.use().
  http.get('/api/auth/me', () =>
    HttpResponse.json({ message: 'Unauthenticated.' }, { status: 401 }),
  ),
]

// ─── Server ───────────────────────────────────────────────────────────────────

export const server = setupServer(...authHandlers, ...formsHandlers)
