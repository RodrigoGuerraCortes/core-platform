/**
 * MSW browser worker — development-only mock API.
 *
 * Intercepts /api/reference/* and /api/forms/* requests so the frontend can
 * display demo data for Reference and Forms modules without dedicated backend endpoints.
 *
 * Auth (/api/auth/*) always goes to the REAL Laravel backend (Sanctum session).
 * Business verticals (CondoFlow, MiniHIS, etc.) MUST use real backend APIs.
 *
 * onUnhandledRequest: 'bypass' means any request without a handler (e.g. /api/condoflow/*)
 * passes through to the Vite proxy → Laravel without interference.
 *
 * Started in main.ts only when VITE_RUNTIME_MODE === 'cookbook'.
 */

import { setupWorker } from 'msw/browser'
import { referenceHandlers } from '@/modules/reference/mocks/handlers'
import { handlers as formsHandlers } from '@/modules/dynamic-forms/tests/mocks/handlers'

// ─── Runtime mode ─────────────────────────────────────────────────────────────
const runtimeMode: string =
  import.meta.env.VITE_RUNTIME_MODE ?? 'vertical'

// ─── Worker ───────────────────────────────────────────────────────────────────
const activeHandlers =
  runtimeMode === 'cookbook'
    ? [...referenceHandlers, ...formsHandlers]
    : []

export const worker = setupWorker(...activeHandlers)
