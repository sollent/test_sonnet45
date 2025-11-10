import { test, expect } from '@playwright/test'
import { DashboardPage } from '../../page-objects/DashboardPage'
import { TaskDialogPage } from '../../page-objects/TaskDialogPage'
import { FiltersModalPage } from '../../page-objects/FiltersModalPage'
import { LoginPage } from '../../page-objects/LoginPage'
import { testLoginUsers } from '../../fixtures/auth.fixture'
import { clearAuth } from '../../utils/helpers'

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

    // Login as test user
    await loginPage.goto()
    const { email, password } = testLoginUsers.valid
    await loginPage.fillForm(email, password)
    await page.waitForTimeout(500)
    await loginPage.submit()
    await page.waitForURL('**/dashboard', { timeout: 15000 })

    // Wait for dashboard to load
    await page.waitForTimeout(2000)

    // Create test tasks with different dates for filtering
    const testTasks = [
      { title: 'Task Today 1', date: 'today' },
      { title: 'Task Today 2', date: 'today' },
      { title: 'Task Tomorrow', date: 'tomorrow' }
    ]

    for (const taskData of testTasks) {
      // Wait for any dialog mask to be completely detached from DOM
      await page.locator('.p-dialog-mask').waitFor({ state: 'detached', timeout: 10000 }).catch(() => {})
      await page.waitForTimeout(1000)

      // Now click should work normally without force
      await dashboardPage.createTaskButton.click()
      await page.waitForTimeout(1500)

      await taskDialogPage.fillTitle(taskData.title)
      await page.waitForTimeout(300)

      // Set date
      await taskDialogPage.clickQuickDate(taskData.date)
      await page.waitForTimeout(500)

      await taskDialogPage.saveButton.click()
      await page.waitForTimeout(1500)

      // Wait for dialog and mask to close completely
      await page.locator('.p-dialog').waitFor({ state: 'hidden', timeout: 10000 }).catch(() => {})
      await page.locator('.p-dialog-mask').waitFor({ state: 'hidden', timeout: 10000 }).catch(() => {})

      // Extra wait to ensure mask is fully gone from DOM
      await page.waitForTimeout(2500)
    }

    // Wait for all tasks to be created and navigate to "All tasks" view
    await page.waitForTimeout(1000)
    const allTasksButton = page.getByRole('button', { name: /все задачи/i }).first()
    if (await allTasksButton.isVisible().catch(() => false)) {
      await allTasksButton.click()
      await page.waitForTimeout(1500)
    }
  })

  test.describe('6.1 Quick Filters', () => {
    test('TC-FILTER-001: Apply "На сегодня" quick filter', async ({ page }) => {
      // Get initial task count
      const initialCount = await dashboardPage.getTaskCount()
      expect(initialCount).toBeGreaterThanOrEqual(3) // We created 3 tasks

      // Click "На сегодня" quick filter
      const todayFilterButton = page.getByRole('button', { name: / На сегодня/i }).first()
      await expect(todayFilterButton).toBeVisible({ timeout: 5000 })
      await todayFilterButton.click()
      await page.waitForTimeout(1500)

      // Verify "Очистить фильтры" button appears
      const clearButton = page.getByRole('button', { name: /очистить фильтры/i })
      await expect(clearButton).toBeVisible({ timeout: 3000 })

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
      const urgentFilterButton = page.getByRole('button', { name: / Срочные/i }).first()
      await expect(urgentFilterButton).toBeVisible({ timeout: 5000 })
      await urgentFilterButton.click()
      await page.waitForTimeout(1500)

      // Verify "Очистить фильтры" button appears
      const clearButton = page.getByRole('button', { name: /очистить фильтры/i })
      await expect(clearButton).toBeVisible({ timeout: 3000 })

      // With simple test data (no urgent tasks), we expect empty state or 0 tasks
      const taskCount = await dashboardPage.getTaskCount()
      const isEmpty = await dashboardPage.isEmptyStateVisible()
      expect(taskCount === 0 || isEmpty).toBe(true)
    })

    test('TC-FILTER-003: Apply "Просроченные" quick filter', async ({ page }) => {
      // Click "Просроченные" quick filter
      const overdueFilterButton = page.getByRole('button', { name: / Просроченные/i }).first()
      await expect(overdueFilterButton).toBeVisible({ timeout: 5000 })
      await overdueFilterButton.click()
      await page.waitForTimeout(1500)

      // Verify "Очистить фильтры" button appears
      const clearButton = page.getByRole('button', { name: /очистить фильтры/i })
      await expect(clearButton).toBeVisible({ timeout: 3000 })

      // With simple test data (no overdue tasks), we expect empty state or 0 tasks
      const taskCount = await dashboardPage.getTaskCount()
      const isEmpty = await dashboardPage.isEmptyStateVisible()
      expect(taskCount === 0 || isEmpty).toBe(true)
    })

    test('TC-FILTER-004: Apply "В процессе" quick filter', async ({ page }) => {
      // Click "В процессе" quick filter
      const inProgressFilterButton = page.getByRole('button', { name: / В процессе/i }).first()
      await expect(inProgressFilterButton).toBeVisible({ timeout: 5000 })
      await inProgressFilterButton.click()
      await page.waitForTimeout(1500)

      // Verify "Очистить фильтры" button appears
      const clearButton = page.getByRole('button', { name: /очистить фильтры/i })
      await expect(clearButton).toBeVisible({ timeout: 3000 })

      // With simple test data (default status is 'pending'), we expect empty state
      const taskCount = await dashboardPage.getTaskCount()
      const isEmpty = await dashboardPage.isEmptyStateVisible()
      expect(taskCount === 0 || isEmpty).toBe(true)
    })

    test('TC-FILTER-005: Apply multiple quick filters', async ({ page }) => {
      // Get initial count
      const initialCount = await dashboardPage.getTaskCount()

      // Click "На сегодня" filter
      const todayFilterButton = page.getByRole('button', { name: / На сегодня/i }).first()
      await todayFilterButton.click()
      await page.waitForTimeout(1500)

      // Verify filter is applied
      const clearButtonAfterFirst = page.getByRole('button', { name: /очистить фильтры/i })
      await expect(clearButtonAfterFirst).toBeVisible({ timeout: 3000 })

      const countAfterFirst = await dashboardPage.getTaskCount()
      expect(countAfterFirst).toBeGreaterThanOrEqual(1)

      // Click "Срочные" filter (this should narrow results further)
      const urgentFilterButton = page.getByRole('button', { name: / Срочные/i }).first()
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
      const todayFilterButton = page.getByRole('button', { name: / На сегодня/i }).first()
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
      expect(allTasksCount).toBeGreaterThan(filteredCount)
      expect(allTasksCount).toBeGreaterThanOrEqual(4) // We created 5 tasks (1 completed might be hidden)
    })
  })
})
