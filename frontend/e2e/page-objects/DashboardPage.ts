import { Page, Locator } from '@playwright/test'

/**
 * Page Object Model for Dashboard Page
 */
export class DashboardPage {
  readonly page: Page
  readonly logoutButton: Locator
  readonly userEmail: Locator
  readonly profileButton: Locator

  constructor(page: Page) {
    this.page = page
    // Logout button can be in different places, try multiple selectors
    this.logoutButton = page.getByRole('button', { name: /выйти|logout|sign out/i }).or(
      page.locator('button[aria-label*="logout"]').or(
        page.locator('button').filter({ has: page.locator('i.pi-sign-out') })
      )
    ).first()
    this.userEmail = page.locator('.profile-button span, .header-subtitle, [class*="user-email"]')
    this.profileButton = page.getByRole('button', { name: /profile|профиль/i }).or(
      page.locator('button').filter({ has: page.locator('i.pi-user') })
    ).first()
  }

  /**
   * Navigate to dashboard
   */
  async goto(): Promise<void> {
    const baseURL = process.env.PLAYWRIGHT_BASE_URL || 'http://localhost:3000'
    await this.page.goto(`${baseURL}/dashboard`)
    await this.page.waitForLoadState('networkidle')
  }

  /**
   * Click logout button
   */
  async logout(): Promise<void> {
    await this.logoutButton.click()
  }

  /**
   * Wait for redirect to login page after logout
   */
  async waitForLoginRedirect(): Promise<void> {
    await this.page.waitForURL('**/login', { timeout: 10000 })
  }

  /**
   * Check if currently on dashboard page
   */
  async isOnDashboard(): Promise<boolean> {
    return this.page.url().includes('/dashboard')
  }

  /**
   * Get user email from header
   */
  async getUserEmail(): Promise<string | null> {
    const emailElement = await this.userEmail.first()
    if (await emailElement.isVisible().catch(() => false)) {
      return await emailElement.textContent()
    }
    return null
  }
}

