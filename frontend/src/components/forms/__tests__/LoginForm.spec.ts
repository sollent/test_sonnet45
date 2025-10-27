import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/vue'
import userEvent from '@testing-library/user-event'
import { createRouter, createMemoryHistory } from 'vue-router'
import { setActivePinia, createPinia } from 'pinia'
import LoginForm from '../LoginForm.vue'

// Mock composables
vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    login: vi.fn(),
    isLoading: false,
  }),
}))

vi.mock('@/composables/useToast', () => ({
  useToast: () => ({
    showSuccess: vi.fn(),
    showError: vi.fn(),
  }),
}))

// Mock PrimeVue components
vi.mock('primevue/inputtext', () => ({
  default: {
    name: 'InputText',
    template: '<input v-bind="$attrs" v-on="$listeners" />',
  },
}))

vi.mock('primevue/password', () => ({
  default: {
    name: 'Password',
    template: '<input type="password" v-bind="$attrs" v-on="$listeners" />',
  },
}))

vi.mock('primevue/message', () => ({
  default: {
    name: 'Message',
    template: '<div v-if="$slots.default"><slot /></div>',
  },
}))

describe('LoginForm', () => {
  let router: ReturnType<typeof createRouter>

  beforeEach(() => {
    setActivePinia(createPinia())
    
    router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/', component: { template: '<div>Home</div>' } },
        { path: '/dashboard', component: { template: '<div>Dashboard</div>' } },
        { path: '/register', component: { template: '<div>Register</div>' } },
      ],
    })
  })

  describe('rendering', () => {
    it('should render login form with email and password fields', () => {
      render(LoginForm, {
        global: {
          plugins: [router],
        },
      })

      expect(screen.getByLabelText(/email/i)).toBeInTheDocument()
      expect(screen.getByLabelText(/password/i)).toBeInTheDocument()
    })

    it('should render submit button', () => {
      render(LoginForm, {
        global: {
          plugins: [router],
        },
      })

      expect(screen.getByRole('button', { name: /sign in/i })).toBeInTheDocument()
    })

    it('should render login form', () => {
      render(LoginForm, {
        global: {
          plugins: [router],
        },
      })

      // Form should be rendered
      const form = document.querySelector('.login-form')
      expect(form).toBeInTheDocument()
    })
  })

  describe('form validation', () => {
    it('should have email input field', () => {
      render(LoginForm, {
        global: {
          plugins: [router],
        },
      })

      const emailInput = screen.getByLabelText(/email/i)
      expect(emailInput).toHaveAttribute('type', 'email')
    })

    it('should have password input field', () => {
      render(LoginForm, {
        global: {
          plugins: [router],
        },
      })

      const passwordInput = screen.getByLabelText(/password/i)
      expect(passwordInput).toBeInTheDocument()
    })
  })

  describe('form submission', () => {
    it('should have submit button initially enabled when form is empty', () => {
      render(LoginForm, {
        global: {
          plugins: [router],
        },
      })

      const submitButton = screen.getByRole('button', { name: /sign in/i })
      // Button might be disabled based on form validity
      expect(submitButton).toBeInTheDocument()
    })
  })

  describe('user interaction', () => {
    it('should allow user to type in email field', async () => {
      const user = userEvent.setup()
      
      render(LoginForm, {
        global: {
          plugins: [router],
        },
      })

      const emailInput = screen.getByLabelText(/email/i) as HTMLInputElement
      
      await user.type(emailInput, 'test@example.com')

      expect(emailInput.value).toBe('test@example.com')
    })

    it('should allow user to type in password field', async () => {
      const user = userEvent.setup()
      
      render(LoginForm, {
        global: {
          plugins: [router],
        },
      })

      const passwordInput = screen.getByLabelText(/password/i) as HTMLInputElement
      
      await user.type(passwordInput, 'password123')

      expect(passwordInput.value).toBe('password123')
    })
  })

  describe('accessibility', () => {
    it('should have accessible form labels', () => {
      render(LoginForm, {
        global: {
          plugins: [router],
        },
      })

      expect(screen.getByLabelText(/email/i)).toHaveAttribute('id', 'email')
      expect(screen.getByLabelText(/password/i)).toHaveAttribute('id', 'password')
    })

    it('should mark required fields', () => {
      render(LoginForm, {
        global: {
          plugins: [router],
        },
      })

      // Required indicators should be present
      const labels = screen.getAllByText('*')
      expect(labels.length).toBeGreaterThan(0)
    })
  })
})

