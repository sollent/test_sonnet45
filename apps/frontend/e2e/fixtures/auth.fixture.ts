/**
 * Test fixtures for authentication
 */

export interface TestUser {
  email: string
  password: string
}

/**
 * Generate unique test user email
 */
export function generateTestUserEmail(): string {
  const timestamp = Date.now()
  const random = Math.floor(Math.random() * 10000)
  return `e2e-test-${timestamp}-${random}@example.com`
}

/**
 * Default test user credentials
 */
export const defaultTestUser: TestUser = {
  email: generateTestUserEmail(),
  password: 'TestPassword123!'
}

/**
 * Valid test users for different scenarios
 */
export const testUsers = {
  valid: {
    email: generateTestUserEmail(),
    password: 'ValidPassword123!'
  },
  weakPassword: {
    email: generateTestUserEmail(),
    password: '12345' // Too short (< 6 chars)
  },
  longPassword: {
    email: generateTestUserEmail(),
    password: 'a'.repeat(41) // Too long (> 40 chars)
  }
}

/**
 * Invalid email formats for testing
 */
export const invalidEmails = [
  'invalid',
  'test@',
  '@example.com',
  'test @example.com',
  'test@example',
  'test..test@example.com',
  ''
]

/**
 * Test user credentials for login tests
 * Note: These should match actual test users in the database
 */
export const testLoginUsers = {
  valid: {
    email: 'sollent98@gmail.com',
    password: 'Pahan1998'
  },
  invalidCredentials: {
    email: 'nonexistent@example.com',
    password: 'WrongPassword123!'
  },
  wrongPassword: {
    email: 'sollent98@gmail.com',
    password: 'WrongPassword123!'
  }
}

