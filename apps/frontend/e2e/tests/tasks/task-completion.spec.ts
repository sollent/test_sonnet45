import { test, expect } from '@playwright/test'
import { DashboardPage } from '../../page-objects/DashboardPage'
import { TaskDialogPage } from '../../page-objects/TaskDialogPage'
import { LoginPage } from '../../page-objects/LoginPage'
import { testLoginUsers } from '../../fixtures/auth.fixture'
import { clearAuth } from '../../utils/helpers'

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

  test('TC-COMPLETE-001: Complete task from task card checkbox', async ({ page }) => {
    // First, create a test task
    await dashboardPage.createTaskButton.click()
    await page.waitForTimeout(1000)

    await taskDialogPage.fillTitle('Test Task for Completion')
    await page.waitForTimeout(300)
    await taskDialogPage.clickQuickDate('today')
    await page.waitForTimeout(500)
    await taskDialogPage.saveButton.click()
    await page.waitForTimeout(1000)

    // Wait for dialog and mask to disappear completely
    await page.locator('.p-dialog').waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {})
    await page.locator('.p-dialog-mask').waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {})
    await page.waitForTimeout(1500)

    // Navigate to "Today" view to ensure task is visible
    const todayButton = page.getByRole('button', { name: /сегодня|today/i }).first()
    if (await todayButton.isVisible().catch(() => false)) {
      await todayButton.click()
      await page.waitForTimeout(1500)
    }

    // Find the created task
    const task = await dashboardPage.findTaskByTitle('Test Task for Completion')
    expect(task).not.toBeNull()

    if (!task) return

    // Find checkbox within the task
    const checkbox = task.locator('input[type="checkbox"]').first()
    await expect(checkbox).toBeVisible({ timeout: 5000 })

    // Get initial state
    const initiallyChecked = await checkbox.isChecked()
    expect(initiallyChecked).toBe(false)

    // Complete the task by checking the checkbox
    await checkbox.scrollIntoViewIfNeeded()
    await page.waitForTimeout(300)
    await checkbox.click({ force: true })

    // Wait for optimistic update
    await page.waitForTimeout(1000)

    // Verify checkbox is now checked (optimistic update)
    const isChecked = await checkbox.isChecked()
    expect(isChecked).toBe(true)

    // Wait for API call to complete
    await page.waitForTimeout(2000)

    // Task should still be visible in completed state
    const taskStillExists = await dashboardPage.findTaskByTitle('Test Task for Completion')
    expect(taskStillExists).not.toBeNull()

    // Verify task appears completed (might have visual indicator like strikethrough)
    if (taskStillExists) {
      const hasCompletedClass = await taskStillExists.evaluate((el) => {
        return el.className.includes('completed') ||
               el.className.includes('checked') ||
               el.querySelector('[class*="completed"]') !== null
      })
      expect(hasCompletedClass).toBe(true)
    }
  })

  test('TC-COMPLETE-002: Uncomplete task from checkbox', async ({ page }) => {
    // First, create and complete a task
    await dashboardPage.createTaskButton.click()
    await page.waitForTimeout(1000)

    await taskDialogPage.fillTitle('Task to Uncomplete')
    await page.waitForTimeout(300)
    await taskDialogPage.clickQuickDate('today')
    await page.waitForTimeout(500)
    await taskDialogPage.saveButton.click()
    await page.waitForTimeout(1000)

    // Wait for dialog and mask to disappear completely
    await page.locator('.p-dialog').waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {})
    await page.locator('.p-dialog-mask').waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {})
    await page.waitForTimeout(1500)

    // Navigate to "Today" view
    const todayButton = page.getByRole('button', { name: /сегодня|today/i }).first()
    if (await todayButton.isVisible().catch(() => false)) {
      await todayButton.click()
      await page.waitForTimeout(1500)
    }

    // Find and complete the task
    const task = await dashboardPage.findTaskByTitle('Task to Uncomplete')
    expect(task).not.toBeNull()

    if (!task) return

    const checkbox = task.locator('input[type="checkbox"]').first()
    await checkbox.scrollIntoViewIfNeeded()
    await page.waitForTimeout(300)
    await checkbox.click({ force: true })
    await page.waitForTimeout(2000)

    // Verify it's completed
    let isChecked = await checkbox.isChecked()
    expect(isChecked).toBe(true)

    // Now uncomplete it
    await checkbox.click({ force: true })
    await page.waitForTimeout(1000)

    // Verify checkbox is now unchecked (optimistic update)
    isChecked = await checkbox.isChecked()
    expect(isChecked).toBe(false)

    // Wait for API call
    await page.waitForTimeout(2000)

    // Task should still exist and be uncompleted
    const taskAfter = await dashboardPage.findTaskByTitle('Task to Uncomplete')
    expect(taskAfter).not.toBeNull()

    if (taskAfter) {
      const completedCheckbox = taskAfter.locator('input[type="checkbox"]').first()
      const finalState = await completedCheckbox.isChecked()
      expect(finalState).toBe(false)
    }
  })

  test('TC-COMPLETE-003: Complete task with subtasks', async ({ page }) => {
    // Create parent task
    await dashboardPage.createTaskButton.click()
    await page.waitForTimeout(1000)

    await taskDialogPage.fillTitle('Parent Task with Subtasks')
    await page.waitForTimeout(300)
    await taskDialogPage.clickQuickDate('today')
    await page.waitForTimeout(500)
    await taskDialogPage.saveButton.click()
    await page.waitForTimeout(1000)

    // Wait for dialog and mask to disappear completely
    await page.locator('.p-dialog').waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {})
    await page.locator('.p-dialog-mask').waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {})
    await page.waitForTimeout(1500)

    // Navigate to "Today" view
    const todayButton = page.getByRole('button', { name: /сегодня|today/i }).first()
    if (await todayButton.isVisible().catch(() => false)) {
      await todayButton.click()
      await page.waitForTimeout(1500)
    }

    // Find the parent task and click to open details
    const parentTask = await dashboardPage.findTaskByTitle('Parent Task with Subtasks')
    expect(parentTask).not.toBeNull()

    if (!parentTask) return

    // Click on task to open details sidebar
    await parentTask.click()
    await page.waitForTimeout(1500)

    // Add a subtask using subtask input
    const subtaskInput = page.locator('input[placeholder*="добавить подзадачу"], input[placeholder*="add subtask"]').first()
    if (await subtaskInput.isVisible().catch(() => false)) {
      await subtaskInput.fill('Subtask 1')
      await page.keyboard.press('Enter')
      await page.waitForTimeout(1000)
    }

    // Close sidebar
    const closeButton = page.getByRole('button', { name: /закрыть|close/i }).first()
    if (await closeButton.isVisible().catch(() => false)) {
      await closeButton.click()
      await page.waitForTimeout(1000)
    }

    // Now complete the parent task
    const parentTaskAgain = await dashboardPage.findTaskByTitle('Parent Task with Subtasks')
    if (parentTaskAgain) {
      const checkbox = parentTaskAgain.locator('input[type="checkbox"]').first()
      await checkbox.scrollIntoViewIfNeeded()
      await page.waitForTimeout(300)
      await checkbox.click({ force: true })
      await page.waitForTimeout(2000)

      // Verify parent is completed
      const isChecked = await checkbox.isChecked()
      expect(isChecked).toBe(true)

      // Progress should show 100% if subtasks are auto-completed
      // This depends on implementation, might need adjustment
    }
  })

  test('TC-COMPLETE-004: Complete task from sidebar button', async ({ page }) => {
    // Create a task
    await dashboardPage.createTaskButton.click()
    await page.waitForTimeout(1000)

    await taskDialogPage.fillTitle('Task to Complete from Sidebar')
    await page.waitForTimeout(300)
    await taskDialogPage.clickQuickDate('today')
    await page.waitForTimeout(500)
    await taskDialogPage.saveButton.click()
    await page.waitForTimeout(1000)

    // Wait for dialog and mask to disappear completely
    await page.locator('.p-dialog').waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {})
    await page.locator('.p-dialog-mask').waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {})
    await page.waitForTimeout(1500)

    // Navigate to "Today" view
    const todayButton = page.getByRole('button', { name: /сегодня|today/i }).first()
    if (await todayButton.isVisible().catch(() => false)) {
      await todayButton.click()
      await page.waitForTimeout(1500)
    }

    // Find and open the task
    const task = await dashboardPage.findTaskByTitle('Task to Complete from Sidebar')
    expect(task).not.toBeNull()

    if (!task) return

    // Click on task to open details
    await task.click()
    await page.waitForTimeout(1500)

    // Look for "Mark as completed" or similar button in sidebar
    const completeButtons = [
      page.getByRole('button', { name: /отметить как завершенную|mark as completed/i }),
      page.getByRole('button', { name: /завершить|complete/i }),
      page.locator('button').filter({ has: page.locator('i.pi-check') })
    ]

    let completeButton = null
    for (const btn of completeButtons) {
      if (await btn.isVisible().catch(() => false)) {
        completeButton = btn
        break
      }
    }

    if (completeButton) {
      await completeButton.click()
      await page.waitForTimeout(1500)

      // Verify sidebar updates to show completed state
      const completedIndicator = page.locator('[class*="completed"], .task-completed, .completed-badge')
      const hasCompletedIndicator = await completedIndicator.isVisible().catch(() => false)

      // Either completed indicator is shown OR an "Uncomplete" button appears
      const uncompleteButton = page.getByRole('button', { name: /вернуть в невыполненные|uncomplete|mark as active/i })
      const hasUncompleteButton = await uncompleteButton.isVisible().catch(() => false)

      expect(hasCompletedIndicator || hasUncompleteButton).toBe(true)
    }

    // Close sidebar and verify task card shows completed state
    const closeButton = page.getByRole('button', { name: /закрыть|close/i }).first()
    if (await closeButton.isVisible().catch(() => false)) {
      await closeButton.click()
      await page.waitForTimeout(1000)
    }

    // Find task again and verify checkbox is checked
    const taskAfter = await dashboardPage.findTaskByTitle('Task to Complete from Sidebar')
    if (taskAfter) {
      const checkbox = taskAfter.locator('input[type="checkbox"]').first()
      const isChecked = await checkbox.isChecked()
      expect(isChecked).toBe(true)
    }
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
    await page.locator('.p-dialog').waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {})
    await page.locator('.p-dialog-mask').waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {})
    await page.waitForTimeout(1500)

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
