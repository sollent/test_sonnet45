import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { PASSWORD_CONSTRAINTS } from '@/config/constants'

interface ValidationRule {
  validator: (value: string) => boolean
  message: string
}

export function useFormValidation() {
  const { t } = useI18n()
  const errors = ref<Record<string, string>>({})

  const hasErrors = computed(() => Object.keys(errors.value).length > 0)

  function validateEmail(email: string): boolean {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(email)
  }

  function validatePassword(password: string): boolean {
    return (
      password.length >= PASSWORD_CONSTRAINTS.MIN_LENGTH &&
      password.length <= PASSWORD_CONSTRAINTS.MAX_LENGTH
    )
  }

  function validateField(
    fieldName: string,
    value: string,
    rules: ValidationRule[]
  ): boolean {
    for (const rule of rules) {
      if (!rule.validator(value)) {
        errors.value[fieldName] = rule.message
        return false
      }
    }
    
    delete errors.value[fieldName]
    return true
  }

  function clearErrors(): void {
    errors.value = {}
  }

  function clearFieldError(fieldName: string): void {
    delete errors.value[fieldName]
  }

  function setError(fieldName: string, message: string): void {
    errors.value[fieldName] = message
  }

  const emailRules: ValidationRule[] = [
    {
      validator: (value: string) => value.length > 0,
      message: t('validation.email_required')
    },
    {
      validator: validateEmail,
      message: t('validation.email_invalid')
    }
  ]

  const passwordRules: ValidationRule[] = [
    {
      validator: (value: string) => value.length > 0,
      message: t('validation.password_required')
    },
    {
      validator: (value: string) => value.length >= PASSWORD_CONSTRAINTS.MIN_LENGTH,
      message: t('validation.password_min_length')
    },
    {
      validator: (value: string) => value.length <= PASSWORD_CONSTRAINTS.MAX_LENGTH,
      message: t('validation.password_max_length')
    }
  ]

  return {
    errors,
    hasErrors,
    validateEmail,
    validatePassword,
    validateField,
    clearErrors,
    clearFieldError,
    setError,
    emailRules,
    passwordRules
  }
}

