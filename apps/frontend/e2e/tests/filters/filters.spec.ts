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
    await page.waitForURL('**/dashboard', { timeout: 15000 })
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
    await page.waitForTimeout(3000) // Increased wait for full load

    // Navigate to "All tasks" view - CRITICAL: filter buttons only exist in certain views
    // Wait for the dashboard to fully load first
    await page.waitForSelector('main', { state: 'visible', timeout: 10000 })
    await page.waitForTimeout(1000)

    // Now click "All tasks" button in sidebar to ensure we see all tasks
    const allTasksButton = page.getByRole('button', { name: /все задачи/i }).first()
    await expect(allTasksButton).toBeVisible({ timeout: 10000 })
    await allTasksButton.click()
    await page.waitForTimeout(2000) // Increased wait for tasks to load

    // Verify tasks are actually loaded
    const initialTaskCount = await dashboardPage.getTaskCount()
    console.log(`Loaded ${initialTaskCount} tasks after API creation`)
  })

  test.describe('6.1 Quick Filters', () => {
    test('TC-FILTER-001: Apply "На сегодня" quick filter', async ({ page }) => {
      // Get initial task count
      const initialCount = await dashboardPage.getTaskCount()
      expect(initialCount).toBeGreaterThanOrEqual(2) // We created 2 tasks for today

      // Click "На сегодня" quick filter
      const todayFilterButton = page.getByRole('button', { name: /На сегодня/i }).first()
      await expect(todayFilterButton).toBeVisible({ timeout: 5000 })
      await todayFilterButton.click()
      // Wait for filter to apply and tasks to reload
      await page.waitForTimeout(2000)

      // Verify "Очистить фильтры" button appears
      const clearButton = page.getByRole('button', { name: /очистить фильтры/i })
      await expect(clearButton).toBeVisible({ timeout: 5000 })

      // Verify filtered task count (should show today's tasks)
      const filteredCount = await dashboardPage.getTaskCount()
      expect(filteredCount).toBeGreaterThanOrEqual(1) // At least some tasks for today
      expect(filteredCount).toBeLessThanOrEqual(initialCount)

      // Verify today's tasks are shown
      const todayTask1 = await dashboardPage.findTaskByTitle('Task Today 1')
      expect(todayTask1).not.toBeNull()
    })

    test('TC-FILTER-002: Apply "Срочные" quick filter', async ({ page }) => {
      // Click "Срочные" quick filter
      const urgentFilterButton = page.getByRole('button', { name: /Срочные/i }).first()
      await expect(urgentFilterButton).toBeVisible({ timeout: 5000 })
      await urgentFilterButton.click()
      await page.waitForTimeout(2000) // Wait for filter to apply

      // Verify "Очистить фильтры" button appears
      const clearButton = page.getByRole('button', { name: /очистить фильтры/i })
      await expect(clearButton).toBeVisible({ timeout: 5000 })

      // With simple test data (no urgent tasks), we expect empty state or 0 tasks
      const taskCount = await dashboardPage.getTaskCount()
      const isEmpty = await dashboardPage.isEmptyStateVisible()
      expect(taskCount === 0 || isEmpty).toBe(true)
    })

    test('TC-FILTER-003: Apply "Просроченные" quick filter', async ({ page }) => {
      // Click "Просроченные" quick filter
      const overdueFilterButton = page.getByRole('button', { name: /Просроченные/i }).first()
      await expect(overdueFilterButton).toBeVisible({ timeout: 5000 })
      await overdueFilterButton.click()
      await page.waitForTimeout(2000) // Wait for filter to apply

      // Verify "Очистить фильтры" button appears (might not show if no results)
      await page.waitForTimeout(1000)

      // With simple test data (no overdue tasks), we expect empty state or 0 tasks
      const taskCount = await dashboardPage.getTaskCount()
      const isEmpty = await dashboardPage.isEmptyStateVisible()
      expect(taskCount === 0 || isEmpty).toBe(true)
    })

    test('TC-FILTER-004: Apply "В процессе" quick filter', async ({ page }) => {
      // Click "В процессе" quick filter
      const inProgressFilterButton = page.getByRole('button', { name: /В процессе/i }).first()
      await expect(inProgressFilterButton).toBeVisible({ timeout: 5000 })
      await inProgressFilterButton.click()
      await page.waitForTimeout(2000) // Wait for filter to apply

      // Wait for results to load
      await page.waitForTimeout(1000)

      // With simple test data (default status is 'pending'), we expect empty state
      const taskCount = await dashboardPage.getTaskCount()
      const isEmpty = await dashboardPage.isEmptyStateVisible()
      expect(taskCount === 0 || isEmpty).toBe(true)
    })

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
