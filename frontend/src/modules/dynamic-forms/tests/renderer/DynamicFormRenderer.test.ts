import { describe, it, expect, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { VueQueryPlugin, QueryClient } from '@tanstack/vue-query'
import DynamicFormRenderer from '../../components/renderer/DynamicFormRenderer.vue'
import { basicSchema, mockVersion, schemaWithSection } from '../fixtures/schemas'

// ─── Stub DynamicFieldRenderer ─────────────────────────────────────────────────
// The renderer orchestrates layout and state transitions — not field implementations.
// Using a synchronous stub avoids flakiness from defineAsyncComponent timing in jsdom.
vi.mock('../../components/renderer/DynamicFieldRenderer.vue', () => ({
  default: {
    name: 'DynamicFieldRendererStub',
    props: ['field', 'modelValue', 'error', 'disabled'],
    emits: ['update:modelValue'],
    template: '<div data-testid="field-stub">{{ field.label }}</div>',
  },
}))

// ─── Mount helper ──────────────────────────────────────────────────────────────

function mountRenderer(overrides: Partial<typeof mockVersion> = {}) {
  const version = { ...mockVersion, ...overrides }
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })

  return mount(DynamicFormRenderer, {
    props: { formId: 1, version },
    global: {
      plugins: [createPinia(), [VueQueryPlugin, { queryClient }]],
    },
  })
}

// ─── Tests ─────────────────────────────────────────────────────────────────────

describe('DynamicFormRenderer', () => {
  it('renders the form title from the schema', async () => {
    const wrapper = mountRenderer()
    await flushPromises()
    expect(wrapper.text()).toContain(basicSchema.title)
  })

  it('renders all renderable fields from the schema', async () => {
    const wrapper = mountRenderer()
    await flushPromises()
    // All non-file field labels must appear via the synchronous stub.
    for (const field of basicSchema.fields) {
      if (field.type === 'file') continue
      expect(wrapper.text()).toContain(field.label)
    }
  })

  it('renders section field labels', async () => {
    const wrapper = mountRenderer({ schema: schemaWithSection })
    await flushPromises()
    expect(wrapper.text()).toContain('Personal Info')
    expect(wrapper.text()).toContain('Contact Info')
  })

  it('renders a submit button', async () => {
    const wrapper = mountRenderer()
    await flushPromises()
    expect(wrapper.find('[type="submit"]').exists()).toBe(true)
  })

  it('shows success state after successful submission', async () => {
    const wrapper = mountRenderer()
    await flushPromises()

    // Populate required fields before submitting to pass client-side validation.
    // DynamicFormRenderer manages its own payload state internally.
    // We trigger submit directly via the form component's handleSubmit.
    // Access internal state via wrapper.vm and set values.
    const vm = wrapper.vm as unknown as { payload: Record<string, unknown>; handleSubmit: () => Promise<void> }
    vm.payload.full_name = 'Alice'
    vm.payload.email = 'alice@example.com'
    vm.payload.country = 'us'

    await vm.handleSubmit()
    await flushPromises()

    expect(wrapper.text()).toContain('Thank you')
  })
})
