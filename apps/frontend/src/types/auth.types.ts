export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterCredentials {
  email: string
  password: string
}

export interface AuthResponse {
  token: string
  refreshToken: string
  refreshTokenExpiration: number
}

export interface User {
  id: number
  email: string
  name?: string | null
  roles: string[]
  createdAt: string
  updatedAt: string
  isEmailVerified?: boolean
}

export interface RefreshTokenRequest {
  refreshToken: string
}

export interface ApiError {
  message: string
  code: number
}

