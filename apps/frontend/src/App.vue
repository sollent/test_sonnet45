<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.store'
import { useLoaderStore } from '@/stores/loader.store'
import { useOfflineDetection } from '@/composables/useOfflineDetection'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import GlobalLanguageSwitcher from '@/components/ui/GlobalLanguageSwitcher.vue'
import AppLoader from '@/components/AppLoader.vue'
import OfflineModal from '@/components/common/OfflineModal.vue'

const route = useRoute()
const authStore = useAuthStore()
const loaderStore = useLoaderStore()
const isAppLoaded = ref(false)

// Offline detection
const { isOnline, isModalVisible } = useOfflineDetection({
  showToast: true,
  showModal: true,
  checkServiceWorker: true
})

// Проверяем, нужно ли пропустить лоадер (при разлогине) - SSR-safe
if (typeof sessionStorage !== 'undefined') {
  const skipInitialLoader = sessionStorage.getItem('skip_loader') === 'true'
  if (skipInitialLoader) {
    sessionStorage.removeItem('skip_loader')
    isAppLoaded.value = true
  }
}

onMounted(async () => {
  await authStore.initializeAuth()

  // Dispatch event for prerenderer to know page is ready
  document.dispatchEvent(new Event('render-event'))
})

function handleLoaderComplete() {
  if (!isAppLoaded.value) {
    isAppLoaded.value = true
  }
  loaderStore.finish()
}

const shouldShowLoader = computed(() => !isAppLoaded.value || loaderStore.isVisible)

const loaderKey = computed(() => {
  if (!isAppLoaded.value) {
    return 'initial-loader'
  }
  return `dynamic-loader-${loaderStore.loaderKey}`
})

// Показывать language switcher только НЕ на главной, календаре и аналитике
const shouldShowLanguageSwitcher = computed(() => {
  const hiddenPaths = ['/dashboard', '/calendar', '/analytics']
  return !hiddenPaths.includes(route.path)
})
</script>

<template>
  <div id="app">
    <!-- App Loader -->
    <AppLoader
      v-if="shouldShowLoader"
      :key="loaderKey"
      @loaded="handleLoaderComplete"
    />
    
    <!-- Main App Content -->
    <template v-else>
    <Toast position="top-right" />
      <ConfirmDialog />
    <router-view v-slot="{ Component, route }">
      <transition name="fade" mode="out-in">
        <component :is="Component" :key="route.path" />
      </transition>
    </router-view>
    <!-- Global Language Switcher (скрыт на главной, календаре и аналитике) -->
    <GlobalLanguageSwitcher v-if="shouldShowLanguageSwitcher" />
    </template>

    <!-- Offline Modal - Always rendered -->
    <OfflineModal v-model="isModalVisible" />
  </div>
</template>

<style scoped>
#app {
  width: 100%;
  min-height: 100vh;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

