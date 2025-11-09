import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useToast } from '../useToast'

// Mock PrimeVue's useToast
vi.mock('primevue/usetoast', () => ({
  useToast: vi.fn(() => ({
    add: vi.fn(),
  })),
}))

// Mock vue-i18n
vi.mock('vue-i18n', () => ({
  useI18n: vi.fn(() => ({
    t: vi.fn((key: string) => {
      const translations: Record<string, string> = {
        'common.success': 'Success',
        'common.error': 'Error',
        'common.info': 'Info',
        'common.warning': 'Warning'
      }
      return translations[key] || key
    })
  }))
}))

import { useToast as usePrimeToast } from 'primevue/usetoast'

describe('useToast', () => {
  let mockAdd: ReturnType<typeof vi.fn>
  let toast: ReturnType<typeof useToast>

  beforeEach(() => {
    mockAdd = vi.fn()
    vi.mocked(usePrimeToast).mockReturnValue({
      add: mockAdd,
    } as any)
    
    toast = useToast()
  })

  describe('showSuccess', () => {
    it('should show success toast with custom message', () => {
      toast.showSuccess('Operation completed successfully')

      expect(mockAdd).toHaveBeenCalledWith({
        severity: 'success',
        summary: 'Success',
        detail: 'Operation completed successfully',
        life: 3000,
      })
    })

    it('should show success toast with custom title', () => {
      toast.showSuccess('User created', 'Account Created')

      expect(mockAdd).toHaveBeenCalledWith({
        severity: 'success',
        summary: 'Account Created',
        detail: 'User created',
        life: 3000,
      })
    })

    it('should use default title when not provided', () => {
      toast.showSuccess('Done!')

      expect(mockAdd).toHaveBeenCalledWith(
        expect.objectContaining({
          summary: 'Success',
        })
      )
    })
  })

  describe('showError', () => {
    it('should show error toast with custom message', () => {
      toast.showError('Something went wrong')

      expect(mockAdd).toHaveBeenCalledWith({
        severity: 'error',
        summary: 'Error',
        detail: 'Something went wrong',
        life: 5000,
      })
    })

    it('should show error toast with custom title', () => {
      toast.showError('Network error', 'Connection Failed')

      expect(mockAdd).toHaveBeenCalledWith({
        severity: 'error',
        summary: 'Connection Failed',
        detail: 'Network error',
        life: 5000,
      })
    })

    it('should use longer life for error toasts', () => {
      toast.showError('Error message')

      expect(mockAdd).toHaveBeenCalledWith(
        expect.objectContaining({
          life: 5000,
        })
      )
    })
  })

  describe('showInfo', () => {
    it('should show info toast with custom message', () => {
      toast.showInfo('New feature available')

      expect(mockAdd).toHaveBeenCalledWith({
        severity: 'info',
        summary: 'Info',
        detail: 'New feature available',
        life: 3000,
      })
    })

    it('should show info toast with custom title', () => {
      toast.showInfo('Check your email', 'Verification Sent')

      expect(mockAdd).toHaveBeenCalledWith({
        severity: 'info',
        summary: 'Verification Sent',
        detail: 'Check your email',
        life: 3000,
      })
    })
  })

  describe('showWarn', () => {
    it('should show warning toast with custom message', () => {
      toast.showWarn('Your session will expire soon')

      expect(mockAdd).toHaveBeenCalledWith({
        severity: 'warn',
        summary: 'Warning',
        detail: 'Your session will expire soon',
        life: 4000,
      })
    })

    it('should show warning toast with custom title', () => {
      toast.showWarn('Low storage space', 'Storage Warning')

      expect(mockAdd).toHaveBeenCalledWith({
        severity: 'warn',
        summary: 'Storage Warning',
        detail: 'Low storage space',
        life: 4000,
      })
    })

    it('should use medium life for warning toasts', () => {
      toast.showWarn('Warning message')

      expect(mockAdd).toHaveBeenCalledWith(
        expect.objectContaining({
          life: 4000,
        })
      )
    })
  })

  describe('toast severities', () => {
    it('should call toast.add for each severity type', () => {
      toast.showSuccess('Success')
      toast.showError('Error')
      toast.showInfo('Info')
      toast.showWarn('Warning')

      expect(mockAdd).toHaveBeenCalledTimes(4)
    })

    it('should use correct severity for each type', () => {
      toast.showSuccess('msg')
      expect(mockAdd).toHaveBeenCalledWith(expect.objectContaining({ severity: 'success' }))

      toast.showError('msg')
      expect(mockAdd).toHaveBeenCalledWith(expect.objectContaining({ severity: 'error' }))

      toast.showInfo('msg')
      expect(mockAdd).toHaveBeenCalledWith(expect.objectContaining({ severity: 'info' }))

      toast.showWarn('msg')
      expect(mockAdd).toHaveBeenCalledWith(expect.objectContaining({ severity: 'warn' }))
    })
  })

  describe('toast lifetimes', () => {
    it('should use correct life duration for each severity', () => {
      toast.showSuccess('msg')
      expect(mockAdd).toHaveBeenLastCalledWith(expect.objectContaining({ life: 3000 }))

      toast.showError('msg')
      expect(mockAdd).toHaveBeenLastCalledWith(expect.objectContaining({ life: 5000 }))

      toast.showInfo('msg')
      expect(mockAdd).toHaveBeenLastCalledWith(expect.objectContaining({ life: 3000 }))

      toast.showWarn('msg')
      expect(mockAdd).toHaveBeenLastCalledWith(expect.objectContaining({ life: 4000 }))
    })
  })
})

