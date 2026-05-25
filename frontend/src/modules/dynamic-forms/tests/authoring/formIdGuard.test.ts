/**
 * Regression tests for the "[object Object]" URL bug.
 *
 * Root cause: useFormQuery and useFormVersionsQuery accepted MaybeRef<number>
 * but their queryFn passed the ref object directly to the API functions instead
 * of calling toValue() first. The API function interpolated the ref object into
 * a template string, producing `/forms/[object Object]` — which PostgreSQL
 * rejected with SQLSTATE[22P02].
 *
 * These tests assert:
 *  1. assertNumericId (the API-layer guard) throws immediately when a non-number
 *     is passed, making the bug visible in development before it hits the network.
 *  2. All formId-consuming API functions reject ref-like objects.
 *  3. Valid numeric IDs are accepted.
 */
import { describe, it, expect } from 'vitest'
import { ref, computed } from 'vue'
import { fetchForm, fetchFormVersions, publishForm, createFormVersion, updateForm, submitForm } from '../../api/forms'

// ── assertNumericId guard ────────────────────────────────────────────────────

describe('forms API — assertNumericId guard', () => {
  it('rejects a Vue ref object with a descriptive TypeError', async () => {
    const refObject = ref(42) // <-- the ref itself, not its .value

    await expect(fetchForm(refObject as unknown as number)).rejects.toThrow(TypeError)
    await expect(fetchForm(refObject as unknown as number)).rejects.toThrow(
      /must be a finite number/,
    )
    await expect(fetchForm(refObject as unknown as number)).rejects.toThrow(
      /Did you forget toValue\(\)/,
    )
  })

  it('rejects a computed ref object', async () => {
    const id = computed(() => 7)

    await expect(fetchForm(id as unknown as number)).rejects.toThrow(TypeError)
  })

  it('rejects a plain object', async () => {
    await expect(fetchForm({} as unknown as number)).rejects.toThrow(TypeError)
  })

  it('rejects undefined', async () => {
    await expect(fetchForm(undefined as unknown as number)).rejects.toThrow(TypeError)
  })

  it('rejects NaN', async () => {
    await expect(fetchForm(NaN)).rejects.toThrow(TypeError)
  })

  it('rejects a numeric string — must be a true number', async () => {
    await expect(fetchForm('42' as unknown as number)).rejects.toThrow(TypeError)
  })

  it('accepts a finite integer — guard does not throw TypeError', async () => {
    // A valid number must NOT be rejected by assertNumericId.
    // We verify this by checking the error is not a TypeError (guard error).
    // MSW may resolve or reject with a different error — both are acceptable.
    let caught: unknown = null
    try {
      await fetchForm(99999)
    } catch (e) {
      caught = e
    }
    if (caught !== null) {
      expect(caught).not.toBeInstanceOf(TypeError)
    }
    // If it resolved (MSW returned a fixture), that's also fine — guard passed.
  })
})

// ── All formId-consuming functions reject ref objects ───────────────────────

describe('forms API — all formId functions reject non-numbers', () => {
  const badId = ref(1) as unknown as number // ref object, not .value

  it('fetchFormVersions rejects a ref object', async () => {
    await expect(fetchFormVersions(badId)).rejects.toThrow(/must be a finite number/)
  })

  it('publishForm rejects a ref object', async () => {
    await expect(publishForm(badId)).rejects.toThrow(/must be a finite number/)
  })

  it('updateForm rejects a ref object', async () => {
    await expect(updateForm(badId, { name: 'x' })).rejects.toThrow(/must be a finite number/)
  })

  it('createFormVersion rejects a ref object', async () => {
    await expect(
      createFormVersion(badId, { version: 1, title: 'T', fields: [] }),
    ).rejects.toThrow(/must be a finite number/)
  })

  it('submitForm rejects a ref object', async () => {
    await expect(submitForm(badId, {})).rejects.toThrow(/must be a finite number/)
  })
})
