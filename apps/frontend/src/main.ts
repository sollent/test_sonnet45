import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { i18n, getInitialLocale } from './i18n'
import { primevueLocaleRu, primevueLocaleEn } from './i18n/primevue-locales'
import { setupPrimeVue } from './plugins/primevue'

// PrimeVue CSS (order is important!)
import 'primeicons/primeicons.css'
import 'primevue/resources/themes/lara-light-blue/theme.css'

// AOS Animation Library
import 'aos/dist/aos.css'

// Custom styles (after PrimeVue to allow overrides)
import './assets/styles/main.css'

// Get initial locale for PrimeVue
const initialLocale = getInitialLocale()
const primevueLocale = initialLocale === 'ru' ? primevueLocaleRu : primevueLocaleEn

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(i18n)

// Setup PrimeVue with tree-shaking (imports only used components)
setupPrimeVue(app, primevueLocale)

app.mount('#app')

