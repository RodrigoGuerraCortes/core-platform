import { config } from '@vue/test-utils'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { beforeAll, afterEach, afterAll } from 'vitest'
import { server } from '../modules/dynamic-forms/tests/mocks/server'

// ─── Vuetify global plugin ─────────────────────────────────────────────────────
// All Vuetify components are registered globally so v-* elements resolve in tests.
// server.deps.inline: ['vuetify'] in vitest.config.ts ensures Vite handles CSS
// imports from vuetify/components instead of crashing Node's ESM loader.
const vuetify = createVuetify({ components, directives })
config.global.plugins = [vuetify]

// jsdom doesn't implement ResizeObserver — Vuetify VTextarea and VProgressCircular need it.
class ResizeObserverStub {
  observe() {}
  unobserve() {}
  disconnect() {}
}
window.ResizeObserver = window.ResizeObserver ?? ResizeObserverStub

// jsdom doesn't implement matchMedia — Vuetify needs it.
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: (query: string) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: () => {},
    removeListener: () => {},
    addEventListener: () => {},
    removeEventListener: () => {},
    dispatchEvent: () => false,
  }),
})

// ─── MSW server lifecycle ──────────────────────────────────────────────────────
beforeAll(() => server.listen({ onUnhandledRequest: 'error' }))
afterEach(() => server.resetHandlers())
afterAll(() => server.close())
