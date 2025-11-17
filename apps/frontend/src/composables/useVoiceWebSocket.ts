/**
 * Composable для WebSocket подключения к Centrifugo
 *
 * Предоставляет real-time обновления статуса голосовых команд
 * через WebSocket соединение с Centrifugo сервером
 */

import { ref, computed, onUnmounted } from 'vue'
import { useAuth } from './useAuth'
import type { VoiceCommandStatusUpdate } from '@/types/voice.types'

/**
 * Состояния WebSocket подключения
 */
export type WebSocketState = 'disconnected' | 'connecting' | 'connected' | 'error'

/**
 * Callback для обработки обновлений команд
 */
export type VoiceCommandUpdateCallback = (update: VoiceCommandStatusUpdate) => void

/**
 * Опции подключения
 */
export interface WebSocketOptions {
  /** URL Centrifugo сервера */
  url?: string

  /** Автоматическое переподключение при разрыве */
  autoReconnect?: boolean

  /** Интервал переподключения в мс */
  reconnectInterval?: number

  /** Максимальное количество попыток переподключения */
  maxReconnectAttempts?: number
}

/**
 * Composable для работы с WebSocket подключением к Centrifugo
 *
 * ПРИМЕЧАНИЕ: В будущем можно заменить на centrifuge-js клиент
 * для более продвинутой функциональности (автоматическое переподключение,
 * presence, history и т.д.)
 */
export function useVoiceWebSocket(options: WebSocketOptions = {}) {
  const { user } = useAuth()

  // Конфигурация
  const config = {
    url: options.url ?? 'ws://localhost:8000/connection/websocket',
    autoReconnect: options.autoReconnect ?? true,
    reconnectInterval: options.reconnectInterval ?? 3000,
    maxReconnectAttempts: options.maxReconnectAttempts ?? 10
  }

  // Состояние
  const connectionState = ref<WebSocketState>('disconnected')
  const error = ref<string | null>(null)
  const lastUpdate = ref<VoiceCommandStatusUpdate | null>(null)

  // WebSocket instance
  let ws: WebSocket | null = null
  let reconnectAttempts = 0
  let reconnectTimeout: number | null = null

  // Callbacks для обновлений
  const updateCallbacks = new Set<VoiceCommandUpdateCallback>()

  // Computed
  const isConnected = computed(() => connectionState.value === 'connected')
  const isConnecting = computed(() => connectionState.value === 'connecting')
  const hasError = computed(() => connectionState.value === 'error')

  /**
   * Подключение к WebSocket
   */
  function connect(): void {
    if (!user.value) {
      error.value = 'User not authenticated'
      connectionState.value = 'error'
      return
    }

    if (ws && ws.readyState === WebSocket.OPEN) {
      console.warn('WebSocket already connected')
      return
    }

    try {
      connectionState.value = 'connecting'
      error.value = null

      // Создаем WebSocket подключение
      ws = new WebSocket(config.url)

      // Обработчики событий
      ws.onopen = handleOpen
      ws.onmessage = handleMessage
      ws.onerror = handleError
      ws.onclose = handleClose

    } catch (err: any) {
      error.value = `Failed to connect: ${err.message || 'Unknown error'}`
      connectionState.value = 'error'
    }
  }

  /**
   * Отключение от WebSocket
   */
  function disconnect(): void {
    // Отменяем автоматическое переподключение
    if (reconnectTimeout !== null) {
      clearTimeout(reconnectTimeout)
      reconnectTimeout = null
    }

    reconnectAttempts = 0

    if (ws) {
      ws.close()
      ws = null
    }

    connectionState.value = 'disconnected'
  }

  /**
   * Обработчик успешного подключения
   */
  function handleOpen(): void {
    console.log('[WebSocket] Connected to Centrifugo')
    connectionState.value = 'connected'
    error.value = null
    reconnectAttempts = 0

    // Подписываемся на канал пользователя
    if (user.value) {
      subscribeToUserChannel(user.value.id)
    }
  }

  /**
   * Обработчик входящих сообщений
   */
  function handleMessage(event: MessageEvent): void {
    try {
      const data = JSON.parse(event.data)

      // Обрабатываем сообщения от Centrifugo
      // Формат зависит от Centrifugo protocol (может быть JSON или binary)

      // Для простоты предполагаем что получаем напрямую VoiceCommandStatusUpdate
      if (data.type === 'voice_command_update') {
        const update: VoiceCommandStatusUpdate = data.payload
        lastUpdate.value = update

        // Вызываем все зарегистрированные callbacks
        updateCallbacks.forEach(callback => callback(update))
      }

    } catch (err: any) {
      console.error('[WebSocket] Failed to parse message:', err)
    }
  }

  /**
   * Обработчик ошибок
   */
  function handleError(event: Event): void {
    console.error('[WebSocket] Error:', event)
    error.value = 'WebSocket connection error'
    connectionState.value = 'error'
  }

  /**
   * Обработчик закрытия соединения
   */
  function handleClose(event: CloseEvent): void {
    console.log('[WebSocket] Disconnected:', event.code, event.reason)
    connectionState.value = 'disconnected'
    ws = null

    // Автоматическое переподключение
    if (config.autoReconnect && reconnectAttempts < config.maxReconnectAttempts) {
      reconnectAttempts++
      console.log(`[WebSocket] Reconnecting... (attempt ${reconnectAttempts}/${config.maxReconnectAttempts})`)

      reconnectTimeout = window.setTimeout(() => {
        connect()
      }, config.reconnectInterval)
    } else if (reconnectAttempts >= config.maxReconnectAttempts) {
      error.value = 'Max reconnection attempts reached'
      connectionState.value = 'error'
    }
  }

  /**
   * Подписка на канал обновлений пользователя
   */
  function subscribeToUserChannel(userId: number): void {
    if (!ws || ws.readyState !== WebSocket.OPEN) {
      console.warn('[WebSocket] Cannot subscribe: not connected')
      return
    }

    // Отправляем сообщение подписки на канал
    // Формат зависит от Centrifugo protocol
    const subscribeMessage = {
      method: 'subscribe',
      params: {
        channel: `voice_commands:${userId}`
      }
    }

    ws.send(JSON.stringify(subscribeMessage))
    console.log(`[WebSocket] Subscribed to channel: voice_commands:${userId}`)
  }

  /**
   * Регистрация callback для обновлений команд
   */
  function onCommandUpdate(callback: VoiceCommandUpdateCallback): () => void {
    updateCallbacks.add(callback)

    // Возвращаем функцию для отписки
    return () => {
      updateCallbacks.delete(callback)
    }
  }

  /**
   * Отправка сообщения в WebSocket
   */
  function send(message: any): void {
    if (!ws || ws.readyState !== WebSocket.OPEN) {
      console.warn('[WebSocket] Cannot send: not connected')
      return
    }

    ws.send(JSON.stringify(message))
  }

  /**
   * Cleanup при unmount компонента
   */
  onUnmounted(() => {
    disconnect()
  })

  return {
    // State
    connectionState,
    error,
    lastUpdate,

    // Computed
    isConnected,
    isConnecting,
    hasError,

    // Methods
    connect,
    disconnect,
    onCommandUpdate,
    send
  }
}

/**
 * TODO: Интеграция с centrifuge-js
 *
 * Для production рекомендуется использовать официальный centrifuge-js клиент:
 *
 * 1. Установить: npm install centrifuge
 * 2. Импортировать: import { Centrifuge } from 'centrifuge'
 * 3. Создать клиент:
 *
 * ```typescript
 * const centrifuge = new Centrifuge(config.url, {
 *   token: 'user-jwt-token', // JWT токен для аутентификации
 *   debug: true
 * })
 *
 * // Подписка на канал
 * const subscription = centrifuge.newSubscription(`voice_commands:${userId}`, {
 *   handler: (ctx) => {
 *     const update: VoiceCommandStatusUpdate = ctx.data
 *     // Обработка обновления
 *   }
 * })
 *
 * subscription.subscribe()
 * centrifuge.connect()
 * ```
 *
 * Преимущества centrifuge-js:
 * - Автоматическое переподключение
 * - JWT аутентификация
 * - Presence (кто онлайн)
 * - History (получение пропущенных сообщений)
 * - RPC (вызов методов на сервере)
 * - Batching (группировка сообщений)
 */
