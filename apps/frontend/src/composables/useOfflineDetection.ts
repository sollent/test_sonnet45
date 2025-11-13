import { ref, onMounted, onUnmounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useI18n } from 'vue-i18n'

export interface OfflineOptions {
  showToast?: boolean
  showModal?: boolean
  checkServiceWorker?: boolean
}

export function useOfflineDetection(options: OfflineOptions = {}) {
  const {
    showToast = true,
    showModal = true,
    checkServiceWorker = true
  } = options

  const isOnline = ref(navigator.onLine)
  const isModalVisible = ref(false)
  const failedRequests = ref(new Set<string>())
  const toast = useToast()
  const { t } = useI18n()

  // Track failed requests
  const trackFailedRequest = (url: string) => {
    failedRequests.value.add(url)

    // Show modal on first failed request
    if (showModal && !isModalVisible.value && failedRequests.value.size > 0) {
      isModalVisible.value = true
    }
  }

  // Clear failed requests when back online
  const clearFailedRequests = () => {
    failedRequests.value.clear()
    isModalVisible.value = false
  }

  // Network status handlers
  const handleOnline = () => {
    isOnline.value = true

    if (showToast) {
      toast.add({
        severity: 'success',
        summary: t('offline.toast.connectionRestored'),
        detail: t('offline.toast.backOnline'),
        life: 3000
      })
    }

    // Clear failed requests after a short delay
    setTimeout(clearFailedRequests, 1000)
  }

  const handleOffline = () => {
    isOnline.value = false

    if (showToast) {
      toast.add({
        severity: 'warn',
        summary: t('offline.toast.connectionLost'),
        detail: t('offline.toast.workingOffline'),
        life: 5000
      })
    }

    // Show modal when going offline
    if (showModal && !isModalVisible.value) {
      isModalVisible.value = true
    }
  }

  // Service Worker message handler
  const handleServiceWorkerMessage = (event: MessageEvent) => {
    if (event.data && event.data.type === 'NETWORK_ERROR') {
      trackFailedRequest(event.data.url)
    }
  }

  // Intercept fetch failures
  const interceptFetch = () => {
    const originalFetch = window.fetch

    window.fetch = async (...args) => {
      try {
        const response = await originalFetch(...args)
        return response
      } catch (error) {
        // Check if it's a network error and we're offline
        if (!navigator.onLine) {
          const url = typeof args[0] === 'string' ? args[0] : args[0].url
          trackFailedRequest(url)
        }
        throw error
      }
    }
  }

  onMounted(() => {
    // Update initial status
    isOnline.value = navigator.onLine

    // Add event listeners
    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)

    // Listen to Service Worker messages
    if (checkServiceWorker && 'serviceWorker' in navigator) {
      navigator.serviceWorker.addEventListener('message', handleServiceWorkerMessage)
    }

    // Intercept fetch for offline detection
    interceptFetch()
  })

  onUnmounted(() => {
    window.removeEventListener('online', handleOnline)
    window.removeEventListener('offline', handleOffline)

    if (checkServiceWorker && 'serviceWorker' in navigator) {
      navigator.serviceWorker.removeEventListener('message', handleServiceWorkerMessage)
    }
  })

  return {
    isOnline,
    isModalVisible,
    failedRequests,
    trackFailedRequest,
    clearFailedRequests
  }
}