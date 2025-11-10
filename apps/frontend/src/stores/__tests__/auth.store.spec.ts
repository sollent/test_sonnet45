import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../auth.store'
import { authService } from '@/services/auth.service'
import type { LoginCredentials, RegisterCredentials, User, AuthResponse } from '@/types/auth.types'

vi.mock('@/services/auth.service', () => ({
  authService: {
    login: vi.fn(),
    register: vi.fn(),
    getCurrentUser: vi.fn(),
    refreshToken: vi.fn(),
  },
}))

describe('AuthStore', () => {
  let store: ReturnType<typeof useAuthStore>

  beforeEach(() => {
    setActivePinia(createPinia())
    store = useAuthStore()
    localStorage.clear()
    vi.clearAllMocks()
  })

  afterEach(() => {
    localStorage.clear()
  })

  describe('initial state', () => {
    it('should have correct initial state', () => {
      expect(store.user).toBeNull()
      expect(store.accessToken).toBeNull()
      expect(store.refreshToken).toBeNull()
      expect(store.isLoading).toBe(false)
      expect(store.error).toBeNull()
      expect(store.isAuthenticated).toBe(false)
    })
  })

  describe('computed properties', () => {
    it('should compute isAuthenticated correctly', () => {
      expect(store.isAuthenticated).toBe(false)

      store.accessToken = 'token'
      expect(store.isAuthenticated).toBe(false) // still false without user

      store.user = {
        id: 1,
        email: 'test@example.com',
        roles: ['ROLE_USER'],
      } as User
      expect(store.isAuthenticated).toBe(true)
    })

    it('should compute userEmail correctly', () => {
      expect(store.userEmail).toBe('')

      store.user = {
        id: 1,
        email: 'user@example.com',
        roles: [],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
      }

      expect(store.userEmail).toBe('user@example.com')
    })

    it('should compute userRoles correctly', () => {
      expect(store.userRoles).toEqual([])

      store.user = {
        id: 1,
        email: 'admin@example.com',
        roles: ['ROLE_USER', 'ROLE_ADMIN'],
      } as User

      expect(store.userRoles).toEqual(['ROLE_USER', 'ROLE_ADMIN'])
    })
  })

  describe('login', () => {
    it('should login successfully', async () => {
      const credentials: LoginCredentials = {
        email: 'test@example.com',
        password: 'password123',
      }

      const authResponse: AuthResponse = {
        token: 'jwt-token',
        refreshToken: 'refresh-token',
        refreshTokenExpiration: Date.now() + 3600000,
      }

      const mockUser: User = {
        id: 1,
        email: credentials.email,
        name: 'Test User',
        roles: ['ROLE_USER'],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
      }

      vi.mocked(authService.login).mockResolvedValue(authResponse)
      vi.mocked(authService.getCurrentUser).mockResolvedValue(mockUser)

      await store.login(credentials)

      expect(authService.login).toHaveBeenCalledWith(credentials)
      expect(store.accessToken).toBe('jwt-token')
      expect(store.refreshToken).toBe('refresh-token')
      expect(store.user).toEqual(mockUser)
      expect(store.error).toBeNull()
      expect(store.isLoading).toBe(false)
      expect(store.isAuthenticated).toBe(true)
    })

    it('should handle login error', async () => {
      const credentials: LoginCredentials = {
        email: 'wrong@example.com',
        password: 'wrongpassword',
      }

      const error = new Error('Invalid credentials')
      vi.mocked(authService.login).mockRejectedValue(error)

      await expect(store.login(credentials)).rejects.toThrow('Invalid credentials')
      
      expect(store.error).toBe('Invalid credentials')
      expect(store.isLoading).toBe(false)
      expect(store.isAuthenticated).toBe(false)
    })

    it('should set loading state during login', async () => {
      const credentials: LoginCredentials = {
        email: 'test@example.com',
        password: 'password123',
      }

      vi.mocked(authService.login).mockImplementation(() => {
        expect(store.isLoading).toBe(true)
        return Promise.resolve({
          token: 'token',
          refreshToken: 'refresh',
          refreshTokenExpiration: Date.now(),
        })
      })

      vi.mocked(authService.getCurrentUser).mockResolvedValue({} as User)

      await store.login(credentials)
      expect(store.isLoading).toBe(false)
    })
  })

  describe('register', () => {
    it('should register and login successfully', async () => {
      const credentials: RegisterCredentials = {
        email: 'newuser@example.com',
        password: 'password123',
      }

      const registerResponse = {
        id: 1,
        email: credentials.email,
      }

      const authResponse: AuthResponse = {
        token: 'jwt-token',
        refreshToken: 'refresh-token',
        refreshTokenExpiration: Date.now() + 3600000,
      }

      const mockUser: User = {
        id: 1,
        email: credentials.email,
        roles: ['ROLE_USER'],
      } as User

      vi.mocked(authService.register).mockResolvedValue(registerResponse)
      vi.mocked(authService.login).mockResolvedValue(authResponse)
      vi.mocked(authService.getCurrentUser).mockResolvedValue(mockUser)

      await store.register(credentials)

      expect(authService.register).toHaveBeenCalledWith(credentials)
      expect(authService.login).toHaveBeenCalledWith({
        email: credentials.email,
        password: credentials.password,
      })
      expect(store.user).toEqual(mockUser)
      expect(store.isAuthenticated).toBe(true)
    })

    it('should handle registration error', async () => {
      const credentials: RegisterCredentials = {
        email: 'existing@example.com',
        password: 'password123',
      }

      const error = new Error('Email already exists')
      vi.mocked(authService.register).mockRejectedValue(error)

      await expect(store.register(credentials)).rejects.toThrow('Email already exists')
      
      expect(store.error).toBe('Email already exists')
      expect(store.isLoading).toBe(false)
    })
  })

  describe('logout', () => {
    it('should clear all auth data', () => {
      // Setup authenticated state
      store.user = { id: 1, email: 'test@example.com' } as User
      store.accessToken = 'token'
      store.refreshToken = 'refresh'
      store.error = 'some error'

      localStorage.setItem('access_token', 'token')
      localStorage.setItem('refresh_token', 'refresh')
      localStorage.setItem('user', JSON.stringify(store.user))

      store.logout()

      expect(store.user).toBeNull()
      expect(store.accessToken).toBeNull()
      expect(store.refreshToken).toBeNull()
      expect(store.error).toBeNull()
      expect(localStorage.getItem('access_token')).toBeNull()
      expect(localStorage.getItem('refresh_token')).toBeNull()
      expect(localStorage.getItem('user')).toBeNull()
    })
  })

  describe('fetchCurrentUser', () => {
    it('should fetch and store current user', async () => {
      const mockUser: User = {
        id: 1,
        email: 'test@example.com',
        name: 'Test User',
        roles: ['ROLE_USER'],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
      }

      vi.mocked(authService.getCurrentUser).mockResolvedValue(mockUser)

      await store.fetchCurrentUser()

      expect(authService.getCurrentUser).toHaveBeenCalled()
      expect(store.user).toEqual(mockUser)
      expect(localStorage.getItem('user')).toBe(JSON.stringify(mockUser))
    })

    it('should logout on fetch user error', async () => {
      store.accessToken = 'token'
      store.user = { id: 1 } as User

      const error = new Error('Unauthorized')
      vi.mocked(authService.getCurrentUser).mockRejectedValue(error)

      await store.fetchCurrentUser()

      expect(store.user).toBeNull()
      expect(store.accessToken).toBeNull()
    })
  })

  describe('refreshAccessToken', () => {
    it('should refresh access token successfully', async () => {
      store.refreshToken = 'old-refresh-token'

      const authResponse: AuthResponse = {
        token: 'new-jwt-token',
        refreshToken: 'new-refresh-token',
        refreshTokenExpiration: Date.now() + 3600000,
      }

      const mockUser: User = {
        id: 1,
        email: 'test@example.com',
        roles: ['ROLE_USER'],
      } as User

      vi.mocked(authService.refreshToken).mockResolvedValue(authResponse)
      vi.mocked(authService.getCurrentUser).mockResolvedValue(mockUser)

      await store.refreshAccessToken()

      expect(authService.refreshToken).toHaveBeenCalledWith({
        refreshToken: 'old-refresh-token',
      })
      expect(store.accessToken).toBe('new-jwt-token')
      expect(store.refreshToken).toBe('new-refresh-token')
    })

    it('should logout on refresh failure', async () => {
      store.refreshToken = 'invalid-token'
      store.user = { id: 1 } as User

      const error = new Error('Invalid refresh token')
      vi.mocked(authService.refreshToken).mockRejectedValue(error)

      await store.refreshAccessToken()

      expect(store.user).toBeNull()
      expect(store.accessToken).toBeNull()
      expect(store.refreshToken).toBeNull()
    })

    it('should logout if no refresh token available', async () => {
      store.refreshToken = null
      store.user = { id: 1 } as User

      await store.refreshAccessToken()

      expect(store.user).toBeNull()
      expect(authService.refreshToken).not.toHaveBeenCalled()
    })
  })

  describe('initializeAuth', () => {
    it('should initialize from localStorage', async () => {
      const mockUser: User = {
        id: 1,
        email: 'test@example.com',
        roles: ['ROLE_USER'],
      } as User

      localStorage.setItem('access_token', 'stored-token')
      localStorage.setItem('refresh_token', 'stored-refresh')
      localStorage.setItem('user', JSON.stringify(mockUser))

      vi.mocked(authService.getCurrentUser).mockResolvedValue(mockUser)

      await store.initializeAuth()

      expect(store.accessToken).toBe('stored-token')
      expect(store.refreshToken).toBe('stored-refresh')
      expect(authService.getCurrentUser).toHaveBeenCalled()
    })

    it('should not initialize if no tokens in localStorage', async () => {
      await store.initializeAuth()

      expect(store.accessToken).toBeNull()
      expect(store.refreshToken).toBeNull()
      expect(authService.getCurrentUser).not.toHaveBeenCalled()
    })

    it('should handle invalid JSON in stored user', async () => {
      localStorage.setItem('access_token', 'token')
      localStorage.setItem('refresh_token', 'refresh')
      localStorage.setItem('user', 'invalid-json')

      vi.mocked(authService.getCurrentUser).mockResolvedValue({} as User)

      await store.initializeAuth()

      expect(store.user).not.toBeNull() // Will be set by fetchCurrentUser
      expect(authService.getCurrentUser).toHaveBeenCalled()
    })
  })

  describe('localStorage integration', () => {
    it('should save tokens to localStorage on login', async () => {
      const credentials: LoginCredentials = {
        email: 'test@example.com',
        password: 'password123',
      }

      const authResponse: AuthResponse = {
        token: 'jwt-token',
        refreshToken: 'refresh-token',
        refreshTokenExpiration: 1234567890,
      }

      vi.mocked(authService.login).mockResolvedValue(authResponse)
      vi.mocked(authService.getCurrentUser).mockResolvedValue({} as User)

      await store.login(credentials)

      expect(localStorage.getItem('access_token')).toBe('jwt-token')
      expect(localStorage.getItem('refresh_token')).toBe('refresh-token')
      expect(localStorage.getItem('refresh_token_expiration')).toBe('1234567890')
    })
  })
})

