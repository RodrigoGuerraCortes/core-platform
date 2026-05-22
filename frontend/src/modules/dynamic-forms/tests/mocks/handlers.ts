import { http, HttpResponse } from 'msw'
import { mockForm } from '../fixtures/schemas'
import type { FormSubmissionDetail } from '../../types'

const BASE = '/api'

export const handlers = [
  // ── GET /api/forms/:id ────────────────────────────────────────────────────
  http.get(`${BASE}/forms/:formId`, () => {
    return HttpResponse.json({ data: mockForm })
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
