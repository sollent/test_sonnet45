# 🔌 Интеграция с API - Конфигурация Axios

> **Краткое содержание**: Axios с request/response перехватчиками для автоматического управления токенами, обработки ошибок и обновления токенов. Паттерн service layer для чистых API вызовов. Автоматический повтор при 401 с ротацией refresh токена.

---

## Конфигурация Axios

### Класс ApiService

**Расположение:** `/src/services/api.service.ts`

```typescript
import axios, { type AxiosInstance, type AxiosError } from 'axios'
import { API_BASE_URL, STORAGE_KEYS } from '@/config/constants'

class ApiService {
  private axiosInstance: AxiosInstance
  private isRefreshing = false
  private failedQueue: Array<any> = []

  constructor() {
    this.axiosInstance = axios.create({
      baseURL: API_BASE_URL,  // http://localhost:8000
      headers: {
        'Content-Type': 'application/json'
      }
    })

    this.setupInterceptors()
  }

  private setupInterceptors(): void {
    // ===== ПЕРЕХВАТЧИК ЗАПРОСОВ =====
    this.axiosInstance.interceptors.request.use(
      (config) => {
        // Пропускаем заголовок Authorization для эндпоинта обновления токена
        const isRefreshEndpoint = config.url?.includes('/api/token/refresh')

        if (!isRefreshEndpoint) {
          // ✅ Добавляем access token к каждому запросу
          const token = localStorage.getItem(STORAGE_KEYS.ACCESS_TOKEN)
          if (token && config.headers) {
            config.headers.Authorization = `Bearer ${token}`
          }
        }

        // ✅ Добавляем заголовок локали
        const locale = localStorage.getItem('locale') || 'en'
        if (config.headers) {
          config.headers['Accept-Language'] = locale
        }

        return config
      },
      (error) => Promise.reject(error)
    )

    // ===== ПЕРЕХВАТЧИК ОТВЕТОВ =====
    this.axiosInstance.interceptors.response.use(
      (response) => response,
      async (error: AxiosError) => {
        const originalRequest = error.config as any

        // Обработка 401 Unauthorized (токен истёк)
        if (error.response?.status === 401 && !originalRequest._retry) {
          // Не повторяем запросы для эндпоинтов авторизации
          const isAuthEndpoint = originalRequest.url?.includes('/api/auth')
          const isRefreshEndpoint = originalRequest.url?.includes('/api/token/refresh')

          if (isAuthEndpoint || isRefreshEndpoint) {
            return Promise.reject(error)
          }

          const refreshToken = localStorage.getItem(STORAGE_KEYS.REFRESH_TOKEN)

          if (!refreshToken) {
            this.clearAuth()
            window.location.href = '/login'
            return Promise.reject(error)
          }

          // ✅ Ставим запросы в очередь во время обновления токена
          if (this.isRefreshing) {
            return new Promise((resolve, reject) => {
              this.failedQueue.push({ resolve, reject })
            })
              .then(token => {
                originalRequest.headers.Authorization = `Bearer ${token}`
                return this.axiosInstance.request(originalRequest)
              })
          }

          originalRequest._retry = true
          this.isRefreshing = true

          try {
            // ✅ Обновляем токен
            const { data } = await this.axiosInstance.post('/api/token/refresh', {
              refreshToken
            })

            // ✅ Сохраняем новые токены
            localStorage.setItem(STORAGE_KEYS.ACCESS_TOKEN, data.token)
            localStorage.setItem(STORAGE_KEYS.REFRESH_TOKEN, data.refreshToken)

            // ✅ Обрабатываем запросы из очереди
            this.processQueue(null, data.token)

            // ✅ Повторяем оригинальный запрос
            originalRequest.headers.Authorization = `Bearer ${data.token}`
            return this.axiosInstance.request(originalRequest)
          } catch (refreshError) {
            // ❌ Обновление не удалось → редирект на логин
            this.processQueue(refreshError, null)
            this.clearAuth()
            window.location.href = '/login'
            return Promise.reject(refreshError)
          } finally {
            this.isRefreshing = false
          }
        }

        return Promise.reject(error)
      }
    )
  }

  private processQueue(error: any = null, token: string | null = null): void {
    this.failedQueue.forEach(prom => {
      if (error) {
        prom.reject(error)
      } else {
        prom.resolve(token)
      }
    })
    this.failedQueue = []
  }

  private clearAuth(): void {
    localStorage.removeItem(STORAGE_KEYS.ACCESS_TOKEN)
    localStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN)
    localStorage.removeItem(STORAGE_KEYS.USER)
  }

  get instance(): AxiosInstance {
    return this.axiosInstance
  }
}

export const apiService = new ApiService()
export const apiClient = apiService.instance
```

---

## Паттерн Service Layer

### Task Service

**Расположение:** `/src/services/task.service.ts`

```typescript
import { apiClient } from './api.service'
import type { Task, CreateTaskRequest, UpdateTaskRequest } from '@/types/task.types'

class TaskService {
  private readonly basePath = '/api/tasks'

  async getTasks(filters?: Record<string, any>): Promise<Task[]> {
    const { data } = await apiClient.get<Task[]>(this.basePath, {
      params: filters
    })
    return data
  }

  async getTask(id: number): Promise<Task> {
    const { data } = await apiClient.get<Task>(`${this.basePath}/${id}`)
    return data
  }

  async createTask(taskData: CreateTaskRequest): Promise<Task> {
    const { data } = await apiClient.post<Task>(this.basePath, taskData)
    return data
  }

  async updateTask(id: number, taskData: UpdateTaskRequest): Promise<Task> {
    const { data } = await apiClient.put<Task>(`${this.basePath}/${id}`, taskData)
    return data
  }

  async deleteTask(id: number): Promise<void> {
    await apiClient.delete(`${this.basePath}/${id}`)
  }
}

export const taskService = new TaskService()
```

---

## Обработка Ошибок

### Глобальный Обработчик Ошибок

```typescript
// utils/errorHandler.ts
import { useToast } from '@/composables/useToast'
import type { AxiosError } from 'axios'

export function handleApiError(error: AxiosError): void {
  const { showError } = useToast()

  if (error.response) {
    const status = error.response.status
    const message = error.response.data?.message || 'Неизвестная ошибка'

    switch (status) {
      case 400:
        showError(`Неверный запрос: ${message}`)
        break
      case 401:
        showError('Неавторизован. Пожалуйста, войдите снова.')
        break
      case 403:
        showError('Доступ запрещён.')
        break
      case 404:
        showError('Ресурс не найден.')
        break
      case 500:
        showError('Ошибка сервера. Попробуйте позже.')
        break
      default:
        showError(message)
    }
  } else if (error.request) {
    showError('Нет ответа от сервера. Проверьте соединение.')
  } else {
    showError('Запрос не удался. Попробуйте снова.')
  }
}
```

---

## Управление Токенами

### Поток Обновления Токенов

```
1. Пользователь делает API запрос
   ↓
2. Access token истёк (401)
   ↓
3. Перехватчик ловит 401
   ↓
4. Ставим текущий запрос в очередь
   ↓
5. Вызываем /api/token/refresh
   ↓
6. Получаем новые access + refresh токены
   ↓
7. Сохраняем новые токены
   ↓
8. Обрабатываем запросы из очереди с новым токеном
   ↓
9. Повторяем оригинальный запрос
   ↓
10. Возвращаем ответ пользователю
```

### Логика Повторных Попыток

```typescript
// Автоматический повтор при сетевой ошибке
import axiosRetry from 'axios-retry'

axiosRetry(apiClient, {
  retries: 3,
  retryDelay: axiosRetry.exponentialDelay,
  retryCondition: (error) => {
    return axiosRetry.isNetworkOrIdempotentRequestError(error) ||
           error.response?.status === 429  // Слишком много запросов
  }
})
```

---

## Связанные Документы

- **[Аутентификация Backend](../backend/AUTHENTICATION.md)** - Реализация JWT
- **[Управление Состоянием](STATE_MANAGEMENT.md)** - Использование сервисов в сторах

---

*Последнее обновление: 2025-01-05*
