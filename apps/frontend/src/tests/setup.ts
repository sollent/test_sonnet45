import { config } from '@vue/test-utils'
import { vi, beforeEach, afterEach } from 'vitest'
import '@testing-library/jest-dom/vitest'
import { createI18n } from 'vue-i18n'
import en from '@/i18n/locales/en'
import ru from '@/i18n/locales/ru'

// Mock window.matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation((query) => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
})

// Mock localStorage with actual implementation
const localStorageMock = (() => {
  let store: Record<string, string> = {}

  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value
    },
    removeItem: (key: string) => {
      delete store[key]
    },
    clear: () => {
      store = {}
    },
    get length() {
      return Object.keys(store).length
    },
    key: (index: number) => {
      const keys = Object.keys(store)
      return keys[index] || null
    },
  }
})()

global.localStorage = localStorageMock as any

// Mock fetch
global.fetch = vi.fn()

// Setup i18n for tests
const i18n = createI18n({
  legacy: false,
  locale: 'en',
  fallbackLocale: 'en',
  messages: {
    en,
    ru
  }
})

// Global test config
config.global.plugins = [i18n]
config.global.stubs = {
  teleport: true,
}

// Suppress expected Vue warnings from PrimeVue components in tests
// These warnings are safe to ignore during testing and don't affect functionality
config.global.config.warnHandler = (msg: string) => {
  // Suppress PrimeVue $listeners warnings (Vue 2 API in Vue 3 context)
  const ignoredWarnings = [
    'Property "$listeners" was accessed during render but is not defined',
    'v-on with no argument expects an object value'
  ]

  const shouldIgnore = ignoredWarnings.some(warning => msg.includes(warning))
  if (shouldIgnore) {
    return // Suppress this warning
  }

  // Log other warnings normally
  console.warn(msg)
}

// Suppress expected console.error messages in tests
// These are intentional errors being tested (e.g., error handling in auth store)
const originalError = console.error
const ignoredErrors = [
  'Failed to fetch user:',
  'Token refresh failed:',
  'Failed to parse stored user'
]

beforeEach(() => {
  // Override console.error to filter expected errors
  console.error = (...args: any[]) => {
    const message = args[0]?.toString() || ''
    const shouldIgnore = ignoredErrors.some(ignored => message.includes(ignored))

    if (!shouldIgnore) {
      originalError.apply(console, args)
    }
  }
})

// Clear mocks before each test
beforeEach(() => {
  localStorageMock.clear()
  vi.clearAllMocks()
})

// Restore console.error after tests
afterEach(() => {
  console.error = originalError
})

