/**
 * Composable для записи голоса через Web Audio API
 *
 * Предоставляет функциональность:
 * - Запись аудио через MediaRecorder
 * - Управление состоянием записи
 * - Загрузка записанного аудио на сервер
 * - Обработка ошибок и permissions
 */

import { ref, computed } from 'vue'
import { voiceService } from '@/services/voice.service'
import mediaService from '@/services/media.service'
import type { VoiceCommandResponse } from '@/types/voice.types'

/**
 * Состояния записи
 */
export type RecordingState = 'idle' | 'recording' | 'paused' | 'processing' | 'error'

/**
 * Настройки записи
 */
export interface RecordingOptions {
  /** Максимальная длительность записи в миллисекундах (по умолчанию 60 сек) */
  maxDurationMs?: number

  /** MIME тип для записи (по умолчанию auto-detect) */
  mimeType?: string

  /** Язык команды */
  language?: 'ru' | 'en' | 'uk'
}

/**
 * Composable для работы с голосовой записью
 */
export function useVoiceRecording(options: RecordingOptions = {}) {
  // Дефолтные настройки
  const config = {
    maxDurationMs: options.maxDurationMs ?? 60000, // 60 секунд
    language: options.language ?? 'ru'
  }

  // Состояние
  const recordingState = ref<RecordingState>('idle')
  const audioBlob = ref<Blob | null>(null)
  const audioUrl = ref<string | null>(null)
  const error = ref<string | null>(null)
  const recordingDuration = ref<number>(0) // в миллисекундах

  // MediaRecorder и stream
  let mediaRecorder: MediaRecorder | null = null
  let audioChunks: Blob[] = []
  let mediaStream: MediaStream | null = null
  let durationInterval: number | null = null
  let startTime: number = 0

  // Computed properties
  const isRecording = computed(() => recordingState.value === 'recording')
  const isPaused = computed(() => recordingState.value === 'paused')
  const isProcessing = computed(() => recordingState.value === 'processing')
  const hasError = computed(() => recordingState.value === 'error')
  const canSubmit = computed(() => audioBlob.value !== null && !isProcessing.value)

  /**
   * Определение поддерживаемого MIME типа для записи
   */
  function getSupportedMimeType(): string {
    const mimeTypes = [
      'audio/webm;codecs=opus',
      'audio/webm',
      'audio/ogg;codecs=opus',
      'audio/mp4',
      'audio/mpeg'
    ]

    // Если указан кастомный MIME тип, проверяем его
    if (options.mimeType && MediaRecorder.isTypeSupported(options.mimeType)) {
      return options.mimeType
    }

    // Ищем первый поддерживаемый
    for (const mimeType of mimeTypes) {
      if (MediaRecorder.isTypeSupported(mimeType)) {
        return mimeType
      }
    }

    // Fallback на дефолтный (браузер выберет сам)
    return ''
  }

  /**
   * Запуск записи
   */
  async function startRecording(): Promise<void> {
    try {
      // Сброс предыдущего состояния
      audioBlob.value = null
      audioUrl.value = null
      error.value = null
      audioChunks = []
      recordingDuration.value = 0

      // Запрос доступа к микрофону
      mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true })

      // Определяем MIME тип
      const mimeType = getSupportedMimeType()
      const recorderOptions = mimeType ? { mimeType } : undefined

      // Создаем MediaRecorder
      mediaRecorder = new MediaRecorder(mediaStream, recorderOptions)

      // Обработчик данных
      mediaRecorder.ondataavailable = (event: BlobEvent) => {
        if (event.data.size > 0) {
          audioChunks.push(event.data)
        }
      }

      // Обработчик остановки
      mediaRecorder.onstop = () => {
        // Создаем blob из chunks
        const mimeTypeUsed = mediaRecorder?.mimeType || 'audio/webm'
        audioBlob.value = new Blob(audioChunks, { type: mimeTypeUsed })

        // Создаем URL для воспроизведения
        if (audioUrl.value) {
          URL.revokeObjectURL(audioUrl.value)
        }
        audioUrl.value = URL.createObjectURL(audioBlob.value)

        // Очистка
        stopDurationTracking()
        cleanupStream()
      }

      // Обработчик ошибки
      mediaRecorder.onerror = (event: Event) => {
        const errorEvent = event as ErrorEvent
        error.value = `Recording error: ${errorEvent.message || 'Unknown error'}`
        recordingState.value = 'error'
        cleanupRecording()
      }

      // Начинаем запись
      mediaRecorder.start()
      recordingState.value = 'recording'
      startTime = Date.now()
      startDurationTracking()

      // Автоматическая остановка при достижении максимальной длительности
      setTimeout(() => {
        if (isRecording.value) {
          stopRecording()
        }
      }, config.maxDurationMs)

    } catch (err: any) {
      // Обработка ошибок доступа к микрофону
      if (err.name === 'NotAllowedError') {
        error.value = 'Microphone access denied. Please allow microphone access in browser settings.'
      } else if (err.name === 'NotFoundError') {
        error.value = 'No microphone found. Please connect a microphone and try again.'
      } else {
        error.value = `Failed to start recording: ${err.message || 'Unknown error'}`
      }
      recordingState.value = 'error'
      cleanupRecording()
    }
  }

  /**
   * Остановка записи
   * Возвращает Promise, который резолвится когда audioBlob готов
   */
  function stopRecording(): Promise<void> {
    return new Promise((resolve, reject) => {
      if (!mediaRecorder || recordingState.value !== 'recording') {
        resolve()
        return
      }

      try {
        // Сохраняем текущий onstop handler
        const originalOnStop = mediaRecorder.onstop

        // Переопределяем onstop handler для резолва Promise
        mediaRecorder.onstop = (event) => {
          // Вызываем оригинальный handler
          if (originalOnStop) {
            originalOnStop.call(mediaRecorder, event)
          }

          // Резолвим Promise - audioBlob теперь готов
          resolve()
        }

        // Останавливаем запись
        mediaRecorder.stop()
        recordingState.value = 'idle'
      } catch (err: any) {
        error.value = `Failed to stop recording: ${err.message || 'Unknown error'}`
        recordingState.value = 'error'
        cleanupRecording()
        reject(err)
      }
    })
  }

  /**
   * Пауза записи (если поддерживается)
   */
  function pauseRecording(): void {
    if (!mediaRecorder || recordingState.value !== 'recording') {
      return
    }

    try {
      if (mediaRecorder.state === 'recording') {
        mediaRecorder.pause()
        recordingState.value = 'paused'
        stopDurationTracking()
      }
    } catch (err: any) {
      error.value = `Failed to pause recording: ${err.message || 'Unknown error'}`
    }
  }

  /**
   * Возобновление записи
   */
  function resumeRecording(): void {
    if (!mediaRecorder || recordingState.value !== 'paused') {
      return
    }

    try {
      if (mediaRecorder.state === 'paused') {
        mediaRecorder.resume()
        recordingState.value = 'recording'
        startDurationTracking()
      }
    } catch (err: any) {
      error.value = `Failed to resume recording: ${err.message || 'Unknown error'}`
    }
  }

  /**
   * Отмена записи
   */
  function cancelRecording(): void {
    cleanupRecording()
    recordingState.value = 'idle'
    audioBlob.value = null
    audioUrl.value = null
    error.value = null
    recordingDuration.value = 0
  }

  /**
   * Отправка записанного аудио на сервер
   */
  async function submitRecording(): Promise<VoiceCommandResponse | null> {
    if (!audioBlob.value) {
      error.value = 'No audio recorded'
      return null
    }

    try {
      recordingState.value = 'processing'
      error.value = null

      // Шаг 1: Загрузить аудио файл на сервер через /api/media
      const uploadResponse = await mediaService.uploadAudio(audioBlob.value, 'voice-command.webm')

      // uploadResponse.filePath содержит путь к файлу (например '/uploads/media/abc123.webm')
      const audioUrl = uploadResponse.filePath

      // Шаг 2: Отправить команду с URL аудио на /api/voice/command
      const response = await voiceService.submitVoiceCommand(
        audioUrl,
        config.language
      )

      recordingState.value = 'idle'
      return response

    } catch (err: any) {
      error.value = `Failed to submit recording: ${err.message || 'Unknown error'}`
      recordingState.value = 'error'
      return null
    }
  }

  /**
   * Трекинг длительности записи
   */
  function startDurationTracking(): void {
    durationInterval = window.setInterval(() => {
      recordingDuration.value = Date.now() - startTime
    }, 100) // Обновляем каждые 100ms
  }

  /**
   * Остановка трекинга длительности
   */
  function stopDurationTracking(): void {
    if (durationInterval !== null) {
      clearInterval(durationInterval)
      durationInterval = null
    }
  }

  /**
   * Очистка stream
   */
  function cleanupStream(): void {
    if (mediaStream) {
      mediaStream.getTracks().forEach(track => track.stop())
      mediaStream = null
    }
  }

  /**
   * Полная очистка ресурсов
   */
  function cleanupRecording(): void {
    stopDurationTracking()

    if (mediaRecorder) {
      if (mediaRecorder.state !== 'inactive') {
        try {
          mediaRecorder.stop()
        } catch (e) {
          // Игнорируем ошибки при остановке
        }
      }
      mediaRecorder = null
    }

    cleanupStream()
    audioChunks = []
  }

  /**
   * Cleanup при unmount компонента
   */
  function cleanup(): void {
    cleanupRecording()
    if (audioUrl.value) {
      URL.revokeObjectURL(audioUrl.value)
      audioUrl.value = null
    }
  }

  /**
   * Проверка поддержки браузером
   */
  function isSupported(): boolean {
    return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder)
  }

  /**
   * Форматирование длительности для отображения (MM:SS)
   */
  function formatDuration(ms: number): string {
    const totalSeconds = Math.floor(ms / 1000)
    const minutes = Math.floor(totalSeconds / 60)
    const seconds = totalSeconds % 60
    return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
  }

  return {
    // State
    recordingState,
    audioBlob,
    audioUrl,
    error,
    recordingDuration,

    // Computed
    isRecording,
    isPaused,
    isProcessing,
    hasError,
    canSubmit,

    // Methods
    startRecording,
    stopRecording,
    pauseRecording,
    resumeRecording,
    cancelRecording,
    submitRecording,
    cleanup,
    isSupported,
    formatDuration
  }
}
