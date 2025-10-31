import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useLoaderStore = defineStore('loader', () => {
  const isVisible = ref(false)
  const loaderKey = ref(0)

  function show(): void {
    loaderKey.value += 1
    isVisible.value = true
  }

  function finish(): void {
    isVisible.value = false
  }

  function reset(): void {
    loaderKey.value = 0
    isVisible.value = false
  }

  return {
    isVisible,
    loaderKey,
    show,
    finish,
    reset
  }
})

