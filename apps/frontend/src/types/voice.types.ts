/**
 * TypeScript types для Voice AI Assistant
 *
 * Соответствуют backend DTOs:
 * - VoiceCommandRequest
 * - VoiceCommandResponse
 * - VoiceCommandHistoryResponse
 * - VoiceCommandStatisticsResponse
 */

/**
 * Статусы обработки голосовой команды
 */
export type VoiceCommandStatus = 'pending' | 'processing' | 'executing' | 'completed' | 'failed'

/**
 * Типы голосовых команд
 */
export type VoiceCommandType = 'voice_audio' | 'voice_text'

/**
 * Request для отправки голосовой команды
 */
export interface VoiceCommandRequest {
  /** URL аудио файла (для голосовых команд) */
  audioUrl?: string

  /** Текст команды (альтернатива аудио) */
  text?: string

  /** Код языка для лучшего распознавания */
  language?: 'ru' | 'en' | 'uk'

  /** Дополнительный контекст для обработки команды */
  context?: Record<string, unknown>
}

/**
 * Распознанная команда от LLM
 */
export interface ParsedCommand {
  /** Действие (create_task, complete_task, filter_tasks) */
  action: string

  /** Параметры команды */
  parameters: Record<string, unknown>

  /** Уверенность LLM в распознавании (0-1) */
  confidence: number
}

/**
 * Результат выполнения команды
 */
export interface ExecutionResult {
  /** Тип результата */
  type: string

  /** Успешность выполнения */
  success: boolean

  /** Сообщение пользователю */
  message: string

  /** Дополнительные данные (например, созданная задача) */
  [key: string]: unknown
}

/**
 * Response с информацией о голосовой команде
 */
export interface VoiceCommandResponse {
  /** ID команды */
  id: number

  /** Текущий статус */
  status: VoiceCommandStatus

  /** Человеко-читаемая метка статуса */
  statusLabel: string

  /** Тип команды */
  commandType: VoiceCommandType

  /** Распознанный текст из аудио */
  transcribedText?: string | null

  /** Распознанная команда */
  parsedCommand?: ParsedCommand | null

  /** Результат выполнения */
  executionResult?: ExecutionResult | null

  /** Сообщение об ошибке (если failed) */
  errorMessage?: string | null

  /** Длительность обработки в миллисекундах */
  processingDurationMs?: number | null

  /** Timestamp создания (ISO 8601) */
  createdAt: string

  /** Timestamp завершения (ISO 8601) */
  completedAt?: string | null

  /** Флаг успешности */
  success: boolean
}

/**
 * Response с историей голосовых команд
 */
export interface VoiceCommandHistoryResponse {
  /** Список команд */
  commands: VoiceCommandResponse[]

  /** Общее количество команд в результате */
  total: number

  /** Лимит, использованный в запросе */
  limit: number

  /** Смещение, использованное в запросе */
  offset: number
}

/**
 * Статистика по статусам команд
 */
export interface CommandsByStatus {
  pending?: number
  processing?: number
  executing?: number
  completed?: number
  failed?: number
}

/**
 * Статистика по действиям
 */
export interface MostUsedActions {
  [action: string]: number
}

/**
 * Статистика по дням
 */
export interface CommandsByDay {
  [date: string]: number
}

/**
 * Response со статистикой использования голосовых команд
 */
export interface VoiceCommandStatisticsResponse {
  /** Общее количество команд */
  totalCommands: number

  /** Количество команд по статусам */
  commandsByStatus: CommandsByStatus

  /** Средняя длительность обработки в миллисекундах */
  averageDurationMs: number

  /** Процент успешных команд */
  successRate: number

  /** Наиболее используемые действия */
  mostUsedActions?: MostUsedActions | null

  /** Количество команд по дням (последние 7 дней) */
  commandsByDay?: CommandsByDay | null
}

/**
 * Параметры для получения истории
 */
export interface VoiceHistoryFilters {
  /** Максимальное количество команд */
  limit?: number

  /** Количество команд для пропуска */
  offset?: number

  /** Фильтр по статусу */
  status?: VoiceCommandStatus
}

/**
 * WebSocket сообщение о статусе команды
 */
export interface VoiceCommandStatusUpdate {
  /** ID команды */
  commandId: number

  /** Новый статус */
  status: VoiceCommandStatus

  /** Обновленная команда */
  command: VoiceCommandResponse
}
