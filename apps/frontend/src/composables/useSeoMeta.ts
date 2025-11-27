import { useHead } from '@unhead/vue'

interface SeoMetaOptions {
  title: string
  description: string
  keywords?: string
  ogImage?: string
  ogType?: string
  canonical?: string
}

/**
 * Composable for setting SEO meta tags on landing pages
 */
export function useSeoMeta(options: SeoMetaOptions) {
  const {
    title,
    description,
    keywords = 'TaskFlow, управление задачами, AI помощник, голосовое управление, Telegram бот, продуктивность',
    ogImage = '/og-image.png',
    ogType = 'website',
    canonical
  } = options

  const fullTitle = title.includes('TaskFlow') ? title : `${title} | TaskFlow`
  const baseUrl = 'https://taskflow.ru'
  const canonicalUrl = canonical ? `${baseUrl}${canonical}` : undefined

  useHead({
    title: fullTitle,
    meta: [
      { name: 'description', content: description },
      { name: 'keywords', content: keywords },

      // Open Graph
      { property: 'og:title', content: fullTitle },
      { property: 'og:description', content: description },
      { property: 'og:image', content: `${baseUrl}${ogImage}` },
      { property: 'og:type', content: ogType },
      { property: 'og:site_name', content: 'TaskFlow' },
      { property: 'og:locale', content: 'ru_RU' },

      // Twitter Card
      { name: 'twitter:card', content: 'summary_large_image' },
      { name: 'twitter:title', content: fullTitle },
      { name: 'twitter:description', content: description },
      { name: 'twitter:image', content: `${baseUrl}${ogImage}` },

      // Additional SEO
      { name: 'robots', content: 'index, follow' },
      { name: 'author', content: 'TaskFlow' },
    ],
    link: canonicalUrl ? [
      { rel: 'canonical', href: canonicalUrl }
    ] : []
  })
}

// Predefined SEO configs for landing pages
export const seoConfigs = {
  home: {
    title: 'TaskFlow - Умная система управления задачами с AI и голосовым управлением',
    description: 'TaskFlow — умный помощник для управления задачами с голосовым управлением, AI-парсингом команд и Telegram интеграцией. Начните бесплатно!',
    canonical: '/'
  },
  about: {
    title: 'О нас',
    description: 'Узнайте больше о TaskFlow — команде, миссии и технологиях, которые делают управление задачами простым и эффективным.',
    canonical: '/about'
  },
  voiceControl: {
    title: 'Голосовое управление задачами',
    description: 'Создавайте и управляйте задачами голосом на русском языке. AI понимает контекст, исправляет ошибки и устанавливает правильные даты.',
    canonical: '/features/voice-control'
  },
  telegram: {
    title: 'Telegram интеграция',
    description: 'Управляйте задачами прямо в Telegram. Превратите хаос сообщений в структурированный список дел с дедлайнами.',
    canonical: '/features/telegram'
  },
  aiAssistant: {
    title: 'AI-помощник',
    description: 'Умный AI-помощник для парсинга команд, автоматизации рутины и предсказаний на основе ваших привычек.',
    canonical: '/features/ai-assistant'
  },
  fileProcessing: {
    title: 'Обработка файлов',
    description: 'Загружайте PDF, Word или изображения — AI проанализирует содержимое и создаст структурированные задачи.',
    canonical: '/features/file-processing'
  },
  smartReminders: {
    title: 'Умные напоминания',
    description: 'Контекстные уведомления, повторяющиеся задачи и геолокационные триггеры для максимальной продуктивности.',
    canonical: '/features/smart-reminders'
  },
  webInterface: {
    title: 'Веб-интерфейс',
    description: 'Красивый и функциональный веб-интерфейс с календарем, графиками аналитики и drag & drop.',
    canonical: '/features/web-interface'
  },
  webSearch: {
    title: 'Поиск в интернете',
    description: 'AI-поиск актуальной информации в интернете для создания задач на основе найденных данных.',
    canonical: '/features/web-search'
  },
  compareTodoist: {
    title: 'TaskFlow vs Todoist - Сравнение',
    description: 'Подробное сравнение TaskFlow и Todoist. Узнайте, какой сервис лучше подходит для ваших задач.',
    canonical: '/compare/todoist'
  },
  compareTickTick: {
    title: 'TaskFlow vs TickTick - Сравнение',
    description: 'Сравнение TaskFlow и TickTick. Голосовое управление, AI и Telegram интеграция vs классический планировщик.',
    canonical: '/compare/ticktick'
  },
  compareAnyDo: {
    title: 'TaskFlow vs Any.do - Сравнение',
    description: 'Сравнение TaskFlow и Any.do. Узнайте преимущества AI-помощника и голосового управления.',
    canonical: '/compare/anydo'
  },
  compareGoogleKeep: {
    title: 'TaskFlow vs Google Keep - Сравнение',
    description: 'Сравнение TaskFlow и Google Keep. От простых заметок к умному управлению задачами с AI.',
    canonical: '/compare/google-keep'
  },
  compareThings3: {
    title: 'TaskFlow vs Things 3 - Сравнение',
    description: 'Сравнение TaskFlow и Things 3. Кроссплатформенность, AI и голосовое управление vs Apple-only.',
    canonical: '/compare/things3'
  },
  alternativesTodoist: {
    title: 'Альтернативы Todoist',
    description: 'Лучшие альтернативы Todoist в 2025. TaskFlow — AI-powered управление задачами с голосовым управлением.',
    canonical: '/alternatives/todoist'
  },
  alternativesGoogleKeep: {
    title: 'Альтернативы Google Keep',
    description: 'Лучшие альтернативы Google Keep для управления задачами. TaskFlow с AI, голосом и Telegram.',
    canonical: '/alternatives/google-keep'
  }
}
