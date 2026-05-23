/**
 * FormCreatePage — authoring flow integration tests.
 *
 * These tests exercise the API contract via MSW without mounting the full
 * Vue component (no @testing-library/vue available). They verify the API
 * layer used by FormCreatePage behaves correctly.
 *
 * Component-level rendering tests can be added once @testing-library/vue
 * is added to the project dependencies.
 */
import { describe, it, expect, afterEach } from 'vitest'
import { server } from '../mocks/server'
import { http, HttpResponse } from 'msw'
import { createForm, fetchForms } from '../../api/forms'

// MSW server lifecycle is managed globally in src/tests/setup.ts.
// Use afterEach here only to reset per-test handler overrides.
afterEach(() => server.resetHandlers())

describe('Forms API layer (authoring)', () => {
  it('createForm returns a draft form with the given name', async () => {
    server.use(
      http.post('/api/forms', async ({ request }) => {
        const body = (await request.json()) as { name: string }
        return HttpResponse.json(
          { data: { id: 42, name: body.name, status: 'draft', tenant_id: 1,
              active_version_id: null, active_version: null,
              created_at: '', updated_at: '' } },
          { status: 201 },
        )
      }),
    )

    const result = await createForm({ name: 'Onboarding Form' })
    expect(result.id).toBe(42)
    expect(result.name).toBe('Onboarding Form')
    expect(result.status).toBe('draft')
    expect(result.active_version_id).toBeNull()
  })

  it('fetchForms returns a paginated list', async () => {
    const result = await fetchForms()
    expect(Array.isArray(result.data)).toBe(true)
    expect(result.meta.current_page).toBe(1)
  })

  it('createForm sends name and description in the request body', async () => {
    let capturedBody: unknown = null

    server.use(
      http.post('/api/forms', async ({ request }) => {
        capturedBody = await request.json()
        return HttpResponse.json(
          { data: { id: 1, name: 'Test', status: 'draft', tenant_id: 1,
              active_version_id: null, active_version: null,
              created_at: '', updated_at: '' } },
          { status: 201 },
        )
      }),
    )

    await createForm({ name: 'Test', description: 'A description' })

    expect(capturedBody).toMatchObject({ name: 'Test', description: 'A description' })
  })

  it('createForm propagates HTTP errors', async () => {
    server.use(
      http.post('/api/forms', () => HttpResponse.json({ message: 'Server error' }, { status: 500 })),
    )

    await expect(createForm({ name: 'Bad' })).rejects.toThrow()
  })
})
