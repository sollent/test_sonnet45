import { test, expect } from '@playwright/test'
import { LoginPage } from '../../page-objects/LoginPage'
import { DashboardPage } from '../../page-objects/DashboardPage'
import { testLoginUsers } from '../../fixtures/auth.fixture'
import { isAuthenticated, clearAuth } from '../../utils/helpers'

test.describe('Logout Flow', () => {
  let loginPage: LoginPage
  let dashboardPage: DashboardPage

  test.beforeEach(async ({ page, context }) => {
    loginPage = new LoginPage(page)
    dashboardPage = new DashboardPage(page)
    
    // Clear cookies and storage
    await context.clearCookies()
    await clearAuth(page)
  })

  test('TC-AUTH-013: Successful logout', async ({ page }) => {
    // Step 1: Login as test user
    const { email, password } = testLoginUsers.valid
    
    await loginPage.goto()
    await loginPage.fillForm(email, password)
    await page.waitForTimeout(500)
    await loginPage.submit()
    
    // Wait for redirect to dashboard
    await page.waitForURL('**/dashboard', { timeout: 15000 })
    await page.waitForTimeout(1000)

    // Verify user is authenticated before logout
    const authenticatedBefore = await isAuthenticated(page)
    expect(authenticatedBefore).toBe(true)

    // Step 2: Click logout button
    // Wait for dashboard to fully load
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)
    
    // Find and click logout button
    const logoutButton = page.getByRole('button', { name: /выход|выйти|logout|sign out/i }).or(
      page.locator('button[aria-label*="logout"]').or(
        page.locator('button').filter({ has: page.locator('i.pi-sign-out') })
      )
    ).first()
    
    // Wait for logout button to be visible
    await logoutButton.waitFor({ state: 'visible', timeout: 5000 })
    await logoutButton.click()

    // Step 3: Verify redirect to login page
    await dashboardPage.waitForLoginRedirect()
    expect(page.url()).toContain('/login')

    // Step 4: Verify user is not authenticated
    const authenticatedAfter = await isAuthenticated(page)
    expect(authenticatedAfter).toBe(false)

    // Step 5: Verify tokens are cleared
    const accessToken = await page.evaluate(() => localStorage.getItem('access_token'))
    const refreshToken = await page.evaluate(() => localStorage.getItem('refresh_token'))
    
    expect(accessToken).toBeNull()
    expect(refreshToken).toBeNull()
  })
})

