import { http, HttpResponse } from 'msw'
import { mockForm, mockVersion, basicSchema } from '../fixtures/schemas'
import type { FormDetail, FormSubmissionDetail, FormVersionDetail } from '../../types'

const BASE = '/api'

let _versionCounter = mockVersion.version_number

const mockDraftVersion: FormVersionDetail = {
  id: 99,
  form_id: mockForm.id,
  version_number: 2,
  schema: { ...basicSchema, title: 'Draft Version' },
  schema_hash: 'draft_hash',
  label: null,
  published_at: null,
}

export const handlers = [
  // ── GET /api/forms (list) ─────────────────────────────────────────────────
  http.get(`${BASE}/forms`, () => {
    return HttpResponse.json({
      data: [mockForm],
      meta: { current_page: 1, last_page: 1, per_page: 15, total: 1, from: 1, to: 1 },
      links: { first: '', last: '', prev: null, next: null },
    })
  }),

  // ── GET /api/forms/:id ────────────────────────────────────────────────────
  http.get(`${BASE}/forms/:formId`, () => {
    return HttpResponse.json({ data: mockForm })
  }),

  // ── POST /api/forms ───────────────────────────────────────────────────────
  http.post(`${BASE}/forms`, async ({ request }) => {
    const body = (await request.json()) as { name: string; description?: string }
    const created: FormDetail = {
      id: 42,
      tenant_id: 1,
      name: body.name,
      description: body.description ?? null,
      status: 'draft',
      active_version_id: null,
      active_version: null,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    }
    return HttpResponse.json({ data: created }, { status: 201 })
  }),

  // ── PATCH /api/forms/:id ──────────────────────────────────────────────────
  http.patch(`${BASE}/forms/:formId`, async ({ request }) => {
    const body = (await request.json()) as Partial<FormDetail>
    return HttpResponse.json({ data: { ...mockForm, ...body } })
  }),

  // ── POST /api/forms/:id/publish ───────────────────────────────────────────
  http.post(`${BASE}/forms/:formId/publish`, () => {
    return HttpResponse.json({ data: { ...mockForm, status: 'active' } })
  }),

  // ── GET /api/forms/:id/versions ───────────────────────────────────────────
  http.get(`${BASE}/forms/:formId/versions`, () => {
    return HttpResponse.json({
      data: [mockDraftVersion, mockVersion],
      meta: { current_page: 1, last_page: 1, per_page: 15, total: 2, from: 1, to: 2 },
      links: { first: '', last: '', prev: null, next: null },
    })
  }),

  // ── POST /api/forms/:id/versions ─────────────────────────────────────────
  http.post(`${BASE}/forms/:formId/versions`, async ({ request }) => {
    const body = (await request.json()) as { schema: unknown; label?: string }
    _versionCounter++
    const created: FormVersionDetail = {
      id: 100 + _versionCounter,
      form_id: mockForm.id,
      version_number: _versionCounter,
      schema: body.schema as FormVersionDetail['schema'],
      schema_hash: `hash_${_versionCounter}`,
      label: body.label ?? null,
      published_at: null,
    }
    return HttpResponse.json({ data: created }, { status: 201 })
  }),

  // ── POST /api/forms/:id/submit ────────────────────────────────────────────
  http.post(`${BASE}/forms/:formId/submit`, async ({ request }) => {
    const body = (await request.json()) as { payload: Record<string, unknown> }
    const submission: FormSubmissionDetail = {
      id: 1,
      form_id: mockForm.id,
      form_version_id: mockForm.active_version_id!,
      submitted_by: null,
      payload: body.payload,
      submitted_at: new Date().toISOString(),
    }
    return HttpResponse.json({ data: submission }, { status: 201 })
  }),
]
