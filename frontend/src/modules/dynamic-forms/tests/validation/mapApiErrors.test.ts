import { describe, it, expect } from 'vitest'
import { mapApiErrors, isGoneError, isConflictError } from '../../validation/mapApiErrors'
import axios from 'axios'

// ─── Helpers ───────────────────────────────────────────────────────────────────

function mockAxiosError(status: number, data: unknown = {}) {
  const error = new axios.AxiosError('Request failed')
  error.response = {
    status,
    data,
    headers: {},
    config: { headers: new axios.AxiosHeaders() },
    statusText: String(status),
  }
  return error
}

// ─── mapApiErrors ──────────────────────────────────────────────────────────────

describe('mapApiErrors', () => {
  it('returns null for non-Axios errors', () => {
    expect(mapApiErrors(new Error('generic'))).toBeNull()
  })

  it('returns null for non-422 Axios errors', () => {
    expect(mapApiErrors(mockAxiosError(500))).toBeNull()
    expect(mapApiErrors(mockAxiosError(401))).toBeNull()
  })

  it('maps 422 errors to first message per field', () => {
    const error = mockAxiosError(422, {
      message: 'Validation failed',
      errors: {
        email: ['Invalid email address', 'Another error'],
        name: ['Name is required'],
      },
    })
    const result = mapApiErrors(error)
    expect(result).not.toBeNull()
    expect(result!.email).toBe('Invalid email address')
    expect(result!.name).toBe('Name is required')
  })

  it('returns null when 422 response has no errors object', () => {
    const error = mockAxiosError(422, { message: 'Validation failed' })
    expect(mapApiErrors(error)).toBeNull()
  })

  it('uses fallback message when error array is empty', () => {
    const error = mockAxiosError(422, {
      errors: { field: [] },
    })
    const result = mapApiErrors(error)
    expect(result!.field).toBe('Invalid value')
  })
})

// ─── isGoneError ───────────────────────────────────────────────────────────────

describe('isGoneError', () => {
  it('returns true for 410 responses', () => {
    expect(isGoneError(mockAxiosError(410))).toBe(true)
  })

  it('returns false for other status codes', () => {
    expect(isGoneError(mockAxiosError(422))).toBe(false)
    expect(isGoneError(new Error('generic'))).toBe(false)
  })
})

// ─── isConflictError ───────────────────────────────────────────────────────────

describe('isConflictError', () => {
  it('returns true for 409 responses', () => {
    expect(isConflictError(mockAxiosError(409))).toBe(true)
  })

  it('returns false for other status codes', () => {
    expect(isConflictError(mockAxiosError(410))).toBe(false)
    expect(isConflictError(new Error('generic'))).toBe(false)
  })
})
