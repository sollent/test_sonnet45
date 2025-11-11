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
 * IMPORTANT: Must be called BEFORE navigating to any page
 * Uses addInitScript to ensure locale is set before app initializes
 */
export async function setLocale(page: Page, locale: 'en' | 'ru' = 'ru'): Promise<void> {
  await page.addInitScript((loc) => {
    localStorage.setItem('locale', loc)
  }, locale)
}

/**
 * Wait for dialog (modal) to close completely, including mask disappearance
 * ENHANCED VERSION: More robust with force close and longer timeouts
 */
export async function waitForDialogToClose(page: Page, timeout = 30000): Promise<void> {
  const startTime = Date.now()

  // Step 1: Wait for dialog to be hidden (detached is more reliable than hidden)
  try {
    await page.locator('.p-dialog').waitFor({ state: 'detached', timeout: 15000 })
  } catch (e) {
    // Dialog might still be visible, try to close it manually
    const closeButton = page.locator('.p-dialog-header-close, button[aria-label="Close"]').first()
    if (await closeButton.isVisible().catch(() => false)) {
      await closeButton.click({ force: true })
      await page.waitForTimeout(1000)
    }
  }

  // Step 2: Wait for mask to be completely removed from DOM
  let maskRemoved = false
  while (!maskRemoved && (Date.now() - startTime) < timeout) {
    // CRITICAL: Check if page is closed before trying to access it
    if (page.isClosed()) {
      console.warn('Page was closed while waiting for dialog mask to close')
      return
    }

    const maskExists = await page.locator('.p-dialog-mask').count()
    if (maskExists === 0) {
      maskRemoved = true
      break
    }

    // Try to wait for mask to detach
    try {
      await page.locator('.p-dialog-mask').waitFor({ state: 'detached', timeout: 2000 })
      maskRemoved = true
    } catch (e) {
      // Check again if page is closed after timeout
      if (page.isClosed()) {
        console.warn('Page was closed during mask wait')
        return
      }
      // Mask still exists, wait a bit and retry
      await page.waitForTimeout(500)
    }
  }

  // Step 3: Additional safety wait for animations
  if (!page.isClosed()) {
    await page.waitForTimeout(1000)
  }

  // Step 4: Final verification - if mask still exists, force remove it via script
  if (!page.isClosed()) {
    const finalMaskCount = await page.locator('.p-dialog-mask').count()
    if (finalMaskCount > 0) {
      console.warn('Dialog mask still present after timeout, force removing...')
      await page.evaluate(() => {
        const masks = document.querySelectorAll('.p-dialog-mask')
        masks.forEach(mask => mask.remove())
      })
      await page.waitForTimeout(500)
    }
  }

  // Step 5: Verify no dialog or mask is blocking
  if (!page.isClosed()) {
    const dialogCount = await page.locator('.p-dialog').count()
    const maskCount = await page.locator('.p-dialog-mask').count()

    if (dialogCount > 0 || maskCount > 0) {
      console.warn(`Warning: ${dialogCount} dialogs and ${maskCount} masks still present`)
    }
  }
}

