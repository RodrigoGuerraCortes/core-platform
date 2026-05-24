import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [vue()],

  test: {
    environment: 'jsdom',
    globals: true,
    // css:false suppresses CSS module processing. We also inline Vuetify so that
    // Vite's transform pipeline (not Node's ESM loader) handles its CSS imports.
    css: false,
    // Override VITE_API_BASE_URL so API calls in jsdom tests use relative paths.
    // The .env value (http://localhost:8010/api) targets the real backend which
    // is unreachable in tests; relative paths let MSW path-only handlers match.
    env: {
      VITE_API_BASE_URL: '/api',
    },
    server: {
      deps: {
        // Forces Vite — not Node — to resolve Vuetify. This lets vite-plugin-vuetify
        // stub out the .css imports that would otherwise crash Node's ESM loader.
        inline: ['vuetify', '@mdi/font'],
      },
    },
    setupFiles: ['./src/tests/setup.ts'],
  },

  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
})
