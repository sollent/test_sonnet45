import { Page, Locator, expect } from '@playwright/test'

/**
 * Page Object Model for Login Page
 */
export class LoginPage {
  readonly page: Page
  readonly emailInput: Locator
  readonly passwordInput: Locator
  readonly submitButton: Locator
  readonly googleLoginButton: Locator
  readonly emailError: Locator
  readonly passwordError: Locator
  readonly formError: Locator
  readonly registerLink: Locator

  constructor(page: Page) {
    this.page = page
    this.emailInput = page.locator('input#email')
    // Password input doesn't have ID, use type
    this.passwordInput = page.locator('input[type="password"]').first()
    this.submitButton = page.getByRole('button', { name: /войти|sign in/i })
    // Google button is in iframe, so we check for the container or iframe
    this.googleLoginButton = page.locator('.google-login-button, iframe[src*="accounts.google.com"]').first()
    this.emailError = page.locator('small.form-error').first()
    this.passwordError = page.locator('small.form-error').last()
    this.formError = page.locator('.form-message .p-toast-message, .p-message-error, .p-message')
    this.registerLink = page.getByRole('link', { name: /регистрация|sign up/i })
  }

  /**
   * Navigate to login page
   */
  async goto(): Promise<void> {
    const baseURL = (globalThis as any).process?.env?.PLAYWRIGHT_BASE_URL || 'http://localhost:3000'
    await this.page.goto(`${baseURL}/login`)
    await this.page.waitForLoadState('networkidle')
  }

  /**
   * Fill login form
   */
  async fillForm(email: string, password: string): Promise<void> {
    await this.emailInput.fill(email)
    await this.passwordInput.fill(password)
    // Wait for form validation to complete
    await this.page.waitForTimeout(300)
  }

  /**
   * Submit login form
   */
  async submit(): Promise<void> {
    // Wait for button to be enabled
    await this.submitButton.waitFor({ state: 'visible', timeout: 5000 })
    await this.page.waitForTimeout(200)
    // Force enable check before clicking
    await this.submitButton.click({ force: false, timeout: 15000 })
  }

  /**
   * Check if email error is visible
   */
  async hasEmailError(): Promise<boolean> {
    return await this.emailError.isVisible().catch(() => false)
  }

  /**
   * Check if password error is visible
   */
  async hasPasswordError(): Promise<boolean> {
    return await this.passwordError.isVisible().catch(() => false)
  }

  /**
   * Get email error text
   */
  async getEmailErrorText(): Promise<string> {
    if (await this.hasEmailError()) {
      return await this.emailError.textContent() || ''
    }
    return ''
  }

  /**
   * Get password error text
   */
  async getPasswordErrorText(): Promise<string> {
    if (await this.hasPasswordError()) {
      return await this.passwordError.textContent() || ''
    }
    return ''
  }

  /**
   * Check if form error message is visible
   */
  async hasFormError(): Promise<boolean> {
    return await this.formError.isVisible().catch(() => false)
  }

  /**
   * Get form error text
   */
  async getFormErrorText(): Promise<string> {
    if (await this.hasFormError()) {
      return await this.formError.textContent() || ''
    }
    return ''
  }

  /**
   * Check if submit button is disabled
   */
  async isSubmitDisabled(): Promise<boolean> {
    return await this.submitButton.isDisabled()
  }

  /**
   * Check if Google login button is visible
   */
  async isGoogleButtonVisible(): Promise<boolean> {
    // Google button is in iframe, check for container or iframe
    const container = this.page.locator('.google-login-button')
    const iframe = this.page.locator('iframe[src*="accounts.google.com"]')
    return await container.isVisible().catch(() => false) || await iframe.isVisible().catch(() => false)
  }

  /**
   * Wait for redirect to dashboard after successful login
   */
  async waitForDashboardRedirect(): Promise<void> {
    await this.page.waitForURL('**/dashboard', { timeout: 15000 })
  }

  /**
   * Check if currently on login page
   */
  async isOnLoginPage(): Promise<boolean> {
    return this.page.url().includes('/login')
  }
}

