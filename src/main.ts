import { createApp } from 'vue'
import App from './App.vue'
import router from './router.ts'

import '@nextcloud/dialogs/style.css'

const app = createApp(App)
app.use(router)
app.mount('#health')
