import { Page, Locator, expect } from '@playwright/test'

/**
 * Page Object Model for Registration Page
 */
export class RegisterPage {
  readonly page: Page
  readonly emailInput: Locator
  readonly passwordInput: Locator
  readonly confirmPasswordInput: Locator
  readonly submitButton: Locator
  readonly googleLoginButton: Locator
  readonly emailError: Locator
  readonly passwordError: Locator
  readonly confirmPasswordError: Locator
  readonly formError: Locator
  readonly loginLink: Locator

  constructor(page: Page) {
    this.page = page
    this.emailInput = page.locator('input#email')
    // Password inputs don't have IDs, use type and placeholder
    this.passwordInput = page.locator('input[type="password"]').first()
    this.confirmPasswordInput = page.locator('input[type="password"]').nth(1)
    this.submitButton = page.getByRole('button', { name: /зарегистрироваться|sign up|регистрация/i })
    // Google button is in iframe, so we check for the container or iframe
    this.googleLoginButton = page.locator('.google-login-button, iframe[src*="accounts.google.com"]').first()
    this.emailError = page.locator('small.form-error').first()
    this.passwordError = page.locator('small.form-error').nth(1)
    this.confirmPasswordError = page.locator('small.form-error').last()
    this.formError = page.locator('.form-message .p-toast-message, .p-message-error, .p-message')
    this.loginLink = page.getByRole('link', { name: /войти|sign in/i })
  }

  /**
   * Navigate to registration page
   */
  async goto(): Promise<void> {
    const baseURL = process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:3000'
    await this.page.goto(`${baseURL}/register`)
    await this.page.waitForLoadState('networkidle')
  }

  /**
   * Fill registration form
   */
  async fillForm(email: string, password: string, confirmPassword?: string): Promise<void> {
    await this.emailInput.fill(email)
    await this.passwordInput.fill(password)
    await this.confirmPasswordInput.fill(confirmPassword || password)
  }

  /**
   * Submit registration form
   */
  async submit(): Promise<void> {
    await this.submitButton.click()
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
   * Check if confirm password error is visible
   */
  async hasConfirmPasswordError(): Promise<boolean> {
    return await this.confirmPasswordError.isVisible().catch(() => false)
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
   * Get confirm password error text
   */
  async getConfirmPasswordErrorText(): Promise<string> {
    if (await this.hasConfirmPasswordError()) {
      return await this.confirmPasswordError.textContent() || ''
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
   * Wait for redirect to dashboard after successful registration
   */
  async waitForDashboardRedirect(): Promise<void> {
    await this.page.waitForURL('**/dashboard', { timeout: 10000 })
  }

  /**
   * Check if currently on registration page
   */
  async isOnRegisterPage(): Promise<boolean> {
    return this.page.url().includes('/register')
  }
}

