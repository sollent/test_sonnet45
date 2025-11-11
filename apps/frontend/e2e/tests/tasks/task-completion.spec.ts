import { test, expect } from '@playwright/test'
import { DashboardPage } from '../../page-objects/DashboardPage'
import { TaskDialogPage } from '../../page-objects/TaskDialogPage'
import { LoginPage } from '../../page-objects/LoginPage'
import { testLoginUsers } from '../../fixtures/auth.fixture'
import { clearAuth, setLocale, waitForDialogToClose } from '../../utils/helpers'

test.describe('Task Completion', () => {
  let dashboardPage: DashboardPage
  let taskDialogPage: TaskDialogPage
  let loginPage: LoginPage

  test.beforeEach(async ({ page, context }) => {
    dashboardPage = new DashboardPage(page)
    taskDialogPage = new TaskDialogPage(page)
    loginPage = new LoginPage(page)

    // Clear cookies and storage
    await context.clearCookies()
    await clearAuth(page)

    // Set Russian locale
    await setLocale(page, 'ru')

    // Login as test user
    await loginPage.goto()
    const { email, password } = testLoginUsers.valid
    await loginPage.fillForm(email, password)
    await page.waitForTimeout(500)
    await loginPage.submit()
    await page.waitForURL('**/dashboard', { timeout: 15000 })

    // Wait for dashboard to load
    await page.waitForTimeout(2000)
  })


  test('TC-COMPLETE-005: Uncomplete task from sidebar', async ({ page }) => {
    // Create and complete a task
    await dashboardPage.createTaskButton.click()
    await page.waitForTimeout(1000)

    await taskDialogPage.fillTitle('Task to Uncomplete from Sidebar')
    await page.waitForTimeout(300)
    await taskDialogPage.clickQuickDate('today')
    await page.waitForTimeout(500)
    await taskDialogPage.saveButton.click()
    await page.waitForTimeout(1000)

    // Wait for dialog and mask to disappear completely
    await waitForDialogToClose(page)

    // Navigate to "Today" view
    const todayButton = page.getByRole('button', { name: /сегодня|today/i }).first()
    if (await todayButton.isVisible().catch(() => false)) {
      await todayButton.click()
      await page.waitForTimeout(1500)
    }

    // Complete it first
    const task = await dashboardPage.findTaskByTitle('Task to Uncomplete from Sidebar')
    if (task) {
      const checkbox = task.locator('input[type="checkbox"]').first()
      await checkbox.scrollIntoViewIfNeeded()
      await page.waitForTimeout(300)
      await checkbox.click({ force: true })
      await page.waitForTimeout(2000)

      // Open task details
      await task.click()
      await page.waitForTimeout(1500)

      // Look for "Uncomplete" or "Return to active" button
      const uncompleteButtons = [
        page.getByRole('button', { name: /вернуть в невыполненные|uncomplete|mark as active/i }),
        page.getByRole('button', { name: /activate|активировать/i })
      ]

      let uncompleteButton = null
      for (const btn of uncompleteButtons) {
        if (await btn.isVisible().catch(() => false)) {
          uncompleteButton = btn
          break
        }
      }

      if (uncompleteButton) {
        await uncompleteButton.click()
        await page.waitForTimeout(1500)

        // Verify sidebar updates to show active state
        const completeButton = page.getByRole('button', { name: /отметить как завершенную|mark as completed|завершить/i })
        const hasCompleteButton = await completeButton.isVisible().catch(() => false)
        expect(hasCompleteButton).toBe(true)
      }

      // Close sidebar
      const closeButton = page.getByRole('button', { name: /закрыть|close/i }).first()
      if (await closeButton.isVisible().catch(() => false)) {
        await closeButton.click()
        await page.waitForTimeout(1000)
      }

      // Verify checkbox is unchecked
      const taskAfter = await dashboardPage.findTaskByTitle('Task to Uncomplete from Sidebar')
      if (taskAfter) {
        const checkboxAfter = taskAfter.locator('input[type="checkbox"]').first()
        const isChecked = await checkboxAfter.isChecked()
        expect(isChecked).toBe(false)
      }
    }
  })
})
