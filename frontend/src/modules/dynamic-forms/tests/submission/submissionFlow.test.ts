import { describe, it, expect } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { VueQueryPlugin, QueryClient } from '@tanstack/vue-query'
import { http, HttpResponse } from 'msw'
import DynamicFormRenderer from '../../components/renderer/DynamicFormRenderer.vue'
import { mockVersion } from '../fixtures/schemas'
import { server } from '../mocks/server'

function mountRenderer() {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return mount(DynamicFormRenderer, {
    props: { formId: 1, version: mockVersion },
    global: {
      plugins: [createPinia(), [VueQueryPlugin, { queryClient }]],
    },
  })
}

function submitWithValidPayload(wrapper: ReturnType<typeof mountRenderer>) {
  const vm = wrapper.vm as unknown as {
    payload: Record<string, unknown>
    handleSubmit: () => Promise<void>
  }
  vm.payload.full_name = 'Alice'
  vm.payload.email = 'alice@example.com'
  vm.payload.country = 'us'
  return vm.handleSubmit()
}

// ─── Submit button loading state ───────────────────────────────────────────────

describe('Submission — loading state', () => {
  it('disables the submit button while submitting', async () => {
    const wrapper = mountRenderer()
    await flushPromises()

    const vm = wrapper.vm as unknown as {
      payload: Record<string, unknown>
      handleSubmit: () => Promise<void>
      isSubmitting: boolean
    }
    vm.payload.full_name = 'Alice'
    vm.payload.email = 'alice@example.com'
    vm.payload.country = 'us'

    // Start submission without awaiting — check disabled mid-flight.
    const promise = vm.handleSubmit()
    await wrapper.vm.$nextTick()

    // Submit button should be loading.
    const btn = wrapper.find('[type="submit"]')
    expect(btn.attributes('disabled') !== undefined || vm.isSubmitting).toBe(true)

    await promise
    await flushPromises()
  })
})

// ─── Success state ─────────────────────────────────────────────────────────────

describe('Submission — success state', () => {
  it('shows success state after a 201 response', async () => {
    const wrapper = mountRenderer()
    await flushPromises()

    await submitWithValidPayload(wrapper)
    await flushPromises()

    expect(wrapper.text()).toContain('Thank you')
    expect(wrapper.text()).not.toContain('Submit')
  })
})

// ─── 422 validation error mapping ─────────────────────────────────────────────

describe('Submission — 422 error mapping', () => {
  it('maps Laravel field errors back to the form without replacing the form', async () => {
    // Override the submit handler to return a 422.
    server.use(
      http.post('/api/forms/:formId/submit', () =>
        HttpResponse.json(
          {
            message: 'Validation failed',
            errors: { email: ['Invalid email address'] },
          },
          { status: 422 },
        ),
      ),
    )

    const wrapper = mountRenderer()
    await flushPromises()

    await submitWithValidPayload(wrapper)
    await flushPromises()

    // Form must still be visible (not replaced by an error state).
    expect(wrapper.find('[type="submit"]').exists()).toBe(true)
    // The field error must appear somewhere in the rendered output.
    expect(wrapper.text()).toContain('Invalid email address')
  })
})

// ─── 410 Gone ─────────────────────────────────────────────────────────────────

describe('Submission — 410 Gone', () => {
  it('shows the gone error state when the form is no longer active', async () => {
    server.use(
      http.post('/api/forms/:formId/submit', () =>
        HttpResponse.json({ message: 'Form is archived' }, { status: 410 }),
      ),
    )

    const wrapper = mountRenderer()
    await flushPromises()

    await submitWithValidPayload(wrapper)
    await flushPromises()

    expect(wrapper.text()).toContain('Form unavailable')
    expect(wrapper.text()).not.toContain('Thank you')
  })
})

// ─── 409 Duplicate ────────────────────────────────────────────────────────────

describe('Submission — 409 Conflict', () => {
  it('shows the duplicate error state for a 409 response', async () => {
    server.use(
      http.post('/api/forms/:formId/submit', () =>
        HttpResponse.json({ message: 'Duplicate submission' }, { status: 409 }),
      ),
    )

    const wrapper = mountRenderer()
    await flushPromises()

    await submitWithValidPayload(wrapper)
    await flushPromises()

    expect(wrapper.text()).toContain('Already submitted')
  })
})

// ─── Generic failure ──────────────────────────────────────────────────────────

describe('Submission — generic failure', () => {
  it('shows a generic error state for 500 responses', async () => {
    server.use(
      http.post('/api/forms/:formId/submit', () =>
        HttpResponse.json({ message: 'Internal server error' }, { status: 500 }),
      ),
    )

    const wrapper = mountRenderer()
    await flushPromises()

    await submitWithValidPayload(wrapper)
    await flushPromises()

    expect(wrapper.text()).toContain('Submission failed')
  })
})
