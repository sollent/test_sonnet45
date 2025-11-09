import { apiClient } from './api.service'
import { API_ENDPOINTS } from '@/config/constants'
import type {
  LoginCredentials,
  RegisterCredentials,
  AuthResponse,
  User,
  RefreshTokenRequest
} from '@/types/auth.types'

class AuthService {
  async login(credentials: LoginCredentials): Promise<AuthResponse> {
    const { data } = await apiClient.post<AuthResponse>(
      API_ENDPOINTS.AUTH.LOGIN,
      credentials
    )
    return data
  }

  async register(credentials: RegisterCredentials): Promise<{ id: number; email: string }> {
    const { data } = await apiClient.post(API_ENDPOINTS.AUTH.REGISTER, credentials)
    return data
  }

  async refreshToken(request: RefreshTokenRequest): Promise<AuthResponse> {
    const { data } = await apiClient.post<AuthResponse>(
      API_ENDPOINTS.AUTH.REFRESH,
      request
    )
    return data
  }

  async getCurrentUser(): Promise<User> {
    const { data } = await apiClient.get<User>(API_ENDPOINTS.USER.ME)
    return data
  }

  async loginWithGoogle(credential: string): Promise<AuthResponse> {
    const { data } = await apiClient.post<AuthResponse>(API_ENDPOINTS.AUTH.GOOGLE, {
      credential
    })
    return data
  }
}

export const authService = new AuthService()

