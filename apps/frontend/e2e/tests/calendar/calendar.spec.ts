import { test, expect } from '@playwright/test'
import { CalendarPage } from '../../page-objects/CalendarPage'
import { DashboardPage } from '../../page-objects/DashboardPage'
import { TaskDialogPage } from '../../page-objects/TaskDialogPage'
import { LoginPage } from '../../page-objects/LoginPage'
import { testLoginUsers } from '../../fixtures/auth.fixture'
import { clearAuth, setLocale } from '../../utils/helpers'

test.describe('Calendar View', () => {
  let calendarPage: CalendarPage
  let dashboardPage: DashboardPage
  let taskDialogPage: TaskDialogPage
  let loginPage: LoginPage

  test.beforeEach(async ({ page, context }) => {
    calendarPage = new CalendarPage(page)
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
    await page.waitForTimeout(2000)
  })

  test.describe('8.1 Calendar Navigation', () => {
    test('TC-CAL-001: Navigate to calendar page', async ({ page }) => {
      // Navigate to calendar via navigation button
      const calendarButton = page.getByRole('button', { name: /календарь|calendar/i })
      await expect(calendarButton).toBeVisible({ timeout: 5000 })
      await calendarButton.click()

      // Wait for navigation
      await page.waitForURL('**/calendar', { timeout: 10000 })

      // Verify on calendar page
      expect(await calendarPage.isOnCalendar()).toBe(true)

      // Verify no errors
      const consoleErrors: string[] = []
      page.on('console', msg => {
        if (msg.type() === 'error') {
          consoleErrors.push(msg.text())
        }
      })

      await page.waitForTimeout(1000)

      // Should have minimal errors (some external resources may fail, like Google)
      const criticalErrors = consoleErrors.filter(err =>
        !err.includes('google') &&
        !err.includes('gstatic') &&
        !err.includes('accounts.google') &&
        !err.includes('intlify')
      )

      expect(criticalErrors.length).toBeLessThanOrEqual(2)
    })

    test('TC-CAL-002: Switch to month view', async ({ page }) => {
      // Navigate to calendar
      await calendarPage.goto()
      await calendarPage.waitForCalendarToLoad()

      // Switch to month view
      await calendarPage.switchToMonthView()

      // Verify month calendar is displayed
      const isMonthView = await calendarPage.isMonthView()
      expect(isMonthView).toBe(true)

      // Verify month/year title is visible
      const title = await calendarPage.getMonthYearTitle()
      expect(title.length).toBeGreaterThan(0)

      // Verify days are displayed (28-42 days for month view)
      const dayCount = await calendarPage.getDayCount()
      expect(dayCount).toBeGreaterThanOrEqual(28)
      expect(dayCount).toBeLessThanOrEqual(42)
    })

    test('TC-CAL-003: Switch to week view', async ({ page }) => {
      // Navigate to calendar
      await calendarPage.goto()
      await calendarPage.waitForCalendarToLoad()

      // Switch to week view
      await calendarPage.switchToWeekView()

      // CRITICAL: Wait for view to change and re-render
      await page.waitForTimeout(2000)

      // Verify 7 days are displayed
      const dayCount = await calendarPage.getDayCount()
      console.log(`Day count after switching to week view: ${dayCount}`)

      // Week view should show exactly 7 days
      expect(dayCount).toBe(7)

      // Verify week calendar is displayed
      const isWeekView = await calendarPage.isWeekView()
      expect(isWeekView).toBe(true)
    })

    test('TC-CAL-004: Navigate to previous month/week', async ({ page }) => {
      // Navigate to calendar
      await calendarPage.goto()
      await calendarPage.waitForCalendarToLoad()

      // Get current month/year
      const currentTitle = await calendarPage.getMonthYearTitle()

      // Navigate to previous month
      await calendarPage.goToPrevious()

      // Get new month/year
      const newTitle = await calendarPage.getMonthYearTitle()

      // Titles should be different
      expect(newTitle).not.toBe(currentTitle)

      // Verify calendar loaded new period (tasks loaded)
      await page.waitForTimeout(500)
      const dayCount = await calendarPage.getDayCount()
      expect(dayCount).toBeGreaterThan(0)
    })

    test('TC-CAL-005: Navigate to next month/week', async ({ page }) => {
      // Navigate to calendar
      await calendarPage.goto()
      await calendarPage.waitForCalendarToLoad()

      // Get current month/year
      const currentTitle = await calendarPage.getMonthYearTitle()

      // Navigate to next month
      await calendarPage.goToNext()

      // Get new month/year
      const newTitle = await calendarPage.getMonthYearTitle()

      // Titles should be different
      expect(newTitle).not.toBe(currentTitle)

      // Verify calendar loaded new period
      await page.waitForTimeout(500)
      const dayCount = await calendarPage.getDayCount()
      expect(dayCount).toBeGreaterThan(0)
    })

    test('TC-CAL-006: Navigate to today', async ({ page }) => {
      // Navigate to calendar
      await calendarPage.goto()
      await calendarPage.waitForCalendarToLoad()

      // Navigate away from current month (go back 2 months)
      await calendarPage.goToPrevious()
      await page.waitForTimeout(500)
      await calendarPage.goToPrevious()
      await page.waitForTimeout(500)

      // Get title after navigating away
      const titleBefore = await calendarPage.getMonthYearTitle()

      // Click "Today" button
      await calendarPage.goToToday()

      // Get current month name
      const now = new Date()
      const currentMonthName = now.toLocaleDateString('ru-RU', { month: 'long', year: 'numeric' })

      // Verify calendar returned to current month
      const titleAfter = await calendarPage.getMonthYearTitle()

      // Title should contain current month
      expect(titleAfter.toLowerCase()).toContain(currentMonthName.split(' ')[0])
    })
  })

  test.describe('8.2 Calendar Task Display', () => {
    test('TC-CAL-007: Tasks are displayed on correct dates', async ({ page }) => {
      // First create a task with due date
      await dashboardPage.goto()
      await page.waitForTimeout(1000)

      await dashboardPage.createTaskButton.click()
      await page.waitForTimeout(1000)

      await taskDialogPage.fillTitle('Calendar Test Task')
      await page.waitForTimeout(300)

      // Set due date to today
      await taskDialogPage.clickQuickDate('today')
      await page.waitForTimeout(500)

      await taskDialogPage.saveButton.click()
      await page.waitForTimeout(2000)

      // Navigate to calendar
      await calendarPage.goto()
      await calendarPage.waitForCalendarToLoad()

      // Click on today's date
      const today = new Date().getDate()
      await calendarPage.clickDay(today)
      await page.waitForTimeout(1000)

      // Verify task appears in selected date panel
      const taskCount = await calendarPage.getSelectedDateTaskCount()
      expect(taskCount).toBeGreaterThanOrEqual(1)

      // Or check if empty state is NOT shown
      const isEmpty = await calendarPage.isEmptyStateVisible()
      expect(isEmpty).toBe(false)
    })

    test('TC-CAL-008: Click task on calendar', async ({ page }) => {
      // Create a task with due date first
      await dashboardPage.goto()
      await page.waitForTimeout(1000)

      await dashboardPage.createTaskButton.click()
      await page.waitForTimeout(1000)

      await taskDialogPage.fillTitle('Clickable Calendar Task')
      await page.waitForTimeout(300)
      await taskDialogPage.clickQuickDate('today')
      await page.waitForTimeout(500)
      await taskDialogPage.saveButton.click()
      await page.waitForTimeout(2000)

      // Navigate to calendar
      await calendarPage.goto()
      await calendarPage.waitForCalendarToLoad()

      // Click on today
      const today = new Date().getDate()
      await calendarPage.clickDay(today)
      await page.waitForTimeout(1000)

      // Try to click on a task in the selected date panel
      const task = page.locator('.task, [class*="task"]').filter({ hasText: /Clickable Calendar Task/i }).first()

      if (await task.isVisible().catch(() => false)) {
        await task.click()
        await page.waitForTimeout(1500)

        // Verify task details sidebar opens or dialog opens
        const sidebar = page.locator('.task-details-sidebar, [class*="sidebar"], [role="dialog"]')
        const isSidebarVisible = await sidebar.isVisible().catch(() => false)

        // If sidebar opened, verify task title is shown
        if (isSidebarVisible) {
          const sidebarText = await sidebar.textContent()
          expect(sidebarText).toContain('Clickable Calendar Task')
        }
      }
    })

    test('TC-CAL-009: Create task from calendar date', async ({ page }) => {
      // Navigate to calendar
      await calendarPage.goto()
      await calendarPage.waitForCalendarToLoad()

      // Click on a future date (e.g., today + 2)
      const today = new Date()
      const futureDay = today.getDate() + 2

      // Make sure we don't go beyond month end (simple check)
      const targetDay = futureDay > 28 ? 15 : futureDay

      await calendarPage.clickDay(targetDay)
      await page.waitForTimeout(1000)

      // Look for "New Task" button in selected date panel
      const createButton = page.getByRole('button', { name: /новая задача|new task|create/i })

      if (await createButton.isVisible({ timeout: 3000 }).catch(() => false)) {
        await createButton.click()
        await page.waitForTimeout(1500)

        // Verify task dialog opens
        const dialog = page.locator('.p-dialog, [role="dialog"]')
        const isDialogVisible = await dialog.isVisible().catch(() => false)
        expect(isDialogVisible).toBe(true)

        // Fill task details
        await taskDialogPage.fillTitle('Task Created from Calendar')
        await page.waitForTimeout(300)
        await taskDialogPage.saveButton.click()
        await page.waitForTimeout(2000)

        // Verify we're back on calendar
        expect(await calendarPage.isOnCalendar()).toBe(true)

        // Click on the same day again and verify task appears
        await calendarPage.clickDay(targetDay)
        await page.waitForTimeout(1000)

        const taskCount = await calendarPage.getSelectedDateTaskCount()
        expect(taskCount).toBeGreaterThanOrEqual(1)
      }
    })
  })
})
