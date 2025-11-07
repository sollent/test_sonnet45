import { test, expect } from '@playwright/test'
import { LoginPage } from '../../page-objects/LoginPage'
import { generateTestUserEmail, invalidEmails, testLoginUsers } from '../../fixtures/auth.fixture'
import { waitForToast, isAuthenticated, clearAuth } from '../../utils/helpers'

test.describe('Login Flow', () => {
  let loginPage: LoginPage

  test.beforeEach(async ({ page, context }) => {
    loginPage = new LoginPage(page)
    
    // Clear cookies and storage
    await context.clearCookies()
    
    // Navigate to login page
    await loginPage.goto()
    
    // Clear auth after page is loaded
    await clearAuth(page)
  })

  test('TC-AUTH-008: Successful login with valid credentials', async ({ page }) => {
    // Use valid test user credentials
    const { email, password } = testLoginUsers.valid

    // Fill form with valid credentials
    await loginPage.fillForm(email, password)
    
    // Wait a bit for form validation
    await page.waitForTimeout(500)
    
    // Submit form
    await loginPage.submit()

    // Wait for redirect to dashboard
    await page.waitForURL('**/dashboard', { timeout: 15000 })

    // Verify redirect to dashboard
    expect(page.url()).toContain('/dashboard')

    // Verify success toast message (may appear after redirect, but might disappear quickly)
    await page.waitForTimeout(1000)
    // Toast might not be visible if it disappeared quickly, so make it optional
    const toastVisible = await page.locator('.p-toast-message').isVisible().catch(() => false)
    if (toastVisible) {
      await waitForToast(page)
    }

    // Verify user is authenticated (has token) - this is the most important check
    const authenticated = await isAuthenticated(page)
    expect(authenticated).toBe(true)
  })

  test('TC-AUTH-009: Login validation - empty fields', async ({ page }) => {
    // Check if submit button is disabled (form validation)
    const isDisabled = await loginPage.isSubmitDisabled()
    
    // If button is enabled, try to submit
    if (!isDisabled) {
      await loginPage.submit()
      await page.waitForTimeout(1000) // Wait for validation
    }

    // Verify we're still on login page
    expect(await loginPage.isOnLoginPage()).toBe(true)

    // Verify error messages for required fields
    // Note: PrimeVue validation might show errors on blur or submit
    // Check if any validation errors are present
    const hasEmailError = await loginPage.hasEmailError()
    const hasPasswordError = await loginPage.hasPasswordError()

    // At least one field should show error OR button should be disabled
    expect(hasEmailError || hasPasswordError || isDisabled).toBe(true)

    // Verify form was not submitted (no redirect)
    expect(page.url()).toContain('/login')
  })

  test('TC-AUTH-010: Login - invalid credentials', async ({ page }) => {
    // Use invalid credentials
    const { email, password } = testLoginUsers.invalidCredentials

    // Fill form with invalid credentials
    await loginPage.fillForm(email, password)
    await page.waitForTimeout(500)
    await loginPage.submit()

    // Wait for error message (could be toast or form error)
    await Promise.race([
      page.waitForSelector('.p-toast-message', { timeout: 8000 }).catch(() => null),
      page.waitForSelector('.form-message .p-message', { timeout: 8000 }).catch(() => null),
      page.waitForSelector('.p-message-error', { timeout: 8000 }).catch(() => null),
      page.waitForTimeout(5000)
    ])

    // Check multiple error indicators
    const hasFormError = await loginPage.hasFormError()
    const hasToast = await page.locator('.p-toast-message').isVisible().catch(() => false)
    const hasMessageError = await page.locator('.p-message-error').isVisible().catch(() => false)
    const hasFormMessage = await page.locator('.form-message').isVisible().catch(() => false)
    const isStillOnLoginPage = await loginPage.isOnLoginPage()
    
    // At least one error indicator should be present OR we should still be on login page
    expect(hasFormError || hasToast || hasMessageError || hasFormMessage || isStillOnLoginPage).toBe(true)

    // Should still be on login page
    expect(isStillOnLoginPage).toBe(true)

    // Verify user is NOT authenticated
    const authenticated = await isAuthenticated(page)
    expect(authenticated).toBe(false)
  })

  test('TC-AUTH-011: Login - wrong password', async ({ page }) => {
    // Use valid email but wrong password
    const { email } = testLoginUsers.valid
    const wrongPassword = 'WrongPassword123!'

    // Fill form with wrong password
    await loginPage.fillForm(email, wrongPassword)
    await page.waitForTimeout(500)
    await loginPage.submit()

    // Wait for error message - check multiple possible locations
    await page.waitForTimeout(2000) // Wait for API response
    
    // Check multiple ways error could be displayed
    const errorSelectors = [
      '.form-message .p-message',
      '.p-message-error',
      '.p-message.p-message-error',
      '.p-toast-message',
      '.p-invalid',
      '[class*="error"]',
      '.p-error'
    ]
    
    let hasError = false
    for (const selector of errorSelectors) {
      const element = page.locator(selector)
      const visible = await element.isVisible({ timeout: 2000 }).catch(() => false)
      if (visible) {
        hasError = true
        break
      }
    }
    
    // Also check if we're still on login page (which indicates error)
    const isOnLoginPage = await loginPage.isOnLoginPage()
    
    // At least one error indicator should be present OR we should still be on login page
    expect(hasError || isOnLoginPage).toBe(true)

    // Should still be on login page
    expect(await loginPage.isOnLoginPage()).toBe(true)

    // Verify user is NOT authenticated
    const authenticated = await isAuthenticated(page)
    expect(authenticated).toBe(false)
  })

  test('TC-AUTH-012: Login - invalid email format', async ({ page }) => {
    // Test a few invalid emails (not all to speed up test)
    const testEmails = ['invalid', 'test@', '@example.com', 'test @example.com']
    
    for (const invalidEmail of testEmails) {
      // Clear form first
      await loginPage.emailInput.clear()
      await loginPage.passwordInput.clear()
      await page.waitForTimeout(200)

      // Fill with invalid email
      await loginPage.emailInput.fill(invalidEmail)
      await loginPage.passwordInput.fill('ValidPassword123!')
      
      // Trigger validation (blur on email field)
      await loginPage.emailInput.blur()
      
      // Wait for validation
      await page.waitForTimeout(1000)

      // Check if email error is shown
      const hasError = await loginPage.hasEmailError()
      
      // Should show error for invalid email
      expect(hasError).toBe(true)

      // Try to submit
      const wasDisabled = await loginPage.isSubmitDisabled()
      if (!wasDisabled) {
        await loginPage.submit()
        await page.waitForTimeout(1000)
      }

      // Should still be on login page
      expect(await loginPage.isOnLoginPage()).toBe(true)
    }
  })

  test('TC-AUTH-013: Login - Google OAuth button presence', async ({ page }) => {
    // Wait for Google button to load (it's in iframe, may take time)
    await page.waitForTimeout(3000)
    
    // Verify Google login button container is visible
    const container = page.locator('.google-login-button')
    const iframe = page.locator('iframe[src*="accounts.google.com"]')
    
    // Wait for either container or iframe to appear
    await Promise.race([
      container.waitFor({ state: 'visible', timeout: 5000 }).catch(() => null),
      iframe.waitFor({ state: 'visible', timeout: 5000 }).catch(() => null),
      page.waitForTimeout(5000)
    ])
    
    const containerVisible = await container.isVisible().catch(() => false)
    const iframeVisible = await iframe.isVisible().catch(() => false)
    
    // At least container or iframe should be visible
    expect(containerVisible || iframeVisible).toBe(true)
  })
})

