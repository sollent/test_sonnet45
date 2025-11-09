<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { setLocale } from '@/i18n'
import Button from 'primevue/button'
import Menu from 'primevue/menu'
import type { MenuItem } from 'primevue/menuitem'

const { locale } = useI18n()
const menu = ref()

const currentFlag = computed(() => {
  return locale.value === 'ru' ? '🇷🇺' : '🇬🇧'
})

const currentLangName = computed(() => {
  return locale.value === 'ru' ? 'Русский' : 'English'
})

const items = computed<MenuItem[]>(() => [
  {
    label: '🇬🇧 English',
    command: () => changeLanguage('en'),
    class: locale.value === 'en' ? 'font-semibold' : '',
  },
  {
    label: '🇷🇺 Русский',
    command: () => changeLanguage('ru'),
    class: locale.value === 'ru' ? 'font-semibold' : '',
  },
])

function toggle(event: Event) {
  menu.value.toggle(event)
}

function changeLanguage(newLocale: 'en' | 'ru') {
  if (newLocale !== locale.value) {
    setLocale(newLocale)
    // Reload page to apply translations everywhere
    window.location.reload()
  }
}
</script>

<template>
  <div class="language-switcher">
    <Button
      :label="`${currentFlag} ${currentLangName}`"
      icon="pi pi-angle-down"
      icon-pos="right"
      severity="secondary"
      text
      @click="toggle"
      aria-haspopup="true"
      :aria-controls="`language-menu`"
    />
    <Menu ref="menu" id="language-menu" :model="items" :popup="true" />
  </div>
</template>

<style scoped>
.language-switcher {
  display: inline-block;
}

:deep(.p-button) {
  font-weight: 500;
  transition: all 0.2s;
}

:deep(.p-button:hover) {
  background: rgba(var(--primary-500-rgb), 0.08) !important;
}
</style>

