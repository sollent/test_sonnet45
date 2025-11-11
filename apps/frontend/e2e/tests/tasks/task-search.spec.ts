import { test, expect } from '@playwright/test'
import { DashboardPage } from '../../page-objects/DashboardPage'
import { TaskDialogPage } from '../../page-objects/TaskDialogPage'
import { LoginPage } from '../../page-objects/LoginPage'
import { testLoginUsers } from '../../fixtures/auth.fixture'
import { clearAuth, setLocale, waitForDialogToClose } from '../../utils/helpers'

test.describe('Search', () => {
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

    // Create test tasks with different titles
    const testTasks = [
      'Meeting with Product Team',
      'Review Pull Request #123',
      'Write Documentation for API',
      'Fix Bug in Login Module',
      'Plan Sprint Retrospective'
    ]

    for (const taskTitle of testTasks) {
      // Wait for any dialog mask to disappear from previous iteration
      await waitForDialogToClose(page)

      await dashboardPage.createTaskButton.click()
      await page.waitForTimeout(1500)
      await taskDialogPage.fillTitle(taskTitle)
      await page.waitForTimeout(300)
      await taskDialogPage.clickQuickDate('today')
      await page.waitForTimeout(500)
      await taskDialogPage.saveButton.click()

      // Wait for save operation to complete
      await page.waitForTimeout(1500)

      // Wait for dialog to close completely - CRITICAL!
      await waitForDialogToClose(page)

      // Additional safety wait for UI to update
      await page.waitForTimeout(500)
    }

    // Wait for all tasks to be created
    await page.waitForTimeout(2000)
  })

})
