import { useToast as usePrimeToast } from 'primevue/usetoast'

export function useToast() {
  const toast = usePrimeToast()

  function showSuccess(message: string, title = 'Success'): void {
    toast.add({
      severity: 'success',
      summary: title,
      detail: message,
      life: 3000
    })
  }

  function showError(message: string, title = 'Error'): void {
    toast.add({
      severity: 'error',
      summary: title,
      detail: message,
      life: 5000
    })
  }

  function showInfo(message: string, title = 'Info'): void {
    toast.add({
      severity: 'info',
      summary: title,
      detail: message,
      life: 3000
    })
  }

  function showWarn(message: string, title = 'Warning'): void {
    toast.add({
      severity: 'warn',
      summary: title,
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

