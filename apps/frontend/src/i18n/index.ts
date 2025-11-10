import { createI18n } from 'vue-i18n'
import { getCurrentInstance } from 'vue'
import en from './locales/en'
import ru from './locales/ru'
import { primevueLocaleRu, primevueLocaleEn } from './primevue-locales'

// Get locale from localStorage or browser default
export function getInitialLocale(): string {
  const storedLocale = localStorage.getItem('locale')
  if (storedLocale && ['en', 'ru'].includes(storedLocale)) {
    return storedLocale
  }

  // Try to get from browser language
  const browserLang = navigator.language.toLowerCase()
  if (browserLang.startsWith('ru')) {
    return 'ru'
  }

  return 'en' // Default to English
}

export const i18n = createI18n({
  legacy: false, // Use Composition API
  locale: getInitialLocale(),
  fallbackLocale: 'en',
  messages: {
    en,
    ru,
  },
  globalInjection: true,
})

// Export helper to change locale
export function setLocale(locale: 'en' | 'ru') {
  i18n.global.locale.value = locale
  localStorage.setItem('locale', locale)
  
  // Update PrimeVue locale
  updatePrimeVueLocale(locale)
}

// Update PrimeVue locale dynamically
// Note: In PrimeVue 3, locale is set at plugin initialization
// For dynamic updates, we need to access the PrimeVue instance
let primevueInstance: any = null

export function setPrimeVueInstance(instance: any) {
  primevueInstance = instance
}

function updatePrimeVueLocale(locale: 'en' | 'ru') {
  try {
    const newLocale = locale === 'ru' ? primevueLocaleRu : primevueLocaleEn
    
    // Try to update via current instance
    const instance = getCurrentInstance()
    if (instance) {
      const app = instance.appContext.app
      if (app && app.config && app.config.globalProperties) {
        const primevue = app.config.globalProperties.$primevue
        if (primevue && primevue.config) {
          primevue.config.locale = newLocale
          return
        }
      }
    }
    
    // Try stored instance
    if (primevueInstance && primevueInstance.config) {
      primevueInstance.config.locale = newLocale
      return
    }
    
    // Fallback: update via window if available (for debugging)
    if (typeof window !== 'undefined' && (window as any).PrimeVue) {
      (window as any).PrimeVue.config.locale = newLocale
    }
  } catch (error) {
    // If update fails, page reload will handle it (LanguageSwitcher uses reload)
    console.warn('Failed to update PrimeVue locale dynamically, will reload on next change:', error)
  }
}

// Export helper to get current locale
export function getCurrentLocale(): string {
  return i18n.global.locale.value
}

