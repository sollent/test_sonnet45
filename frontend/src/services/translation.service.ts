import { apiService } from './api.service'

export interface TranslationItem {
  value: string
  label: string
  color: string
}

export interface EnumTranslations {
  priorities: Record<string, TranslationItem>
  statuses: Record<string, TranslationItem>
}

class TranslationService {
  private cachedTranslations: EnumTranslations | null = null
  private currentLocale: string | null = null

  /**
   * Get all enum translations
   */
  async getEnumTranslations(locale?: string): Promise<EnumTranslations> {
    const effectiveLocale = locale || localStorage.getItem('locale') || 'en'
    
    // Return cached translations if locale hasn't changed
    if (this.cachedTranslations && this.currentLocale === effectiveLocale) {
      return this.cachedTranslations
    }

    try {
      const response = await apiService.get<EnumTranslations>(
        `/translations/enums?locale=${effectiveLocale}`
      )
      
      this.cachedTranslations = response.data
      this.currentLocale = effectiveLocale
      
      return response.data
    } catch (error) {
      console.error('Failed to fetch enum translations:', error)
      // Return default translations as fallback
      return this.getDefaultTranslations()
    }
  }

  /**
   * Get priority translations
   */
  async getPriorityTranslations(locale?: string): Promise<Record<string, TranslationItem>> {
    const translations = await this.getEnumTranslations(locale)
    return translations.priorities
  }

  /**
   * Get status translations
   */
  async getStatusTranslations(locale?: string): Promise<Record<string, TranslationItem>> {
    const translations = await this.getEnumTranslations(locale)
    return translations.statuses
  }

  /**
   * Clear cached translations (call when locale changes)
   */
  clearCache(): void {
    this.cachedTranslations = null
    this.currentLocale = null
  }

  /**
   * Get default translations as fallback
   */
  private getDefaultTranslations(): EnumTranslations {
    return {
      priorities: {
        low: { value: 'low', label: 'Low', color: '#94a3b8' },
        medium: { value: 'medium', label: 'Medium', color: '#3b82f6' },
        high: { value: 'high', label: 'High', color: '#f59e0b' },
        urgent: { value: 'urgent', label: 'Urgent', color: '#ef4444' }
      },
      statuses: {
        pending: { value: 'pending', label: 'Pending', color: '#94a3b8' },
        in_progress: { value: 'in_progress', label: 'In Progress', color: '#3b82f6' },
        completed: { value: 'completed', label: 'Completed', color: '#10b981' },
        cancelled: { value: 'cancelled', label: 'Cancelled', color: '#ef4444' },
        archived: { value: 'archived', label: 'Archived', color: '#6b7280' }
      }
    }
  }
}

export const translationService = new TranslationService()


