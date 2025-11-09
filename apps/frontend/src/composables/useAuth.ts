import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth.store'
import type { LoginCredentials, RegisterCredentials } from '@/types/auth.types'

export function useAuth() {
  const authStore = useAuthStore()

  const isAuthenticated = computed(() => authStore.isAuthenticated)
  const user = computed(() => authStore.user)
  const isLoading = computed(() => authStore.isLoading)
  const error = computed(() => authStore.error)

  async function login(credentials: LoginCredentials): Promise<void> {
    return authStore.login(credentials)
  }

  async function register(credentials: RegisterCredentials): Promise<void> {
    return authStore.register(credentials)
  }

  async function loginWithGoogle(credential: string): Promise<void> {
    return authStore.loginWithGoogle(credential)
  }

  function logout(): void {
    authStore.logout()
  }

  return {
    isAuthenticated,
    user,
    isLoading,
    error,
    login,
    register,
    loginWithGoogle,
    logout
  }
}

