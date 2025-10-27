import { config } from '@vue/test-utils'
import { vi, beforeEach } from 'vitest'
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

// Clear mocks before each test
beforeEach(() => {
  localStorageMock.clear()
  vi.clearAllMocks()
})

