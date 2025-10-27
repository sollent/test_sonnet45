import axios, { type AxiosInstance, type AxiosError, type InternalAxiosRequestConfig } from 'axios'
import { API_BASE_URL, STORAGE_KEYS } from '@/config/constants'
import type { ErrorResponse } from '@/types/api.types'

class ApiService {
  private axiosInstance: AxiosInstance

  constructor() {
    this.axiosInstance = axios.create({
      baseURL: API_BASE_URL,
      headers: {
        'Content-Type': 'application/json'
      }
    })

    this.setupInterceptors()
  }

  private setupInterceptors(): void {
    // Request interceptor - добавляем токен и локаль к каждому запросу
    this.axiosInstance.interceptors.request.use(
      (config: InternalAxiosRequestConfig) => {
        const token = localStorage.getItem(STORAGE_KEYS.ACCESS_TOKEN)
        if (token && config.headers) {
          config.headers.Authorization = `Bearer ${token}`
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
        const originalRequest = error.config
        
        if (error.response?.status === 401 && originalRequest) {
          // Не пытаемся refresh для login/register endpoints
          const isAuthEndpoint = originalRequest.url?.includes('/api/auth') || 
                                 originalRequest.url?.includes('/api/users')
          
          if (isAuthEndpoint) {
            // Для auth endpoints просто отклоняем запрос
            return Promise.reject(error)
          }
          
          // Token expired - попытка обновить через refresh token
          const refreshToken = localStorage.getItem(STORAGE_KEYS.REFRESH_TOKEN)
          
          if (refreshToken) {
            try {
              const { data } = await this.axiosInstance.post('/api/token/refresh', {
                refreshToken
              })
              
              // Сохраняем новые токены
              localStorage.setItem(STORAGE_KEYS.ACCESS_TOKEN, data.token)
              localStorage.setItem(STORAGE_KEYS.REFRESH_TOKEN, data.refreshToken)
              
              // Повторяем оригинальный запрос
              originalRequest.headers.Authorization = `Bearer ${data.token}`
              return this.axiosInstance.request(originalRequest)
            } catch (refreshError) {
              // Если refresh не удался - очищаем и редиректим на логин
              this.clearAuth()
              window.location.href = '/login'
            }
          } else {
            this.clearAuth()
            window.location.href = '/login'
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

