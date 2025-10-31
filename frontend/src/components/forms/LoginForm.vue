<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Message from 'primevue/message'
import BaseButton from '@/components/ui/BaseButton.vue'
import GoogleLoginButton from '@/components/auth/GoogleLoginButton.vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'
import { useFormValidation } from '@/composables/useFormValidation'
import type { LoginCredentials } from '@/types/auth.types'
import { useLoaderStore } from '@/stores/loader.store'

const router = useRouter()
const { t } = useI18n()
const { login, isLoading } = useAuth()
const { showSuccess, showError } = useToast()
const { errors, validateField, clearFieldError, emailRules, passwordRules } = useFormValidation()
const loaderStore = useLoaderStore()

const formData = ref<LoginCredentials>({
  email: '',
  password: ''
})

const formError = ref<string | null>(null)

const isFormValid = computed(() => {
  return formData.value.email.length > 0 && formData.value.password.length > 0
})

function validateEmailField(): void {
  validateField('email', formData.value.email, emailRules)
}

function validatePasswordField(): void {
  validateField('password', formData.value.password, passwordRules)
}

async function handleSubmit(): Promise<void> {
  formError.value = null
  
  // Validate all fields
  validateEmailField()
  validatePasswordField()

  if (Object.keys(errors.value).length > 0) {
    return
  }

  try {
    await login(formData.value)
    showSuccess(t('success.login_success'), t('common.welcome'))
    loaderStore.show()
    router.push('/dashboard')
  } catch (error: any) {
    // Extract message from API response
    const errorMessage = error?.response?.data?.message || error?.message || t('errors.login_failed')
    formError.value = errorMessage
    showError(errorMessage, t('errors.error'))
  }
}
</script>

<template>
  <form class="login-form" @submit.prevent="handleSubmit">
    <div class="form-group">
      <label for="email" class="form-label">
        {{ t('common.email') }}
        <span class="form-label__required">*</span>
      </label>
      <InputText
        id="email"
        v-model="formData.email"
        type="email"
        :placeholder="t('auth.login_subtitle')"
        :class="{ 'p-invalid': errors.email }"
        :disabled="isLoading"
        class="form-input"
        @blur="validateEmailField"
        @input="clearFieldError('email')"
      />
      <small v-if="errors.email" class="form-error">
        {{ errors.email }}
      </small>
    </div>

    <div class="form-group">
      <label for="password" class="form-label">
        {{ t('common.password') }}
        <span class="form-label__required">*</span>
      </label>
      <Password
        id="password"
        v-model="formData.password"
        :placeholder="t('common.password')"
        :class="{ 'p-invalid': errors.password }"
        :disabled="isLoading"
        :feedback="false"
        toggle-mask
        class="form-input"
        input-class="w-full"
        @blur="validatePasswordField"
        @input="clearFieldError('password')"
      />
      <small v-if="errors.password" class="form-error">
        {{ errors.password }}
      </small>
    </div>

    <div v-if="formError" class="form-message">
      <Message severity="error" :closable="false">{{ formError }}</Message>
    </div>

    <div class="form-actions">
      <BaseButton
        type="submit"
        :label="t('auth.sign_in')"
        :loading="isLoading"
        :disabled="!isFormValid || isLoading"
        full-width
        size="large"
      >
        <i class="pi pi-sign-in"></i>
        {{ t('auth.sign_in') }}
      </BaseButton>
    </div>

    <div class="form-divider">
      <span class="form-divider__text">{{ t('common.or') }}</span>
    </div>

    <div class="form-social">
      <GoogleLoginButton />
    </div>
  </form>
</template>

<style scoped>
.login-form {
  width: 100%;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9375rem;
}

.form-label__required {
  color: var(--error);
}

.form-input {
  width: 100%;
}

/* Стили для InputText */
.form-input:deep(.p-inputtext),
.form-input.p-inputtext {
  width: 100%;
  padding: 0.875rem 1rem;
  font-size: 1rem;
  border-radius: 10px;
  border: 2px solid var(--border-color);
  transition: all var(--transition-base);
  background: var(--bg-primary);
}

.form-input:deep(.p-inputtext:focus),
.form-input.p-inputtext:focus {
  border-color: var(--primary-500);
  box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
  outline: none;
}

.form-input:deep(.p-inputtext.p-invalid),
.form-input.p-inputtext.p-invalid {
  border-color: var(--error);
}

/* Стили для Password компонента */
.form-input:deep(.p-password) {
  width: 100%;
}

.form-input:deep(.p-password-input) {
  width: 100%;
  padding: 0.875rem 1rem;
  padding-right: 3rem;
  font-size: 1rem;
  border-radius: 10px;
  border: 2px solid var(--border-color);
  transition: all var(--transition-base);
  background: var(--bg-primary);
}

.form-input:deep(.p-password-input:focus) {
  border-color: var(--primary-500);
  box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
  outline: none;
}

.form-input:deep(.p-password-input.p-invalid) {
  border-color: var(--error);
}

/* Обертка для Password */
.form-input:deep(.p-password) {
  position: relative;
  display: flex;
  align-items: center;
}

/* Иконка показа пароля */
.form-input:deep(.p-password-toggle-mask),
.form-input:deep(.p-password .p-button) {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  padding: 0.5rem;
  background: transparent !important;
  border: none !important;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.form-input:deep(.p-password .p-button:hover) {
  background: transparent !important;
}

.form-input:deep(.p-password .p-button .p-icon) {
  color: var(--text-secondary);
  width: 1.25rem;
  height: 1.25rem;
}

.form-error {
  display: block;
  margin-top: 0.375rem;
  color: var(--error);
  font-size: 0.875rem;
}

.form-message {
  margin-bottom: 1.5rem;
}

.form-message :deep(.p-message) {
  padding: 1rem;
  border-radius: 10px;
  margin: 0;
}

.form-message :deep(.p-message-wrapper) {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.form-message :deep(.p-message-icon) {
  font-size: 1.25rem;
}

.form-message :deep(.p-message-text) {
  font-size: 0.9375rem;
  line-height: 1.5;
}

.form-actions {
  margin-top: 2rem;
}

.form-divider {
  position: relative;
  margin: 2rem 0;
  text-align: center;
}

.form-divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: var(--border-color);
}

.form-divider__text {
  position: relative;
  display: inline-block;
  padding: 0 1rem;
  background: var(--bg-primary);
  color: var(--text-secondary);
  font-size: 0.875rem;
  font-weight: 500;
}

.form-social {
  margin-top: 1rem;
}

/* Animations */
.form-group {
  animation: slideUp 0.4s ease-out;
  animation-fill-mode: both;
}

.form-group:nth-child(1) {
  animation-delay: 0.1s;
}

.form-group:nth-child(2) {
  animation-delay: 0.2s;
}

.form-actions {
  animation: slideUp 0.4s ease-out 0.3s;
  animation-fill-mode: both;
}

/* Responsive */
@media (max-width: 768px) {
  .form-group {
    margin-bottom: 1.25rem;
  }

  .form-input :deep(.p-inputtext) {
    padding: 0.75rem 0.875rem;
    font-size: 0.9375rem;
  }
}
</style>

