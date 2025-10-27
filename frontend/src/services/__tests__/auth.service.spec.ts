import { describe, it, expect, vi, beforeEach } from 'vitest'
import { authService } from '../auth.service'
import { apiClient } from '../api.service'
import type { LoginCredentials, RegisterCredentials, AuthResponse, User } from '@/types/auth.types'

vi.mock('../api.service', () => ({
  apiClient: {
    post: vi.fn(),
    get: vi.fn(),
  },
}))

describe('AuthService', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('login', () => {
    it('should successfully login with valid credentials', async () => {
      const credentials: LoginCredentials = {
        email: 'test@example.com',
        password: 'password123',
      }

      const mockResponse: AuthResponse = {
        token: 'mock-jwt-token',
        refreshToken: 'mock-refresh-token',
        refreshTokenExpiration: Date.now() + 3600000,
      }

      vi.mocked(apiClient.post).mockResolvedValue({ data: mockResponse })

      const result = await authService.login(credentials)

      expect(apiClient.post).toHaveBeenCalledWith('/api/auth', credentials)
      expect(result).toEqual(mockResponse)
      expect(result.token).toBe('mock-jwt-token')
      expect(result.refreshToken).toBe('mock-refresh-token')
    })

    it('should throw error on failed login', async () => {
      const credentials: LoginCredentials = {
        email: 'wrong@example.com',
        password: 'wrongpassword',
      }

      const mockError = new Error('Invalid credentials')
      vi.mocked(apiClient.post).mockRejectedValue(mockError)

      await expect(authService.login(credentials)).rejects.toThrow('Invalid credentials')
      expect(apiClient.post).toHaveBeenCalledWith('/api/auth', credentials)
    })
  })

  describe('register', () => {
    it('should successfully register new user', async () => {
      const credentials: RegisterCredentials = {
        email: 'newuser@example.com',
        password: 'password123',
      }

      const mockResponse = {
        id: 1,
        email: 'newuser@example.com',
      }

      vi.mocked(apiClient.post).mockResolvedValue({ data: mockResponse })

      const result = await authService.register(credentials)

      expect(apiClient.post).toHaveBeenCalledWith('/api/users', credentials)
      expect(result).toEqual(mockResponse)
      expect(result.id).toBe(1)
      expect(result.email).toBe('newuser@example.com')
    })

    it('should throw error when email already exists', async () => {
      const credentials: RegisterCredentials = {
        email: 'existing@example.com',
        password: 'password123',
      }

      const mockError = new Error('Email already exists')
      vi.mocked(apiClient.post).mockRejectedValue(mockError)

      await expect(authService.register(credentials)).rejects.toThrow('Email already exists')
    })
  })

  describe('refreshToken', () => {
    it('should successfully refresh access token', async () => {
      const request = {
        refreshToken: 'old-refresh-token',
      }

      const mockResponse: AuthResponse = {
        token: 'new-jwt-token',
        refreshToken: 'new-refresh-token',
        refreshTokenExpiration: Date.now() + 3600000,
      }

      vi.mocked(apiClient.post).mockResolvedValue({ data: mockResponse })

      const result = await authService.refreshToken(request)

      expect(apiClient.post).toHaveBeenCalledWith('/api/token/refresh', request)
      expect(result.token).toBe('new-jwt-token')
      expect(result.refreshToken).toBe('new-refresh-token')
    })

    it('should throw error when refresh token is invalid', async () => {
      const request = {
        refreshToken: 'invalid-refresh-token',
      }

      const mockError = new Error('Invalid refresh token')
      vi.mocked(apiClient.post).mockRejectedValue(mockError)

      await expect(authService.refreshToken(request)).rejects.toThrow('Invalid refresh token')
    })
  })

  describe('getCurrentUser', () => {
    it('should fetch current user profile', async () => {
      const mockUser: User = {
        id: 1,
        email: 'user@example.com',
        name: 'Test User',
        roles: ['ROLE_USER'],
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString(),
      }

      vi.mocked(apiClient.get).mockResolvedValue({ data: mockUser })

      const result = await authService.getCurrentUser()

      expect(apiClient.get).toHaveBeenCalledWith('/api/users/me')
      expect(result).toEqual(mockUser)
      expect(result.email).toBe('user@example.com')
      expect(result.roles).toContain('ROLE_USER')
    })

    it('should throw error when user is not authenticated', async () => {
      const mockError = new Error('Unauthorized')
      vi.mocked(apiClient.get).mockRejectedValue(mockError)

      await expect(authService.getCurrentUser()).rejects.toThrow('Unauthorized')
    })
  })

  describe('loginWithGoogle', () => {
    it('should successfully login with Google ID token', async () => {
      const idToken = 'google-id-token-mock'

      const mockResponse: AuthResponse = {
        token: 'jwt-token-from-google',
        refreshToken: 'refresh-token-from-google',
        refreshTokenExpiration: Date.now() + 3600000,
      }

      vi.mocked(apiClient.post).mockResolvedValue({ data: mockResponse })

      const result = await authService.loginWithGoogle(idToken)

      expect(apiClient.post).toHaveBeenCalledWith('/api/auth/google', {
        credential: idToken,
      })
      expect(result).toEqual(mockResponse)
      expect(result.token).toBe('jwt-token-from-google')
    })

    it('should throw error with invalid Google ID token', async () => {
      const idToken = 'invalid-google-token'

      const mockError = new Error('Invalid Google token')
      vi.mocked(apiClient.post).mockRejectedValue(mockError)

      await expect(authService.loginWithGoogle(idToken)).rejects.toThrow('Invalid Google token')
    })
  })

  describe('api call parameters', () => {
    it('should use correct endpoints', async () => {
      const credentials = { email: 'test@example.com', password: 'pass' }
      
      vi.mocked(apiClient.post).mockResolvedValue({ data: {} })
      vi.mocked(apiClient.get).mockResolvedValue({ data: {} })

      await authService.login(credentials)
      expect(apiClient.post).toHaveBeenCalledWith('/api/auth', expect.any(Object))

      await authService.register(credentials)
      expect(apiClient.post).toHaveBeenCalledWith('/api/users', expect.any(Object))

      await authService.refreshToken({ refreshToken: 'token' })
      expect(apiClient.post).toHaveBeenCalledWith('/api/token/refresh', expect.any(Object))

      await authService.getCurrentUser()
      expect(apiClient.get).toHaveBeenCalledWith('/api/users/me')

      await authService.loginWithGoogle('token')
      expect(apiClient.post).toHaveBeenCalledWith('/api/auth/google', expect.any(Object))
    })
  })
})

