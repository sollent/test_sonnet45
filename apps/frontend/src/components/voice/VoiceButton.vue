<script setup lang="ts">
import { computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import Button from 'primevue/button'
import ProgressSpinner from 'primevue/progressspinner'
import { useVoiceRecording } from '@/composables/useVoiceRecording'
import { useToast } from '@/composables/useToast'
import type { VoiceCommandResponse } from '@/types/voice.types'

/**
 * Props
 */
interface Props {
  /** Язык для распознавания голоса */
  language?: 'ru' | 'en' | 'uk'

  /** Размер кнопки */
  size?: 'small' | 'medium' | 'large'

  /** Показывать текстовую метку */
  showLabel?: boolean

  /** Кастомная иконка микрофона */
  icon?: string

  /** Отключить кнопку */
  disabled?: boolean
}

/**
 * Emits
 */
interface Emits {
  /** Событие при успешной отправке команды */
  (e: 'command-submitted', response: VoiceCommandResponse): void

  /** Событие при начале записи */
  (e: 'recording-started'): void

  /** Событие при остановке записи */
  (e: 'recording-stopped'): void

  /** Событие при ошибке */
  (e: 'error', error: string): void
}

const props = withDefaults(defineProps<Props>(), {
  language: 'ru',
  size: 'medium',
  showLabel: false, // Убрали label - только иконка
  icon: 'pi pi-microphone',
  disabled: false
})

const emit = defineEmits<Emits>()

// Composables
const { t } = useI18n()
const { showSuccess, showError } = useToast()

// Инициализация записи
const recording = useVoiceRecording({
  language: props.language,
  maxDurationMs: 60000 // 60 секунд
})

// Computed свойства
const buttonLabel = computed(() => {
  if (!props.showLabel) return ''

  if (recording.isRecording.value) {
    return t('voice.recording')
  }
  if (recording.isProcessing.value) {
    return t('voice.processing')
  }
  if (recording.hasError.value) {
    return t('voice.error')
  }
  return t('voice.speak')
})

const buttonIcon = computed(() => {
  if (recording.isRecording.value) {
    return 'pi pi-stop-circle'
  }
  if (recording.isProcessing.value) {
    return '' // Будет показан спиннер
  }
  return props.icon
})

const buttonClass = computed(() => {
  const baseClass = 'voice-button'
  const stateClass = recording.isRecording.value ? 'recording' : ''
  const errorClass = recording.hasError.value ? 'error' : ''
  const sizeClass = `voice-button-${props.size}`

  return [baseClass, stateClass, errorClass, sizeClass].filter(Boolean).join(' ')
})

const buttonSeverity = computed(() => {
  if (recording.hasError.value) return 'danger'
  if (recording.isRecording.value) return 'danger'
  if (recording.isProcessing.value) return 'secondary'
  return 'success'
})

const durationDisplay = computed(() => {
  if (!recording.isRecording.value) return ''
  return recording.formatDuration(recording.recordingDuration.value)
})

/**
 * Обработка клика по кнопке
 */
async function handleClick(): Promise<void> {
  // Если идет запись - остановить
  if (recording.isRecording.value) {
    // Ждём пока запись остановится и audioBlob будет готов
    await recording.stopRecording()
    emit('recording-stopped')

    // После остановки автоматически отправляем
    await submitRecording()
    return
  }

  // Если есть ошибка - сбросить
  if (recording.hasError.value) {
    recording.cancelRecording()
    return
  }

  // Начать новую запись
  await startRecording()
}

/**
 * Начать запись
 */
async function startRecording(): Promise<void> {
  try {
    await recording.startRecording()
    emit('recording-started')
    showSuccess(t('voice.recording_started'))
  } catch (error: any) {
    const errorMessage = error.message || t('voice.recording_error')
    emit('error', errorMessage)
  }
}

/**
 * Отправить запись на сервер
 */
async function submitRecording(): Promise<void> {
  if (!recording.canSubmit.value) {
    return
  }

  try {
    const response = await recording.submitRecording()

    if (response) {
      showSuccess(t('voice.command_submitted'))
      emit('command-submitted', response)

      // Очистка после успешной отправки
      recording.cancelRecording()
    } else {
      throw new Error('Failed to submit recording')
    }
  } catch (error: any) {
    const errorMessage = error.message || t('voice.submission_error')
    showError(errorMessage)
    emit('error', errorMessage)
  }
}

/**
 * Проверка поддержки браузером
 */
onMounted(() => {
  if (!recording.isSupported()) {
    const errorMessage = t('voice.not_supported')
    showError(errorMessage)
    emit('error', errorMessage)
  }
})

/**
 * Отслеживание ошибок записи
 */
watch(() => recording.error.value, (newError) => {
  if (newError) {
    showError(newError)
    emit('error', newError)
  }
})

/**
 * Cleanup при unmount
 */
defineExpose({
  startRecording,
  stopRecording: recording.stopRecording,
  cancelRecording: recording.cancelRecording,
  isRecording: recording.isRecording
})
</script>

<template>
  <div class="voice-button-container">
    <Button
      :label="buttonLabel"
      :icon="buttonIcon"
      :severity="buttonSeverity"
      :disabled="disabled || recording.isProcessing.value || !recording.isSupported()"
      :class="buttonClass"
      rounded
      @click="handleClick"
    >
      <!-- Spinner при обработке -->
      <template v-if="recording.isProcessing.value" #icon>
        <ProgressSpinner
          style="width: 20px; height: 20px"
          strokeWidth="4"
        />
      </template>
    </Button>

    <!-- Индикатор длительности записи -->
    <Transition name="fade">
      <div v-if="recording.isRecording.value" class="duration-indicator">
        <span class="duration-text">{{ durationDisplay }}</span>
        <span class="recording-pulse"></span>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.voice-button-container {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
}

/* Базовые стили кнопки */
.voice-button {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
  border-radius: 50% !important;
  padding: 0 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

/* Размеры для круглой кнопки */
.voice-button-small {
  width: 48px !important;
  height: 48px !important;
  font-size: 1.25rem;
}

.voice-button-medium {
  width: 56px !important;
  height: 56px !important;
  font-size: 1.5rem;
}

.voice-button-large {
  width: 64px !important;
  height: 64px !important;
  font-size: 1.75rem;
}

/* Состояние записи */
.voice-button.recording {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
  }
  50% {
    box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
  }
}

/* Состояние ошибки */
.voice-button.error {
  animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97);
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
  20%, 40%, 60%, 80% { transform: translateX(5px); }
}

/* Индикатор длительности */
.duration-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 1.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #ef4444;
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.duration-text {
  font-variant-numeric: tabular-nums;
  min-width: 3ch;
}

/* Пульсирующий индикатор записи */
.recording-pulse {
  width: 8px;
  height: 8px;
  background: #ef4444;
  border-radius: 50%;
  animation: blink 1s ease-in-out infinite;
}

@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Responsive */
@media (max-width: 768px) {
  .voice-button-container {
    gap: 0.5rem;
  }

  .voice-button-medium {
    width: 52px !important;
    height: 52px !important;
    font-size: 1.375rem;
  }

  .voice-button-large {
    width: 56px !important;
    height: 56px !important;
    font-size: 1.5rem;
  }

  .duration-indicator {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .duration-indicator {
    background: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.4);
  }
}
</style>
