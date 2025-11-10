import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/auth.service'
import { STORAGE_KEYS } from '@/config/constants'
import type { LoginCredentials, RegisterCredentials, User, AuthResponse } from '@/types/auth.types'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref<User | null>(null)
  const accessToken = ref<string | null>(null)
  const refreshToken = ref<string | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Getters
  const isAuthenticated = computed(() => !!accessToken.value && !!user.value)
  const userEmail = computed(() => user.value?.email ?? '')
  const userRoles = computed(() => user.value?.roles ?? [])

  // Actions
  async function login(credentials: LoginCredentials): Promise<void> {
    isLoading.value = true
    error.value = null

    try {
      const authResponse: AuthResponse = await authService.login(credentials)
      await handleAuthResponse(authResponse)
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Login failed'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function register(credentials: RegisterCredentials): Promise<void> {
    isLoading.value = true
    error.value = null

    try {
      await authService.register(credentials)
      // После успешной регистрации автоматически логинимся
      await login({
        email: credentials.email,
        password: credentials.password
      })
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Registration failed'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function loginWithGoogle(credential: string): Promise<void> {
    isLoading.value = true
    error.value = null

    try {
      const authResponse: AuthResponse = await authService.loginWithGoogle(credential)
      await handleAuthResponse(authResponse)
    } catch (err: unknown) {
      const errorMessage = err instanceof Error ? err.message : 'Google login failed'
      error.value = errorMessage
      throw err
    } finally {
      isLoading.value = false
    }
  }

  async function fetchCurrentUser(): Promise<void> {
    isLoading.value = true
    
    try {
      const userData = await authService.getCurrentUser()
      user.value = userData
      saveUserToStorage(userData)
    } catch (err: unknown) {
      console.error('Failed to fetch user:', err)
      logout()
    } finally {
      isLoading.value = false
    }
  }

  async function refreshAccessToken(): Promise<void> {
    const storedRefreshToken = refreshToken.value || localStorage.getItem(STORAGE_KEYS.REFRESH_TOKEN)
    
    if (!storedRefreshToken) {
      logout()
      return
    }

    try {
      const authResponse = await authService.refreshToken({
        refreshToken: storedRefreshToken
      })
      await handleAuthResponse(authResponse)
    } catch (err) {
      console.error('Token refresh failed:', err)
      logout()
    }
  }

  function logout(): void {
    user.value = null
    accessToken.value = null
    refreshToken.value = null
    error.value = null
    
    clearStorage()
  }

  async function initializeAuth(): Promise<void> {
    const storedToken = localStorage.getItem(STORAGE_KEYS.ACCESS_TOKEN)
    const storedRefreshToken = localStorage.getItem(STORAGE_KEYS.REFRESH_TOKEN)
    const storedUser = localStorage.getItem(STORAGE_KEYS.USER)

    if (storedToken && storedRefreshToken) {
      accessToken.value = storedToken
      refreshToken.value = storedRefreshToken
      
      if (storedUser) {
        try {
          user.value = JSON.parse(storedUser) as User
        } catch {
          console.error('Failed to parse stored user')
        }
      }

      // Проверяем актуальность токена
      await fetchCurrentUser()
    }
  }

  // Helper functions
  async function handleAuthResponse(authResponse: AuthResponse): Promise<void> {
    accessToken.value = authResponse.token
    refreshToken.value = authResponse.refreshToken
    
    saveTokensToStorage(authResponse)
    await fetchCurrentUser()
  }

  function saveTokensToStorage(authResponse: AuthResponse): void {
    localStorage.setItem(STORAGE_KEYS.ACCESS_TOKEN, authResponse.token)
    localStorage.setItem(STORAGE_KEYS.REFRESH_TOKEN, authResponse.refreshToken)
    localStorage.setItem(
      STORAGE_KEYS.REFRESH_TOKEN_EXPIRATION,
      String(authResponse.refreshTokenExpiration)
    )
  }

  function saveUserToStorage(userData: User): void {
    localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(userData))
  }

  function clearStorage(): void {
    localStorage.removeItem(STORAGE_KEYS.ACCESS_TOKEN)
    localStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN)
    localStorage.removeItem(STORAGE_KEYS.REFRESH_TOKEN_EXPIRATION)
    localStorage.removeItem(STORAGE_KEYS.USER)
  }

  return {
    // State
    user,
    accessToken,
    refreshToken,
    isLoading,
    error,
    // Getters
    isAuthenticated,
    userEmail,
    userRoles,
    // Actions
    login,
    register,
    loginWithGoogle,
    logout,
    fetchCurrentUser,
    refreshAccessToken,
    initializeAuth
  }
})

