import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen } from '@testing-library/vue'
import { createRouter, createMemoryHistory } from 'vue-router'
import { setActivePinia, createPinia } from 'pinia'
import HomeView from '../HomeView.vue'

// Mock composables
const mockIsAuthenticated = vi.fn(() => false)

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    isAuthenticated: mockIsAuthenticated(),
  }),
}))

describe('HomeView', () => {
  let router: ReturnType<typeof createRouter>

  beforeEach(() => {
    setActivePinia(createPinia())
    mockIsAuthenticated.mockReturnValue(false)
    
    router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/', component: { template: '<div>Home</div>' } },
        { path: '/register', component: { template: '<div>Register</div>' } },
        { path: '/login', component: { template: '<div>Login</div>' } },
        { path: '/dashboard', component: { template: '<div>Dashboard</div>' } },
      ],
    })
  })

  describe('rendering', () => {
    it('should render welcome message', () => {
      render(HomeView, {
        global: {
          plugins: [router],
        },
      })

      expect(screen.getByText(/welcome to/i)).toBeInTheDocument()
      expect(screen.getByText(/auth app/i)).toBeInTheDocument()
    })

    it('should render app description', () => {
      render(HomeView, {
        global: {
          plugins: [router],
        },
      })

      expect(screen.getByText(/A modern authentication system built with Vue\.js and Symfony/i)).toBeInTheDocument()
    })

    it('should render hero section', () => {
      render(HomeView, {
        global: {
          plugins: [router],
        },
      })

      const view = document.querySelector('.home-view')
      expect(view).toBeInTheDocument()

      const hero = document.querySelector('.home-hero')
      expect(hero).toBeInTheDocument()
    })
  })

  describe('unauthenticated state', () => {
    it('should show "Get Started" button when not authenticated', () => {
      mockIsAuthenticated.mockReturnValue(false)
      
      render(HomeView, {
        global: {
          plugins: [router],
        },
      })

      expect(screen.getByText(/get started/i)).toBeInTheDocument()
    })

    it('should show "Sign In" button when not authenticated', () => {
      mockIsAuthenticated.mockReturnValue(false)
      
      render(HomeView, {
        global: {
          plugins: [router],
        },
      })

      expect(screen.getByRole('button', { name: /sign in/i })).toBeInTheDocument()
    })

    it('should not show "User Profile" button when not authenticated', () => {
      mockIsAuthenticated.mockReturnValue(false)
      
      render(HomeView, {
        global: {
          plugins: [router],
        },
      })

      expect(screen.queryByRole('button', { name: /user profile/i })).not.toBeInTheDocument()
    })
  })

  describe('authenticated state', () => {
    it('should show "User Profile" button when authenticated', () => {
      mockIsAuthenticated.mockReturnValue(true)
      
      render(HomeView, {
        global: {
          plugins: [router],
        },
      })

      expect(screen.getByRole('button', { name: /user profile/i })).toBeInTheDocument()
    })

    it('should not show "Get Started" button when authenticated', () => {
      mockIsAuthenticated.mockReturnValue(true)
      
      render(HomeView, {
        global: {
          plugins: [router],
        },
      })

      expect(screen.queryByText(/get started/i)).not.toBeInTheDocument()
    })

    it('should not show "Sign In" button when authenticated', () => {
      mockIsAuthenticated.mockReturnValue(true)
      
      render(HomeView, {
        global: {
          plugins: [router],
        },
      })

      expect(screen.queryByRole('button', { name: /sign in/i })).not.toBeInTheDocument()
    })
  })

  describe('features section', () => {
    it('should render features section', () => {
      render(HomeView, {
        global: {
          plugins: [router],
        },
      })

      // Features section should exist
      const featuresSection = document.querySelector('.home-features')
      expect(featuresSection).toBeInTheDocument()
    })
  })

  describe('responsive design', () => {
    it('should have container class for responsive layout', () => {
      render(HomeView, {
        global: {
          plugins: [router],
        },
      })

      const container = document.querySelector('.container')
      expect(container).toBeInTheDocument()
    })
  })

  describe('visual elements', () => {
    it('should render logo icon', () => {
      render(HomeView, {
        global: {
          plugins: [router],
        },
      })

      const logo = document.querySelector('.home-logo')
      expect(logo).toBeInTheDocument()
    })

    it('should render hero background elements', () => {
      render(HomeView, {
        global: {
          plugins: [router],
        },
      })

      const heroBackground = document.querySelector('.home-hero__background')
      expect(heroBackground).toBeInTheDocument()

      const heroGradient = document.querySelector('.hero-gradient')
      expect(heroGradient).toBeInTheDocument()
    })

    it('should render decorative shapes', () => {
      render(HomeView, {
        global: {
          plugins: [router],
        },
      })

      const shapes = document.querySelectorAll('.hero-shape')
      expect(shapes.length).toBe(3)
    })
  })
})

