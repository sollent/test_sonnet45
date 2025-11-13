import axios, { type AxiosInstance, type AxiosError, type InternalAxiosRequestConfig } from 'axios'
import { API_BASE_URL, STORAGE_KEYS } from '@/config/constants'
import type { ErrorResponse } from '@/types/api.types'
import { triggerOfflineModal } from '@/composables/useOfflineDetection'

class ApiService {
  private axiosInstance: AxiosInstance
  private isRefreshing = false
  private failedQueue: Array<{
    resolve: (value?: unknown) => void
    reject: (reason?: unknown) => void
  }> = []

  constructor() {
    this.axiosInstance = axios.create({
      baseURL: API_BASE_URL,
      headers: {
        'Content-Type': 'application/json'
      }
    })

    this.setupInterceptors()
  }

  private processQueue(error: unknown = null, token: string | null = null): void {
    this.failedQueue.forEach(prom => {
      if (error) {
        prom.reject(error)
      } else {
        prom.resolve(token)
      }
    })

    this.failedQueue = []
  }

  private setupInterceptors(): void {
    // Request interceptor - добавляем токен и локаль к каждому запросу
    this.axiosInstance.interceptors.request.use(
      (config: InternalAxiosRequestConfig) => {
        // НЕ добавляем Authorization для refresh endpoint
        const isRefreshEndpoint = config.url?.includes('/api/token/refresh')

        if (!isRefreshEndpoint) {
          const token = localStorage.getItem(STORAGE_KEYS.ACCESS_TOKEN)
          if (token && config.headers) {
            config.headers.Authorization = `Bearer ${token}`
          }
        }

        // Добавляем Accept-Language header
        const locale = localStorage.getItem('locale') || 'en'
        if (config.headers) {
          config.headers['Accept-Language'] = locale
        }

        return config
      },
      (error) => Promise.reject(error)
    )

    // Response interceptor - обработка ошибок
    this.axiosInstance.interceptors.response.use(
      (response) => response,
      async (error: AxiosError<ErrorResponse>) => {
        const originalRequest = error.config as InternalAxiosRequestConfig & { _retry?: boolean }

        // Проверяем сетевые ошибки (нет ответа от сервера)
        if (!error.response && error.code === 'ERR_NETWORK') {
          // Это сетевая ошибка - показываем модалку
          const url = originalRequest?.url || 'unknown'
          triggerOfflineModal(url)
        }

        if (error.response?.status === 401 && originalRequest && !originalRequest._retry) {
          // Не пытаемся refresh для refresh/auth endpoints
          const isRefreshEndpoint = originalRequest.url?.includes('/api/token/refresh')
          const isAuthEndpoint = originalRequest.url?.includes('/api/auth')

          if (isRefreshEndpoint || isAuthEndpoint) {
            // Для этих endpoints просто отклоняем запрос
            return Promise.reject(error)
          }

          const refreshToken = localStorage.getItem(STORAGE_KEYS.REFRESH_TOKEN)

          if (!refreshToken) {
            this.clearAuth()
            sessionStorage.setItem('skip_loader', 'true')
            window.location.href = '/login'
            return Promise.reject(error)
          }

          // Если уже идет процесс рефреша - добавляем запрос в очередь
          if (this.isRefreshing) {
            return new Promise((resolve, reject) => {
              this.failedQueue.push({ resolve, reject })
            })
              .then(token => {
                if (originalRequest.headers) {
                  originalRequest.headers.Authorization = `Bearer ${token}`
                }
                return this.axiosInstance.request(originalRequest)
              })
              .catch(err => Promise.reject(err))
          }

          originalRequest._retry = true
          this.isRefreshing = true

          try {
            const { data } = await this.axiosInstance.post('/api/token/refresh', {
              refreshToken
            })

            // Сохраняем новые токены
            localStorage.setItem(STORAGE_KEYS.ACCESS_TOKEN, data.token)
            localStorage.setItem(STORAGE_KEYS.REFRESH_TOKEN, data.refreshToken)

            // Обрабатываем очередь запросов
            this.processQueue(null, data.token)

            // Повторяем оригинальный запрос
            if (originalRequest.headers) {
              originalRequest.headers.Authorization = `Bearer ${data.token}`
            }
            return this.axiosInstance.request(originalRequest)
          } catch (refreshError) {
            // Если refresh не удался - очищаем очередь и редиректим на логин
            this.processQueue(refreshError, null)
            this.clearAuth()
            sessionStorage.setItem('skip_loader', 'true')
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

  private clearAuth(): void {
    localStorage.removeItem(STORAGE_KEYS.ACCESS_TOKEN)
    localStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN)
    localStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN_EXPIRATION)
    localStorage.removeItem(STORAGE_KEYS.USER)
  }

  get instance(): AxiosInstance {
    return this.axiosInstance
  }
}

export const apiService = new ApiService()
export const apiClient = apiService.instance

