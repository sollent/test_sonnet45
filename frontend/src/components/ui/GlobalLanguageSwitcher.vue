<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { setLocale } from '@/i18n'
import { useToast } from '@/composables/useToast'

const { locale } = useI18n()
const { showSuccess } = useToast()

interface LanguageOption {
  locale: 'ru' | 'en'
  flag: string
  label: string
  shortLabel: string
}

const languages: LanguageOption[] = [
  {
    locale: 'ru',
    flag: '🇷🇺',
    label: 'Русский',
    shortLabel: 'RU'
  },
  {
    locale: 'en',
    flag: '🇬🇧',
    label: 'English',
    shortLabel: 'EN'
  }
]

const currentLanguage = computed(() => {
  return languages.find(lang => lang.locale === locale.value) || languages[0]
})

function switchLanguage(newLocale: 'ru' | 'en') {
  setLocale(newLocale)
  
  const newLang = languages.find(l => l.locale === newLocale)
  if (newLang) {
    const message = newLocale === 'ru' 
      ? `Язык изменен на ${newLang.label}` 
      : `Language changed to ${newLang.label}`
    const title = newLocale === 'ru' ? 'Успешно' : 'Success'
    
    showSuccess(message, title)
  }
}

// State for custom menu
const isOpen = ref(false)

function toggleMenu() {
  isOpen.value = !isOpen.value
}

function selectLanguage(lang: LanguageOption) {
  if (lang.locale !== locale.value) {
    switchLanguage(lang.locale)
  }
  isOpen.value = false
}
</script>

<template>
  <div class="global-language-switcher">
    <!-- Custom implementation without SpeedDial -->
    <div class="language-menu-container" :class="{ 'is-open': isOpen }">
      <!-- Language options -->
      <transition-group name="menu-item" tag="div" class="language-options">
        <template v-for="lang in languages" :key="lang.locale">
          <button 
            v-if="lang.locale !== locale"
            v-show="isOpen"
            class="language-option"
            @click="selectLanguage(lang)"
            :aria-label="`Switch to ${lang.label}`"
          >
            <span class="option-flag">{{ lang.flag }}</span>
            <span class="option-label">{{ lang.shortLabel }}</span>
          </button>
        </template>
      </transition-group>
      
      <!-- Main button -->
      <button
        class="language-main-button"
        :class="{ 'is-open': isOpen }"
        @click="toggleMenu"
        aria-label="Language selector"
      >
        <span v-if="currentLanguage" class="main-flag">{{ currentLanguage.flag }}</span>
        <transition name="fade" mode="out-in">
          <span v-if="currentLanguage" :key="currentLanguage.locale" class="main-label">
            {{ currentLanguage.shortLabel }}
          </span>
        </transition>
      </button>
    </div>

    <!-- Background overlay -->
    <transition name="fade">
      <div 
        v-if="isOpen" 
        class="menu-overlay"
        @click="isOpen = false"
      />
    </transition>
  </div>
</template>

<style scoped>
.global-language-switcher {
  position: fixed;
  right: 1.5rem;
  bottom: 1.5rem;
  z-index: 9999;
}

.language-menu-container {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

/* Main button */
.language-main-button {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  background: linear-gradient(135deg, var(--p-primary-500), var(--p-primary-600));
  border: none;
  cursor: pointer;
  color: white;
  box-shadow: 
    0 4px 20px 0 rgba(0, 0, 0, 0.2),
    0 2px 8px 0 rgba(0, 0, 0, 0.15),
    inset 0 1px 0 rgba(255, 255, 255, 0.2);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  outline: none;
}

.language-main-button::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at center, transparent 30%, rgba(255, 255, 255, 0.1));
  opacity: 0;
  transition: opacity 0.3s ease;
}

.language-main-button:hover::before {
  opacity: 1;
}

.language-main-button:hover {
  transform: translateY(-2px) scale(1.05);
  box-shadow: 
    0 8px 28px 0 rgba(0, 0, 0, 0.25),
    0 4px 12px 0 rgba(0, 0, 0, 0.18),
    inset 0 1px 0 rgba(255, 255, 255, 0.3);
}

.language-main-button:active {
  transform: scale(0.98);
}

.language-main-button.is-open {
  background: linear-gradient(135deg, var(--p-primary-600), var(--p-primary-700));
  transform: scale(1.05);
  box-shadow: 
    0 12px 32px 0 rgba(0, 0, 0, 0.3),
    0 6px 16px 0 rgba(0, 0, 0, 0.22),
    inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.main-flag {
  font-size: 2rem;
  line-height: 1;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.main-label {
  position: absolute;
  bottom: 4px;
  right: 4px;
  background: white;
  color: var(--p-primary-600);
  font-size: 0.625rem;
  font-weight: 700;
  padding: 2px 4px;
  border-radius: 4px;
  line-height: 1;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

/* Language options */
.language-options {
  position: absolute;
  bottom: calc(100% + 0.75rem);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  align-items: center;
}

.language-option {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  background: white;
  border: 2px solid var(--p-primary-100);
  cursor: pointer;
  box-shadow: 
    0 4px 16px 0 rgba(0, 0, 0, 0.15),
    0 2px 6px 0 rgba(0, 0, 0, 0.1);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  outline: none;
  position: relative;
  overflow: hidden;
}

.language-option::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: var(--p-primary-50);
  transform: translate(-50%, -50%);
  transition: width 0.4s ease, height 0.4s ease;
}

.language-option:hover::before {
  width: 120%;
  height: 120%;
}

.language-option:hover {
  transform: translateY(-4px) scale(1.08);
  border-color: var(--p-primary-300);
  box-shadow: 
    0 8px 24px 0 rgba(0, 0, 0, 0.2),
    0 4px 10px 0 rgba(0, 0, 0, 0.15);
}

.language-option:active {
  transform: scale(0.95);
}

.option-flag {
  font-size: 1.5rem;
  line-height: 1;
  position: relative;
  z-index: 1;
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.15));
}

.option-label {
  font-size: 0.625rem;
  font-weight: 600;
  color: var(--p-gray-700);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  position: relative;
  z-index: 1;
}

/* Menu overlay */
.menu-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(2px);
  z-index: -1;
}

/* Animations */
.menu-item-enter-active {
  animation: bounceIn 0.5s ease;
}

.menu-item-leave-active {
  animation: bounceOut 0.3s ease;
}

@keyframes bounceIn {
  0% {
    opacity: 0;
    transform: scale(0.3) translateY(20px);
  }
  50% {
    transform: scale(1.05) translateY(-5px);
  }
  100% {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

@keyframes bounceOut {
  0% {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
  100% {
    opacity: 0;
    transform: scale(0.3) translateY(20px);
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .language-option {
    background: var(--surface-800);
    border-color: var(--surface-700);
  }
  
  .language-option:hover {
    border-color: var(--p-primary-400);
  }
  
  .option-label {
    color: var(--text-color);
  }
  
  .main-label {
    background: var(--surface-800);
    color: var(--p-primary-300);
  }
  
  .menu-overlay {
    background: rgba(0, 0, 0, 0.6);
  }
}

/* Mobile adjustments */
@media (max-width: 640px) {
  .global-language-switcher {
    right: 1rem;
    bottom: 1rem;
  }
  
  .language-main-button {
    width: 56px;
    height: 56px;
  }
  
  .main-flag {
    font-size: 1.75rem;
  }
  
  .language-option {
    width: 48px;
    height: 48px;
  }
  
  .option-flag {
    font-size: 1.25rem;
  }
  
  .option-label {
    font-size: 0.5625rem;
  }
}

/* Pulse effect on first load */
@keyframes initialPulse {
  0%, 100% {
    box-shadow: 
      0 4px 20px 0 rgba(0, 0, 0, 0.2),
      0 2px 8px 0 rgba(0, 0, 0, 0.15),
      0 0 0 0 rgba(var(--p-primary-500), 0.4);
  }
  50% {
    box-shadow: 
      0 4px 20px 0 rgba(0, 0, 0, 0.2),
      0 2px 8px 0 rgba(0, 0, 0, 0.15),
      0 0 0 15px rgba(var(--p-primary-500), 0);
  }
}

.language-main-button {
  animation: initialPulse 2s ease 0.5s;
}
</style>