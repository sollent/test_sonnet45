export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8089'
export const APP_TITLE = import.meta.env.VITE_APP_TITLE || 'Auth App'

export const STORAGE_KEYS = {
  ACCESS_TOKEN: 'access_token',
  REFRESH_TOKEN: 'refresh_token',
  REFRESH_TOKEN_EXPIRATION: 'refresh_token_expiration',
  USER: 'user'
} as const

export const API_ENDPOINTS = {
  AUTH: {
    LOGIN: '/api/auth',
    REGISTER: '/api/users',
    REFRESH: '/api/token/refresh',
    GOOGLE: '/api/auth/google'
  },
  USER: {
    ME: '/api/users/me'
  }
} as const

export const ROUTES = {
  HOME: '/',
  LOGIN: '/login',
  REGISTER: '/register',
  DASHBOARD: '/dashboard',
  PROFILE: '/profile'
} as const

export const PASSWORD_CONSTRAINTS = {
  MIN_LENGTH: 6,
  MAX_LENGTH: 40
} as const

