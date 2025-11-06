import { Page, expect } from '@playwright/test'

/**
 * Wait for page to be fully loaded
 */
export async function waitForPageLoad(page: Page): Promise<void> {
  await page.waitForLoadState('networkidle')
  await page.waitForLoadState('domcontentloaded')
}

/**
 * Wait for toast message to appear
 */
export async function waitForToast(page: Page, message?: string): Promise<void> {
  const toastSelector = '.p-toast-message'
  await page.waitForSelector(toastSelector, { timeout: 5000 })
  
  if (message) {
    await expect(page.locator(toastSelector)).toContainText(message, { timeout: 5000 })
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

