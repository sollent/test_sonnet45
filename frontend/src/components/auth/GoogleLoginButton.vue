<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { GoogleLogin } from 'vue3-google-login'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { GOOGLE_CONFIG } from '@/config/google'

const router = useRouter()
const { t } = useI18n()
const { loginWithGoogle } = useAuth()
const { showSuccess, showError } = useToast()
const isLoading = ref(false)

interface CredentialResponse {
  credential: string
}

async function handleGoogleLogin(response: CredentialResponse) {
  if (!response.credential) {
    showError(t('errors.google_auth_failed'))
    return
  }

  isLoading.value = true

  try {
    await loginWithGoogle(response.credential)
    showSuccess(t('success.login_success'), t('common.welcome'))
    router.push('/dashboard')
  } catch (error: any) {
    console.error('Google login error:', error)
    const errorMessage = error.response?.data?.message || t('errors.google_auth_failed')
    showError(errorMessage)
  } finally {
    isLoading.value = false
  }
}

function handleGoogleError() {
  showError(t('errors.google_auth_failed'))
}
</script>

<template>
  <div class="google-login-button">
    <GoogleLogin
      :client-id="GOOGLE_CONFIG.CLIENT_ID"
      :callback="handleGoogleLogin"
      :error="handleGoogleError"
      :button-config="{
        theme: 'outline',
        size: 'large',
        text: 'continue_with',
        shape: 'rectangular',
        width: 400
      }"
      :loading="isLoading"
    />
  </div>
</template>

<style scoped>
.google-login-button {
  width: 100%;
}

.google-login-button :deep(.abcRioButton) {
  width: 100% !important;
}

.google-login-button :deep(.abcRioButtonContents) {
  width: 100% !important;
}

.google-login-button :deep(div[role="button"]) {
  width: 100% !important;
  min-width: 100% !important;
  max-width: 100% !important;
}

.google-login-button :deep(button) {
  width: 100% !important;
  min-width: 100% !important;
  max-width: 100% !important;
  border-radius: 8px !important;
  transition: all 0.3s ease;
  height: 48px !important;
  font-size: 16px !important;
}

.google-login-button :deep(iframe) {
  width: 100% !important;
}

.google-login-button :deep(button:hover) {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
}

.google-login-button :deep(button:active) {
  transform: translateY(0);
}
</style>

