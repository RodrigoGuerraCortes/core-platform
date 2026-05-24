/**
 * Auth MSW handlers — mock Sanctum SPA authentication endpoints.
 *
 * Stateful: _authenticated tracks session state across handler calls within
 * a single test. Reset it with setMockAuthenticated(false) in beforeEach.
 */

import { http, HttpResponse } from 'msw'
import type { AuthUser } from '@/stores/auth'

export const mockAuthUser: AuthUser = {
  id: 1,
  name: 'Test User',
  email: 'test@example.com',
  email_verified_at: '2024-01-01T00:00:00.000Z',
  is_platform_admin: false,
}

let _authenticated = false

export function setMockAuthenticated(value: boolean): void {
  _authenticated = value
}

export const authHandlers = [
  // GET /sanctum/csrf-cookie — always succeeds in tests (cookie management is browser-level)
  http.get('/sanctum/csrf-cookie', () => {
    return new HttpResponse(null, { status: 204 })
  }),

  // POST /api/auth/login
  http.post('/api/auth/login', async ({ request }) => {
    const body = (await request.json()) as { email: string; password: string }

    if (body.email === mockAuthUser.email && body.password === 'password') {
      _authenticated = true
      return HttpResponse.json({ data: mockAuthUser })
    }

    return HttpResponse.json(
      { message: 'Invalid credentials', errors: [] },
      { status: 401 },
    )
  }),

  // POST /api/auth/logout
  http.post('/api/auth/logout', () => {
    _authenticated = false
    return HttpResponse.json({ message: 'Logged out successfully.' })
  }),

  // GET /api/auth/me
  http.get('/api/auth/me', () => {
    if (_authenticated) {
      return HttpResponse.json({ data: mockAuthUser })
    }
    return HttpResponse.json({ message: 'Unauthenticated.' }, { status: 401 })
  }),
]
