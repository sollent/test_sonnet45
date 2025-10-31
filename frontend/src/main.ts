import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import Ripple from 'primevue/ripple'

import App from './App.vue'
import router from './router'
import { i18n } from './i18n'

// PrimeVue CSS (order is important!)
import 'primeicons/primeicons.css'
import 'primevue/resources/themes/lara-light-blue/theme.css'

// AOS Animation Library
import 'aos/dist/aos.css'

// Custom styles (after PrimeVue to allow overrides)
import './assets/styles/main.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(i18n)
app.use(PrimeVue, {
  ripple: true
})
app.use(ToastService)
app.use(ConfirmationService)

app.directive('ripple', Ripple)

app.mount('#app')

