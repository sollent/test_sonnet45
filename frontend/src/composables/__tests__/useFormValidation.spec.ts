import { describe, it, expect, beforeEach } from 'vitest'
import { defineComponent, h } from 'vue'
import { mount } from '@vue/test-utils'
import { useFormValidation } from '../useFormValidation'

describe('useFormValidation', () => {
  let validation: ReturnType<typeof useFormValidation>

  beforeEach(() => {
    // Wrap composable in a component to provide Vue context
    const TestComponent = defineComponent({
      setup() {
        validation = useFormValidation()
        return () => h('div')
      }
    })
    mount(TestComponent)
  })

  describe('validateEmail', () => {
    it('should return true for valid email addresses', () => {
      expect(validation.validateEmail('test@example.com')).toBe(true)
      expect(validation.validateEmail('user.name@domain.co.uk')).toBe(true)
      expect(validation.validateEmail('user+tag@example.com')).toBe(true)
    })

    it('should return false for invalid email addresses', () => {
      expect(validation.validateEmail('invalid')).toBe(false)
      expect(validation.validateEmail('test@')).toBe(false)
      expect(validation.validateEmail('@example.com')).toBe(false)
      expect(validation.validateEmail('test @example.com')).toBe(false)
      expect(validation.validateEmail('')).toBe(false)
    })
  })

  describe('validatePassword', () => {
    it('should return true for passwords within valid length range', () => {
      expect(validation.validatePassword('123456')).toBe(true)
      expect(validation.validatePassword('ValidPass123')).toBe(true)
      expect(validation.validatePassword('a'.repeat(40))).toBe(true)
    })

    it('should return false for passwords too short', () => {
      expect(validation.validatePassword('')).toBe(false)
      expect(validation.validatePassword('12345')).toBe(false)
    })

    it('should return false for passwords too long', () => {
      expect(validation.validatePassword('a'.repeat(129))).toBe(false)
    })
  })

  describe('validateField', () => {
    it('should validate field with custom rules', () => {
      const rules = [
        {
          validator: (value: string) => value.length > 0,
          message: 'Field is required'
        },
        {
          validator: (value: string) => value.length >= 3,
          message: 'Must be at least 3 characters'
        }
      ]

      expect(validation.validateField('username', 'john', rules)).toBe(true)
      expect(validation.errors.value).not.toHaveProperty('username')
    })

    it('should set error when validation fails', () => {
      const rules = [
        {
          validator: (value: string) => value.length > 0,
          message: 'Field is required'
        }
      ]

      expect(validation.validateField('username', '', rules)).toBe(false)
      expect(validation.errors.value.username).toBe('Field is required')
    })

    it('should stop at first failing rule', () => {
      const rules = [
        {
          validator: (value: string) => value.length > 0,
          message: 'Field is required'
        },
        {
          validator: (value: string) => value.length >= 5,
          message: 'Must be at least 5 characters'
        }
      ]

      validation.validateField('username', '', rules)
      expect(validation.errors.value.username).toBe('Field is required')
    })
  })

  describe('error management', () => {
    it('should track hasErrors correctly', () => {
      expect(validation.hasErrors.value).toBe(false)

      validation.setError('email', 'Invalid email')
      expect(validation.hasErrors.value).toBe(true)

      validation.clearErrors()
      expect(validation.hasErrors.value).toBe(false)
    })

    it('should set error for specific field', () => {
      validation.setError('email', 'Email is invalid')
      
      expect(validation.errors.value.email).toBe('Email is invalid')
      expect(validation.hasErrors.value).toBe(true)
    })

    it('should clear all errors', () => {
      validation.setError('email', 'Email error')
      validation.setError('password', 'Password error')
      
      expect(Object.keys(validation.errors.value).length).toBe(2)
      
      validation.clearErrors()
      
      expect(Object.keys(validation.errors.value).length).toBe(0)
      expect(validation.hasErrors.value).toBe(false)
    })

    it('should clear specific field error', () => {
      validation.setError('email', 'Email error')
      validation.setError('password', 'Password error')
      
      validation.clearFieldError('email')
      
      expect(validation.errors.value).not.toHaveProperty('email')
      expect(validation.errors.value.password).toBe('Password error')
    })
  })

  describe('predefined rules', () => {
    it('should have email rules', () => {
      expect(validation.emailRules).toBeDefined()
      expect(validation.emailRules.length).toBeGreaterThan(0)
    })

    it('should validate email with emailRules', () => {
      expect(validation.validateField('email', 'test@example.com', validation.emailRules)).toBe(true)
      expect(validation.validateField('email', 'invalid', validation.emailRules)).toBe(false)
      expect(validation.errors.value.email).toBeTruthy()
    })

    it('should have password rules', () => {
      expect(validation.passwordRules).toBeDefined()
      expect(validation.passwordRules.length).toBeGreaterThan(0)
    })

    it('should validate password with passwordRules', () => {
      expect(validation.validateField('password', 'validpass123', validation.passwordRules)).toBe(true)
      expect(validation.validateField('password', '123', validation.passwordRules)).toBe(false)
      expect(validation.errors.value.password).toBeTruthy()
    })
  })

  describe('reactivity', () => {
    it('should update errors reactively', () => {
      const initialErrorCount = Object.keys(validation.errors.value).length
      
      validation.setError('field1', 'Error 1')
      expect(Object.keys(validation.errors.value).length).toBe(initialErrorCount + 1)
      
      validation.setError('field2', 'Error 2')
      expect(Object.keys(validation.errors.value).length).toBe(initialErrorCount + 2)
    })

    it('should update hasErrors reactively', () => {
      expect(validation.hasErrors.value).toBe(false)
      
      validation.setError('test', 'Test error')
      expect(validation.hasErrors.value).toBe(true)
      
      validation.clearFieldError('test')
      expect(validation.hasErrors.value).toBe(false)
    })
  })
})
