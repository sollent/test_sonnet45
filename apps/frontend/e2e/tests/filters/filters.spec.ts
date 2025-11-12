import { test, expect } from '@playwright/test'
import { DashboardPage } from '../../page-objects/DashboardPage'
import { TaskDialogPage } from '../../page-objects/TaskDialogPage'
import { FiltersModalPage } from '../../page-objects/FiltersModalPage'
import { LoginPage } from '../../page-objects/LoginPage'
import { testLoginUsers } from '../../fixtures/auth.fixture'
import { clearAuth, setLocale } from '../../utils/helpers'
import { createTasksViaAPI, getTaskDate } from '../../utils/api-helpers'

test.describe('Filters', () => {
  let dashboardPage: DashboardPage
  let taskDialogPage: TaskDialogPage
  let filtersModalPage: FiltersModalPage
  let loginPage: LoginPage

  // Increase timeout for these tests due to longer setup
  test.setTimeout(60000)

  test.beforeEach(async ({ page, context }) => {
    dashboardPage = new DashboardPage(page)
    taskDialogPage = new TaskDialogPage(page)
    filtersModalPage = new FiltersModalPage(page)
    loginPage = new LoginPage(page)

    // Clear cookies and storage
    await context.clearCookies()
    await clearAuth(page)

    // Set Russian locale for UI elements
    await setLocale(page, 'ru')

    const { email, password } = testLoginUsers.valid

    // HYBRID APPROACH: Login via UI (proper auth), create tasks via API (avoid dialog issues)

    // Step 1: Login via UI to establish proper authentication
    await loginPage.goto()
    await loginPage.fillForm(email, password)
    await page.waitForTimeout(500)
    await loginPage.submit()
    await page.waitForURL('**/dashboard', { timeout: 45000 })
    await page.waitForTimeout(2000)

    // Step 2: Get auth token from localStorage (now valid after UI login)
    const token = await page.evaluate(() => localStorage.getItem('access_token'))

    // Step 3: Create test tasks via API (much faster, no dialog mask issues!)
    const testTasks = [
      { title: 'Task Today 1', due_date: getTaskDate('today') },
      { title: 'Task Today 2', due_date: getTaskDate('today') },
      { title: 'Task Tomorrow', due_date: getTaskDate('tomorrow') }
    ]

    if (token) {
      await createTasksViaAPI(testTasks, token)
    }

    // Step 4: Reload dashboard to show newly created tasks
    await page.reload()
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(2000)

    // Navigate to "All tasks" view - CRITICAL: filter buttons only exist in certain views
    // Wait for the dashboard to fully load first
    await page.waitForSelector('main', { state: 'visible', timeout: 10000 })
    await page.waitForTimeout(1000)

    // Now click "All tasks" button in sidebar
    const allTasksButton = page.getByRole('button', { name: /все задачи/i }).first()
    await expect(allTasksButton).toBeVisible({ timeout: 10000 })
    await allTasksButton.click()
    await page.waitForTimeout(1500)
  })

  test.describe('6.1 Quick Filters', () => {

    test('TC-FILTER-005: Apply multiple quick filters', async ({ page }) => {
      // Get initial count
      const initialCount = await dashboardPage.getTaskCount()

      // Click "На сегодня" filter
      const todayFilterButton = page.getByRole('button', { name: /На сегодня/i }).first()
      await todayFilterButton.click()
      await page.waitForTimeout(1500)

      // Verify filter is applied
      const clearButtonAfterFirst = page.getByRole('button', { name: /очистить фильтры/i })
      await expect(clearButtonAfterFirst).toBeVisible({ timeout: 3000 })

      const countAfterFirst = await dashboardPage.getTaskCount()
      expect(countAfterFirst).toBeGreaterThanOrEqual(1)

      // Click "Срочные" filter (this should narrow results further)
      const urgentFilterButton = page.getByRole('button', { name: /Срочные/i }).first()
      await urgentFilterButton.click()
      await page.waitForTimeout(1500)

      // Verify both filters are active
      const clearButton = page.getByRole('button', { name: /очистить фильтры/i })
      await expect(clearButton).toBeVisible({ timeout: 3000 })

      // With no urgent tasks in test data, should show empty
      const finalCount = await dashboardPage.getTaskCount()
      expect(finalCount).toBeLessThanOrEqual(countAfterFirst) // Narrower filter
    })

    test('TC-FILTER-006: Clear quick filters', async ({ page }) => {
      // Apply a quick filter first
      const todayFilterButton = page.getByRole('button', { name: /На сегодня/i }).first()
      await todayFilterButton.click()
      await page.waitForTimeout(1500)

      // Verify filter is applied
      const clearButton = page.getByRole('button', { name: /очистить фильтры/i })
      await expect(clearButton).toBeVisible({ timeout: 3000 })

      // Get filtered count
      const filteredCount = await dashboardPage.getTaskCount()

      // Click clear filters
      await clearButton.click()
      await page.waitForTimeout(1500)

      // Verify clear button is gone
      await expect(clearButton).not.toBeVisible({ timeout: 3000 })

      // Verify all tasks are shown again
      const allTasksCount = await dashboardPage.getTaskCount()
      expect(allTasksCount).toBeGreaterThanOrEqual(filteredCount) // Should show at least same or more tasks
      expect(allTasksCount).toBeGreaterThanOrEqual(2) // We created 2 tasks for today
    })
  })
})
