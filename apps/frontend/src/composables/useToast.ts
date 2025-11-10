import { useToast as usePrimeToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'

export function useToast() {
  const toast = usePrimeToast()
  const { t } = useI18n()

  function showSuccess(message: string, title?: string): void {
    const summary = title ?? (t('common.success') || 'Success')
    toast.add({
      severity: 'success',
      summary,
      detail: message,
      life: 3000
    })
  }

  function showError(message: string, title?: string): void {
    const summary = title ?? (t('common.error') || 'Error')
    toast.add({
      severity: 'error',
      summary,
      detail: message,
      life: 5000
    })
  }

  function showInfo(message: string, title?: string): void {
    const summary = title ?? (t('common.info') || 'Info')
    toast.add({
      severity: 'info',
      summary,
      detail: message,
      life: 3000
    })
  }

  function showWarn(message: string, title?: string): void {
    const summary = title ?? (t('common.warning') || 'Warning')
    toast.add({
      severity: 'warn',
      summary,
      detail: message,
      life: 4000
    })
  }

  return {
    showSuccess,
    showError,
    showInfo,
    showWarn
  }
}

