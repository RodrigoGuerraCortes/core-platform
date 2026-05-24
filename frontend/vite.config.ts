import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vuetify from 'vite-plugin-vuetify'
import { fileURLToPath, URL } from 'node:url'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    // Vuetify tree-shaking: auto-imports only used components
    vuetify({ autoImport: true }),
  ],

  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },

  server: {
    // Bind to all interfaces so the port is reachable from outside the
    // container. Has no effect when running on the host directly.
    host: '0.0.0.0',
    port: 5173,

    // HMR WebSocket must connect to the host machine's address, not the
    // container's internal hostname. The browser opens this WebSocket, so
    // it must be reachable from the developer's machine.
    hmr: {
      host: 'localhost',
    },

    // Proxy /api calls to the Laravel backend.
    //   - On host (no container): BACKEND_URL is unset → falls back to nginx's exposed port.
    //   - In Docker container:    BACKEND_URL=http://nginx:80 (set via docker-compose env).
    proxy: {
      // All /api/* requests are forwarded to the Laravel backend.
      '/api': {
        target: process.env.BACKEND_URL ?? 'http://localhost:8010',
        changeOrigin: true,
      },
      // Sanctum CSRF cookie endpoint — must be proxied so the browser receives
      // the XSRF-TOKEN cookie on the same origin as the SPA (localhost:5173).
      '/sanctum': {
        target: process.env.BACKEND_URL ?? 'http://localhost:8010',
        changeOrigin: true,
      },
    },
  },
})
