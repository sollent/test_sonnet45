import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { translationService, type EnumTranslations, type TranslationItem } from '@/services/translation.service'
import { TaskPriority, TaskStatus } from '@/types/task.types'

export function useEnumTranslations() {
  const { locale } = useI18n()
  const translations = ref<EnumTranslations | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Load translations
  const loadTranslations = async () => {
    isLoading.value = true
    error.value = null
    
    try {
      translations.value = await translationService.getEnumTranslations(locale.value)
    } catch (err) {
      error.value = 'Failed to load translations'
      console.error('Error loading translations:', err)
    } finally {
      isLoading.value = false
    }
  }

  // Watch for locale changes
  watch(locale, () => {
    translationService.clearCache()
    loadTranslations()
  })

  // Load initial translations
  loadTranslations()

  // Get priority label
  const getPriorityLabel = (priority: TaskPriority | string): string => {
    if (!translations.value?.priorities) return priority
    const key = typeof priority === 'string' ? priority.toLowerCase() : priority.toLowerCase()
    return translations.value.priorities[key]?.label || priority
  }

  // Get priority color
  const getPriorityColor = (priority: TaskPriority | string): string => {
    if (!translations.value?.priorities) {
      // Fallback colors
      const colors: Record<string, string> = {
        low: '#94a3b8',
        medium: '#3b82f6',
        high: '#f59e0b',
        urgent: '#ef4444'
      }
      const key = typeof priority === 'string' ? priority.toLowerCase() : priority.toLowerCase()
      return colors[key] || '#94a3b8'
    }
    const key = typeof priority === 'string' ? priority.toLowerCase() : priority.toLowerCase()
    return translations.value.priorities[key]?.color || '#94a3b8'
  }

  // Get status label
  const getStatusLabel = (status: TaskStatus | string): string => {
    if (!translations.value?.statuses) return status
    const key = typeof status === 'string' ? status.toLowerCase() : status.toLowerCase()
    return translations.value.statuses[key]?.label || status
  }

  // Get status color
  const getStatusColor = (status: TaskStatus | string): string => {
    if (!translations.value?.statuses) {
      // Fallback colors
      const colors: Record<string, string> = {
        pending: '#94a3b8',
        in_progress: '#3b82f6',
        completed: '#10b981',
        cancelled: '#ef4444',
        archived: '#6b7280'
      }
      const key = typeof status === 'string' ? status.toLowerCase() : status.toLowerCase()
      return colors[key] || '#94a3b8'
    }
    const key = typeof status === 'string' ? status.toLowerCase() : status.toLowerCase()
    return translations.value.statuses[key]?.color || '#94a3b8'
  }

  // Get all priority options for dropdowns
  const priorityOptions = computed(() => {
    if (!translations.value?.priorities) {
      return Object.values(TaskPriority).map(value => ({
        value,
        label: value,
        color: getPriorityColor(value)
      }))
    }
    return Object.values(translations.value.priorities)
  })

  // Get all status options for dropdowns
  const statusOptions = computed(() => {
    if (!translations.value?.statuses) {
      return Object.values(TaskStatus).map(value => ({
        value,
        label: value,
        color: getStatusColor(value)
      }))
    }
    return Object.values(translations.value.statuses)
  })

  return {
    translations,
    isLoading,
    error,
    getPriorityLabel,
    getPriorityColor,
    getStatusLabel,
    getStatusColor,
    priorityOptions,
    statusOptions,
    loadTranslations
  }
}

