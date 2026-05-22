import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './app/App.vue'
import router from './router'
import { vuetify } from './plugins/vuetify'
import { installQuery } from './plugins/query'

const app = createApp(App)

// Order matters: Pinia must be installed before any store is accessed.
app.use(createPinia())
app.use(router)
app.use(vuetify)
installQuery(app)

app.mount('#app')
