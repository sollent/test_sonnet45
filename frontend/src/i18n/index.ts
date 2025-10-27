import { createI18n } from 'vue-i18n'
import en from './locales/en'
import ru from './locales/ru'

// Get locale from localStorage or browser default
function getInitialLocale(): string {
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
}

// Export helper to get current locale
export function getCurrentLocale(): string {
  return i18n.global.locale.value
}

