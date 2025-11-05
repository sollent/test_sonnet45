# 🔌 API Integration - Axios Configuration

> **TL;DR**: Axios with request/response interceptors for automatic token management, error handling, and token refresh. Service layer pattern for clean API calls. Automatic retry on 401 with refresh token rotation.

---

## Axios Configuration

### ApiService Class

**Location:** `/src/services/api.service.ts`

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
    // ===== REQUEST INTERCEPTOR =====
    this.axiosInstance.interceptors.request.use(
      (config) => {
        // Skip Authorization header for refresh endpoint
        const isRefreshEndpoint = config.url?.includes('/api/token/refresh')

        if (!isRefreshEndpoint) {
          // ✅ Add access token to every request
          const token = localStorage.getItem(STORAGE_KEYS.ACCESS_TOKEN)
          if (token && config.headers) {
            config.headers.Authorization = `Bearer ${token}`
          }
        }

        // ✅ Add locale header
        const locale = localStorage.getItem('locale') || 'en'
        if (config.headers) {
          config.headers['Accept-Language'] = locale
        }

        return config
      },
      (error) => Promise.reject(error)
    )

    // ===== RESPONSE INTERCEPTOR =====
    this.axiosInstance.interceptors.response.use(
      (response) => response,
      async (error: AxiosError) => {
        const originalRequest = error.config as any

        // Handle 401 Unauthorized (token expired)
        if (error.response?.status === 401 && !originalRequest._retry) {
          // Don't retry for auth endpoints
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

          // ✅ Queue requests while refreshing
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
            // ✅ Refresh token
            const { data } = await this.axiosInstance.post('/api/token/refresh', {
              refreshToken
            })

            // ✅ Save new tokens
            localStorage.setItem(STORAGE_KEYS.ACCESS_TOKEN, data.token)
            localStorage.setItem(STORAGE_KEYS.REFRESH_TOKEN, data.refreshToken)

            // ✅ Process queued requests
            this.processQueue(null, data.token)

            // ✅ Retry original request
            originalRequest.headers.Authorization = `Bearer ${data.token}`
            return this.axiosInstance.request(originalRequest)
          } catch (refreshError) {
            // ❌ Refresh failed → redirect to login
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

## Service Layer Pattern

### Task Service

**Location:** `/src/services/task.service.ts`

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

## Error Handling

### Global Error Handler

```typescript
// utils/errorHandler.ts
import { useToast } from '@/composables/useToast'
import type { AxiosError } from 'axios'

export function handleApiError(error: AxiosError): void {
  const { showError } = useToast()

  if (error.response) {
    const status = error.response.status
    const message = error.response.data?.message || 'Unknown error'

    switch (status) {
      case 400:
        showError(`Bad Request: ${message}`)
        break
      case 401:
        showError('Unauthorized. Please login again.')
        break
      case 403:
        showError('Access denied.')
        break
      case 404:
        showError('Resource not found.')
        break
      case 500:
        showError('Server error. Please try again later.')
        break
      default:
        showError(message)
    }
  } else if (error.request) {
    showError('No response from server. Check your connection.')
  } else {
    showError('Request failed. Please try again.')
  }
}
```

---

## Token Management

### Token Refresh Flow

```
1. User makes API request
   ↓
2. Access token expired (401)
   ↓
3. Interceptor catches 401
   ↓
4. Queue current request
   ↓
5. Call /api/token/refresh
   ↓
6. Get new access + refresh tokens
   ↓
7. Save new tokens
   ↓
8. Process queued requests with new token
   ↓
9. Retry original request
   ↓
10. Return response to user
```

### Retry Logic

```typescript
// Automatic retry on network error
import axiosRetry from 'axios-retry'

axiosRetry(apiClient, {
  retries: 3,
  retryDelay: axiosRetry.exponentialDelay,
  retryCondition: (error) => {
    return axiosRetry.isNetworkOrIdempotentRequestError(error) ||
           error.response?.status === 429  // Too Many Requests
  }
})
```

---

## Related Documents

- **[Backend Authentication](../backend/AUTHENTICATION.md)** - JWT implementation
- **[State Management](STATE_MANAGEMENT.md)** - Using services in stores

---

*Last updated: 2025-01-05*
