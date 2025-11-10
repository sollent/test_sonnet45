import { Page, expect } from '@playwright/test'

/**
 * Wait for page to be fully loaded
 */
export async function waitForPageLoad(page: Page): Promise<void> {
  await page.waitForLoadState('networkidle')
  await page.waitForLoadState('domcontentloaded')
}

/**
 * Wait for toast message to appear (optional - won't fail if toast doesn't appear)
 */
export async function waitForToast(page: Page, message?: string, required = false): Promise<void> {
  const toastSelector = '.p-toast-message'
  try {
    await page.waitForSelector(toastSelector, { timeout: 10000 })
    
    if (message) {
      await expect(page.locator(toastSelector)).toContainText(message, { timeout: 5000 })
    }
  } catch (e) {
    if (required) {
      throw e
    }
    // If toast is not required, just continue
  }
}

/**
 * Check if user is authenticated (has token in localStorage)
 */
export async function isAuthenticated(page: Page): Promise<boolean> {
  const token = await page.evaluate(() => localStorage.getItem('access_token'))
  return token !== null && token !== ''
}

/**
 * Clear authentication (remove tokens)
 */
export async function clearAuth(page: Page): Promise<void> {
  try {
    await page.evaluate(() => {
      try {
        localStorage.removeItem('access_token')
        localStorage.removeItem('refresh_token')
      } catch (e) {
        // Ignore if localStorage is not accessible
      }
    })
  } catch (e) {
    // Ignore if page context is not ready
  }
}

/**
 * Wait for navigation to specific route
 */
export async function waitForRoute(page: Page, route: string, timeout = 10000): Promise<void> {
  await page.waitForURL(`**${route}`, { timeout })
}

/**
 * Generate unique string with timestamp
 */
export function generateUniqueString(prefix = 'test'): string {
  return `${prefix}-${Date.now()}-${Math.floor(Math.random() * 10000)}`
}

/**
 * Set application locale (language)
 */
export async function setLocale(page: Page, locale: 'en' | 'ru' = 'ru'): Promise<void> {
  await page.evaluate((loc) => {
    localStorage.setItem('locale', loc)
  }, locale)
}

/**
 * Wait for dialog (modal) to close completely, including mask disappearance
 */
export async function waitForDialogToClose(page: Page, timeout = 10000): Promise<void> {
  try {
    // Wait for dialog to be hidden
    await page.locator('.p-dialog').waitFor({ state: 'hidden', timeout: timeout / 2 })
  } catch (e) {
    // Dialog might already be closed
  }

  try {
    // Wait for mask to be hidden (this is what blocks clicks!)
    await page.locator('.p-dialog-mask').waitFor({ state: 'detached', timeout: timeout / 2 })
  } catch (e) {
    // Mask might already be removed
  }

  // Additional wait for animation to complete
  await page.waitForTimeout(500)

  // Verify no mask is blocking
  const maskExists = await page.locator('.p-dialog-mask').count()
  if (maskExists > 0) {
    // If mask still exists, wait longer
    await page.waitForTimeout(1000)
  }
}

