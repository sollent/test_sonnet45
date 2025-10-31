<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import GlobalLanguageSwitcher from '@/components/ui/GlobalLanguageSwitcher.vue'
import AppLoader from '@/components/AppLoader.vue'

const authStore = useAuthStore()
const isAppLoaded = ref(false)

onMounted(async () => {
  await authStore.initializeAuth()
})

function handleLoaderComplete() {
  isAppLoaded.value = true
}
</script>

<template>
  <div id="app">
    <!-- App Loader -->
    <AppLoader v-if="!isAppLoaded" @loaded="handleLoaderComplete" />
    
    <!-- Main App Content -->
    <template v-else>
      <Toast position="top-right" />
      <ConfirmDialog />
      <router-view v-slot="{ Component, route }">
        <transition name="fade" mode="out-in">
          <component :is="Component" :key="route.path" />
        </transition>
      </router-view>
      <!-- Global Language Switcher -->
      <GlobalLanguageSwitcher />
    </template>
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

