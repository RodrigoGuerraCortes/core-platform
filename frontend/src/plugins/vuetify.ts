import { createVuetify } from 'vuetify'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import '@mdi/font/css/materialdesignicons.css'
// When using vite-plugin-vuetify with autoImport: true, individual component
// imports are handled by the plugin — do NOT import 'vuetify/styles' globally,
// only the base reset is needed.
import 'vuetify/styles'

export const vuetify = createVuetify({
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: { mdi },
  },
  theme: {
    defaultTheme: 'light',
  },
})
