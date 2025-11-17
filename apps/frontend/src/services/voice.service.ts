/**
 * Voice Command Service - обработка голосовых команд
 *
 * Предоставляет методы для:
 * - Отправки голосовых и текстовых команд
 * - Получения истории команд
 * - Отслеживания статуса обработки
 * - Получения статистики использования
 */

import { apiClient } from './api.service'
import type {
  VoiceCommandRequest,
  VoiceCommandResponse,
  VoiceCommandHistoryResponse,
  VoiceCommandStatisticsResponse,
  VoiceHistoryFilters
} from '@/types/voice.types'

/**
 * API endpoints для голосовых команд
 */
const API_ENDPOINTS = {
  COMMAND: '/api/voice/command',
  STATUS: (id: number) => `/api/voice/status/${id}`,
  HISTORY: '/api/voice/history',
  STATISTICS: '/api/voice/statistics',
  RETRY: (id: number) => `/api/voice/retry/${id}`
}

/**
 * Сервис для работы с голосовыми командами
 */
class VoiceCommandService {
  /**
   * Отправить голосовую или текстовую команду на обработку
   *
   * @param request - Данные команды (аудио URL или текст)
   * @returns Promise с информацией о созданной команде
   *
   * @example
   * ```typescript
   * // Текстовая команда
   * const result = await voiceService.submitCommand({
   *   text: 'Создай задачу купить молоко завтра',
   *   language: 'ru'
   * })
   *
   * // Голосовая команда
   * const result = await voiceService.submitCommand({
   *   audioUrl: 'https://example.com/audio/command.mp3',
   *   language: 'ru'
   * })
   * ```
   */
  async submitCommand(request: VoiceCommandRequest): Promise<VoiceCommandResponse> {
    const { data } = await apiClient.post<VoiceCommandResponse>(
      API_ENDPOINTS.COMMAND,
      request
    )
    return data
  }

  /**
   * Получить статус конкретной команды
   *
   * @param commandId - ID команды
   * @returns Promise с информацией о команде
   *
   * @example
   * ```typescript
   * const command = await voiceService.getCommandStatus(123)
   * console.log(command.status) // 'completed', 'processing', etc.
   * ```
   */
  async getCommandStatus(commandId: number): Promise<VoiceCommandResponse> {
    const { data } = await apiClient.get<VoiceCommandResponse>(
      API_ENDPOINTS.STATUS(commandId)
    )
    return data
  }

  /**
   * Получить историю голосовых команд пользователя
   *
   * @param filters - Фильтры (limit, offset, status)
   * @returns Promise с списком команд
   *
   * @example
   * ```typescript
   * // Последние 20 команд
   * const history = await voiceService.getHistory({ limit: 20, offset: 0 })
   *
   * // Только проваленные команды
   * const failed = await voiceService.getHistory({ status: 'failed' })
   * ```
   */
  async getHistory(filters?: VoiceHistoryFilters): Promise<VoiceCommandHistoryResponse> {
    const params = new URLSearchParams()

    if (filters?.limit !== undefined) {
      params.append('limit', String(filters.limit))
    }
    if (filters?.offset !== undefined) {
      params.append('offset', String(filters.offset))
    }
    if (filters?.status) {
      params.append('status', filters.status)
    }

    const queryString = params.toString()
    const url = queryString ? `${API_ENDPOINTS.HISTORY}?${queryString}` : API_ENDPOINTS.HISTORY

    const { data } = await apiClient.get<VoiceCommandHistoryResponse>(url)
    return data
  }

  /**
   * Получить статистику использования голосовых команд
   *
   * @returns Promise со статистикой
   *
   * @example
   * ```typescript
   * const stats = await voiceService.getStatistics()
   * console.log(`Success rate: ${stats.successRate}%`)
   * console.log(`Total commands: ${stats.totalCommands}`)
   * ```
   */
  async getStatistics(): Promise<VoiceCommandStatisticsResponse> {
    const { data } = await apiClient.get<VoiceCommandStatisticsResponse>(
      API_ENDPOINTS.STATISTICS
    )
    return data
  }

  /**
   * Повторить обработку проваленной команды
   *
   * @param commandId - ID команды для повторной обработки
   * @returns Promise с обновленной информацией о команде
   *
   * @example
   * ```typescript
   * const result = await voiceService.retryCommand(123)
   * console.log(result.status) // 'processing' - команда запущена заново
   * ```
   */
  async retryCommand(commandId: number): Promise<VoiceCommandResponse> {
    const { data } = await apiClient.post<VoiceCommandResponse>(
      API_ENDPOINTS.RETRY(commandId)
    )
    return data
  }

  /**
   * Отправить текстовую команду (удобный метод)
   *
   * @param text - Текст команды
   * @param language - Язык команды (по умолчанию 'ru')
   * @returns Promise с информацией о созданной команде
   *
   * @example
   * ```typescript
   * const result = await voiceService.submitTextCommand(
   *   'Создай задачу купить молоко',
   *   'ru'
   * )
   * ```
   */
  async submitTextCommand(
    text: string,
    language: 'ru' | 'en' | 'uk' = 'ru'
  ): Promise<VoiceCommandResponse> {
    return this.submitCommand({ text, language })
  }

  /**
   * Отправить голосовую команду (удобный метод)
   *
   * @param audioUrl - URL аудио файла
   * @param language - Язык команды (по умолчанию 'ru')
   * @returns Promise с информацией о созданной команде
   *
   * @example
   * ```typescript
   * const result = await voiceService.submitVoiceCommand(
   *   'https://example.com/audio.mp3',
   *   'ru'
   * )
   * ```
   */
  async submitVoiceCommand(
    audioUrl: string,
    language: 'ru' | 'en' | 'uk' = 'ru'
  ): Promise<VoiceCommandResponse> {
    return this.submitCommand({ audioUrl, language })
  }

  /**
   * Опросить статус команды с интервалом до завершения
   *
   * @param commandId - ID команды
   * @param intervalMs - Интервал опроса в миллисекундах (по умолчанию 1000)
   * @param maxAttempts - Максимальное количество попыток (по умолчанию 60)
   * @param onUpdate - Callback для обновлений статуса
   * @returns Promise с финальным статусом команды
   *
   * @example
   * ```typescript
   * const finalStatus = await voiceService.pollCommandStatus(
   *   123,
   *   1000,
   *   60,
   *   (command) => console.log(`Status: ${command.status}`)
   * )
   * ```
   */
  async pollCommandStatus(
    commandId: number,
    intervalMs = 1000,
    maxAttempts = 60,
    onUpdate?: (command: VoiceCommandResponse) => void
  ): Promise<VoiceCommandResponse> {
    let attempts = 0

    while (attempts < maxAttempts) {
      const command = await this.getCommandStatus(commandId)

      // Вызываем callback если предоставлен
      if (onUpdate) {
        onUpdate(command)
      }

      // Проверяем финальные статусы
      if (command.status === 'completed' || command.status === 'failed') {
        return command
      }

      // Ждем перед следующей попыткой
      await new Promise(resolve => setTimeout(resolve, intervalMs))
      attempts++
    }

    // Если достигли максимума попыток, получаем последний статус
    return this.getCommandStatus(commandId)
  }
}

/**
 * Singleton instance сервиса голосовых команд
 */
export const voiceService = new VoiceCommandService()
