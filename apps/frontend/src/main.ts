import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createHead } from '@unhead/vue/client'

import App from './App.vue'
import router from './router'
import { i18n, getInitialLocale } from './i18n'
import { primevueLocaleRu, primevueLocaleEn } from './i18n/primevue-locales'
import { setupPrimeVue } from './plugins/primevue'

// PrimeVue CSS (order is important!)
import 'primeicons/primeicons.css'
import 'primevue/resources/themes/lara-light-blue/theme.css'

// Custom styles (after PrimeVue to allow overrides)
import './assets/styles/main.css'

// Note: AOS CSS is lazy-loaded in LandingPage.vue to reduce initial bundle size

// ============================================
// PWA Service Worker Registration
// ============================================
// VitePWA генерирует service worker автоматически (vite.config.ts),
// но его нужно явно зарегистрировать в коде приложения
import { registerSW } from 'virtual:pwa-register'

// Автоматическое обновление service worker (registerType: 'autoUpdate' в vite.config.ts)
// Service Worker будет обновляться автоматически при обнаружении новой версии
const updateSW = registerSW({
  onNeedRefresh() {
    // Вызывается когда доступна новая версия приложения
    // При registerType: 'autoUpdate' обновление происходит автоматически
    console.log('[PWA] New content available, updating...')
  },
  onOfflineReady() {
    // Вызывается когда приложение готово к работе offline
    console.log('[PWA] App ready to work offline')
  },
  onRegistered(registration) {
    // Вызывается при успешной регистрации service worker
    if (registration) {
      console.log('[PWA] Service Worker registered successfully')

      // Проверяем обновления каждые 60 минут
      setInterval(() => {
        registration.update()
      }, 60 * 60 * 1000) // 1 час
    }
  },
  onRegisterError(error) {
    // Вызывается при ошибке регистрации
    console.error('[PWA] Service Worker registration failed:', error)
  }
})

// Get initial locale for PrimeVue
const initialLocale = getInitialLocale()
const primevueLocale = initialLocale === 'ru' ? primevueLocaleRu : primevueLocaleEn

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(i18n)

// SEO meta tags management
const head = createHead()
app.use(head)

// Setup PrimeVue with tree-shaking (imports only used components)
setupPrimeVue(app, primevueLocale)

app.mount('#app')

