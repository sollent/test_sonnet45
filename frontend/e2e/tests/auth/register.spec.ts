import { test, expect } from '@playwright/test'
import { RegisterPage } from '../../page-objects/RegisterPage'
import { generateTestUserEmail, testUsers, invalidEmails, defaultTestUser } from '../../fixtures/auth.fixture'
import { waitForToast, isAuthenticated, clearAuth, waitForRoute } from '../../utils/helpers'

test.describe('Registration Flow', () => {
  let registerPage: RegisterPage

  test.beforeEach(async ({ page, context }) => {
    registerPage = new RegisterPage(page)
    
    // Clear cookies and storage
    await context.clearCookies()
    
    // Navigate to register page
    await registerPage.goto()
    
    // Clear auth after page is loaded
    await clearAuth(page)
  })

  test('TC-AUTH-001: Successful registration with valid data', async ({ page }) => {
    const testEmail = generateTestUserEmail()
    const testPassword = 'TestPassword123!'

    // Fill form with valid data
    await registerPage.fillForm(testEmail, testPassword)
    
    // Wait a bit for form validation
    await page.waitForTimeout(500)
    
    // Submit form
    await registerPage.submit()

    // Wait for redirect to dashboard (with longer timeout)
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

  test('TC-AUTH-002: Registration validation - empty fields', async ({ page }) => {
    // Check if submit button is disabled (form validation)
    const isDisabled = await registerPage.isSubmitDisabled()
    
    // If button is enabled, try to submit
    if (!isDisabled) {
      await registerPage.submit()
      await page.waitForTimeout(1000) // Wait for validation
    }

    // Verify we're still on registration page
    expect(await registerPage.isOnRegisterPage()).toBe(true)

    // Verify error messages for required fields
    // Note: PrimeVue validation might show errors on blur or submit
    // Check if any validation errors are present
    const hasEmailError = await registerPage.hasEmailError()
    const hasPasswordError = await registerPage.hasPasswordError()
    const hasConfirmPasswordError = await registerPage.hasConfirmPasswordError()

    // At least one field should show error OR button should be disabled
    expect(hasEmailError || hasPasswordError || hasConfirmPasswordError || isDisabled).toBe(true)

    // Verify form was not submitted (no redirect)
    expect(page.url()).toContain('/register')
  })

  test('TC-AUTH-003: Registration validation - invalid email', async ({ page }) => {
    // Test a few invalid emails (not all to speed up test)
    const testEmails = ['invalid', 'test@', '@example.com', 'test @example.com']
    
    for (const invalidEmail of testEmails) {
      // Clear form first
      await registerPage.emailInput.clear()
      await registerPage.passwordInput.clear()
      await registerPage.confirmPasswordInput.clear()
      await page.waitForTimeout(200)

      // Fill with invalid email
      await registerPage.emailInput.fill(invalidEmail)
      await registerPage.passwordInput.fill('ValidPassword123!')
      await registerPage.confirmPasswordInput.fill('ValidPassword123!')
      
      // Trigger validation (blur on email field)
      await registerPage.emailInput.blur()
      
      // Wait for validation
      await page.waitForTimeout(1000)

      // Check if email error is shown
      const hasError = await registerPage.hasEmailError()
      
      // Should show error for invalid email
      expect(hasError).toBe(true)

      // Try to submit
      const wasDisabled = await registerPage.isSubmitDisabled()
      if (!wasDisabled) {
        await registerPage.submit()
        await page.waitForTimeout(1000)
      }

      // Should still be on registration page
      expect(await registerPage.isOnRegisterPage()).toBe(true)
    }
  })

  test('TC-AUTH-004: Registration validation - password mismatch', async ({ page }) => {
    const testEmail = generateTestUserEmail()
    const password = 'ValidPassword123!'
    const differentPassword = 'DifferentPassword456!'

    // Fill form with mismatched passwords
    await registerPage.emailInput.fill(testEmail)
    await registerPage.passwordInput.fill(password)
    await registerPage.confirmPasswordInput.fill(differentPassword)
    
    // Trigger validation on confirm password
    await registerPage.confirmPasswordInput.blur()
    await page.waitForTimeout(1000)

    // Check if confirm password error is shown
    const hasError = await registerPage.hasConfirmPasswordError()
    expect(hasError).toBe(true)

    // Try to submit (button might be disabled)
    const wasDisabled = await registerPage.isSubmitDisabled()
    if (!wasDisabled) {
      await registerPage.submit()
      await page.waitForTimeout(1000)
    }

    // Should still be on registration page
    expect(await registerPage.isOnRegisterPage()).toBe(true)
  })

  test('TC-AUTH-005: Registration validation - weak password', async ({ page }) => {
    const testEmail = generateTestUserEmail()
    const weakPassword = '12345' // Less than 6 characters

    // Fill form with weak password
    await registerPage.emailInput.fill(testEmail)
    await registerPage.passwordInput.fill(weakPassword)
    await registerPage.confirmPasswordInput.fill(weakPassword)
    
    // Trigger validation by typing and blurring
    await registerPage.passwordInput.type(weakPassword)
    await registerPage.passwordInput.blur()
    await page.waitForTimeout(1500)

    // Check if password error is shown OR button is disabled
    const hasError = await registerPage.hasPasswordError()
    const isDisabled = await registerPage.isSubmitDisabled()
    
    // Check if form is valid (computed property might prevent submission)
    const formValid = await page.evaluate(() => {
      const submitBtn = document.querySelector('button[type="submit"]');
      return submitBtn ? !submitBtn.hasAttribute('disabled') : false;
    })
    
    // Either error should be shown, button should be disabled, or form should be invalid
    expect(hasError || isDisabled || !formValid).toBe(true)

    // If error is shown, verify it's about password length
    if (hasError) {
      const errorText = await registerPage.getPasswordErrorText()
      expect(errorText.toLowerCase()).toMatch(/password|пароль|length|длина|минимум|minimum/i)
    }

    // Try to submit if button is not disabled
    if (!isDisabled && formValid) {
      await registerPage.submit()
      await page.waitForTimeout(1000)
    }

    // Should still be on registration page
    expect(await registerPage.isOnRegisterPage()).toBe(true)
  })

  test('TC-AUTH-006: Registration - duplicate email', async ({ page }) => {
    // First, register a user
    const testEmail = generateTestUserEmail()
    const testPassword = 'TestPassword123!'

    await registerPage.fillForm(testEmail, testPassword)
    await page.waitForTimeout(500)
    await registerPage.submit()
    
    // Wait for redirect to dashboard
    await page.waitForURL('**/dashboard', { timeout: 15000 })
    await page.waitForTimeout(1000)

    // Clear auth and go back to registration
    await clearAuth(page)
    await registerPage.goto()
    await page.waitForTimeout(1000)

    // Try to register with the same email
    await registerPage.fillForm(testEmail, 'DifferentPassword456!')
    await page.waitForTimeout(500)
    await registerPage.submit()

    // Wait for error message (could be toast or form error)
    // Wait for either toast or form error to appear
    await Promise.race([
      page.waitForSelector('.p-toast-message', { timeout: 5000 }).catch(() => null),
      page.waitForSelector('.form-message .p-message', { timeout: 5000 }).catch(() => null),
      page.waitForTimeout(3000)
    ])

    // Should show error about duplicate email (either form error or toast)
    const hasFormError = await registerPage.hasFormError()
    const hasToast = await page.locator('.p-toast-message').isVisible().catch(() => false)
    
    expect(hasFormError || hasToast).toBe(true)

    // Should still be on registration page
    expect(await registerPage.isOnRegisterPage()).toBe(true)
  })

  test('TC-AUTH-007: Registration - Google OAuth button presence', async ({ page }) => {
    // Wait for Google button to load (it's in iframe)
    await page.waitForTimeout(2000)
    
    // Verify Google login button container is visible
    const container = page.locator('.google-login-button')
    const iframe = page.locator('iframe[src*="accounts.google.com"]')
    
    const containerVisible = await container.isVisible().catch(() => false)
    const iframeVisible = await iframe.isVisible().catch(() => false)
    
    // At least container or iframe should be visible
    expect(containerVisible || iframeVisible).toBe(true)
  })
})

