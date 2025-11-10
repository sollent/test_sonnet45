import { computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { primevueLocaleRu, primevueLocaleEn } from '@/i18n/primevue-locales'

/**
 * Composable to sync PrimeVue Calendar locale with vue-i18n
 * Use this in components that use Calendar component
 */
export function usePrimeVueLocale() {
  const { locale } = useI18n()

  const calendarLocale = computed(() => {
    return locale.value === 'ru' ? primevueLocaleRu : primevueLocaleEn
  })

  return {
    calendarLocale
  }
}

