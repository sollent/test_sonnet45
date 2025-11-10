import { Page, Locator } from '@playwright/test'

/**
 * Page Object Model for Calendar Page
 */
export class CalendarPage {
  readonly page: Page

  // Navigation
  readonly monthViewButton: Locator
  readonly weekViewButton: Locator
  readonly prevButton: Locator
  readonly nextButton: Locator
  readonly todayButton: Locator
  readonly monthYearTitle: Locator

  // Calendar elements
  readonly calendarGrid: Locator
  readonly calendarDays: Locator
  readonly taskEvents: Locator
  readonly selectedDatePanel: Locator
  readonly createTaskButton: Locator

  constructor(page: Page) {
    this.page = page

    // View buttons
    this.monthViewButton = page.getByRole('button', { name: /месяц|month/i })
    this.weekViewButton = page.getByRole('button', { name: /неделя|week/i })

    // Navigation buttons
    this.prevButton = page.locator('button:has(.pi-chevron-left)')
    this.nextButton = page.locator('button:has(.pi-chevron-right)')
    this.todayButton = page.getByRole('button', { name: /сегодня|today/i })

    // Month/Year title
    this.monthYearTitle = page.locator('h2, .calendar-title, [class*="month-year"]').filter({
      hasText: /январь|февраль|март|апрель|май|июнь|июль|август|сентябрь|октябрь|ноябрь|декабрь|january|february|march|april|may|june|july|august|september|october|november|december/i
    }).first()

    // Calendar grid and days
    this.calendarGrid = page.locator('.calendar-grid, .calendar-body, [class*="calendar"]').filter({
      has: page.locator('[class*="day"], .day')
    }).first()
    this.calendarDays = page.locator('.calendar-day, .day, [class*="calendar-day"], [class*="day-cell"]')

    // Task events in calendar
    this.taskEvents = page.locator('.task-event, .calendar-task, [class*="task-event"], [class*="calendar-task"]')

    // Selected date panel (shows tasks for selected day)
    this.selectedDatePanel = page.locator('.selected-date-panel, .day-tasks, [class*="day-tasks"]').first()

    // Create task button
    this.createTaskButton = page.getByRole('button', { name: /новая задача|new task|create/i })
  }

  /**
   * Navigate to calendar page
   */
  async goto(): Promise<void> {
    const baseURL = (globalThis as any).process?.env?.PLAYWRIGHT_BASE_URL || 'http://localhost:3000'
    await this.page.goto(`${baseURL}/calendar`)
    await this.page.waitForLoadState('networkidle')
    await this.page.waitForTimeout(1000)
  }

  /**
   * Check if on calendar page
   */
  async isOnCalendar(): Promise<boolean> {
    return this.page.url().includes('/calendar')
  }

  /**
   * Switch to month view
   */
  async switchToMonthView(): Promise<void> {
    await this.monthViewButton.waitFor({ state: 'visible', timeout: 5000 })
    await this.monthViewButton.click()
    await this.page.waitForTimeout(1000)
  }

  /**
   * Switch to week view
   */
  async switchToWeekView(): Promise<void> {
    await this.weekViewButton.waitFor({ state: 'visible', timeout: 5000 })
    await this.weekViewButton.click()
    await this.page.waitForTimeout(1000)
  }

  /**
   * Go to previous month/week
   */
  async goToPrevious(): Promise<void> {
    await this.prevButton.waitFor({ state: 'visible', timeout: 5000 })
    await this.prevButton.click()
    await this.page.waitForTimeout(1500) // Wait for calendar to load
  }

  /**
   * Go to next month/week
   */
  async goToNext(): Promise<void> {
    await this.nextButton.waitFor({ state: 'visible', timeout: 5000 })
    await this.nextButton.click()
    await this.page.waitForTimeout(1500) // Wait for calendar to load
  }

  /**
   * Go to today
   */
  async goToToday(): Promise<void> {
    await this.todayButton.waitFor({ state: 'visible', timeout: 5000 })
    await this.todayButton.click()
    await this.page.waitForTimeout(1000)
  }

  /**
   * Get current month/year title
   */
  async getMonthYearTitle(): Promise<string> {
    await this.monthYearTitle.waitFor({ state: 'visible', timeout: 5000 })
    return await this.monthYearTitle.textContent() || ''
  }

  /**
   * Get count of visible days in calendar
   */
  async getDayCount(): Promise<number> {
    return await this.calendarDays.count()
  }

  /**
   * Click on a specific day
   */
  async clickDay(dayNumber: number): Promise<void> {
    // Find day cell with specific number
    const dayCell = this.calendarDays.filter({ hasText: new RegExp(`^${dayNumber}$`) }).first()
    await dayCell.waitFor({ state: 'visible', timeout: 5000 })
    await dayCell.scrollIntoViewIfNeeded()
    await dayCell.click()
    await this.page.waitForTimeout(1000)
  }

  /**
   * Click on a day by date string (e.g., "2025-11-10")
   */
  async clickDayByDate(dateString: string): Promise<void> {
    const [year, month, day] = dateString.split('-').map(Number)
    const dayNumber = day

    // Might need to navigate to correct month first
    // For now, just click the day number
    await this.clickDay(dayNumber)
  }

  /**
   * Get count of task events in calendar
   */
  async getTaskEventCount(): Promise<number> {
    return await this.taskEvents.count()
  }

  /**
   * Get tasks for a specific day
   */
  async getTasksForDay(dayNumber: number): Promise<Locator[]> {
    // Click on day first to see tasks
    await this.clickDay(dayNumber)
    await this.page.waitForTimeout(500)

    // Find tasks in selected date panel
    const tasks = this.selectedDatePanel.locator('.task, [class*="task"]')
    const count = await tasks.count()

    const taskList: Locator[] = []
    for (let i = 0; i < count; i++) {
      taskList.push(tasks.nth(i))
    }

    return taskList
  }

  /**
   * Check if selected date panel is visible
   */
  async isSelectedDatePanelVisible(): Promise<boolean> {
    return await this.selectedDatePanel.isVisible().catch(() => false)
  }

  /**
   * Click on a task in calendar
   */
  async clickTaskInCalendar(taskTitle: string): Promise<void> {
    const task = this.taskEvents.filter({ hasText: new RegExp(taskTitle, 'i') }).first()
    await task.waitFor({ state: 'visible', timeout: 5000 })
    await task.click()
    await this.page.waitForTimeout(1000)
  }

  /**
   * Create task from calendar date
   */
  async createTaskFromDate(dayNumber: number): Promise<void> {
    // Click on day
    await this.clickDay(dayNumber)
    await this.page.waitForTimeout(500)

    // Click create task button in selected date panel
    if (await this.createTaskButton.isVisible().catch(() => false)) {
      await this.createTaskButton.click()
      await this.page.waitForTimeout(1000)
    }
  }

  /**
   * Check if calendar is in month view
   */
  async isMonthView(): Promise<boolean> {
    // Month view typically shows 28-42 days (4-6 weeks)
    const dayCount = await this.getDayCount()
    return dayCount >= 28 && dayCount <= 42
  }

  /**
   * Check if calendar is in week view
   */
  async isWeekView(): Promise<boolean> {
    // Week view shows 7 days
    const dayCount = await this.getDayCount()
    return dayCount === 7
  }

  /**
   * Wait for calendar to load
   */
  async waitForCalendarToLoad(): Promise<void> {
    await this.calendarGrid.waitFor({ state: 'visible', timeout: 10000 })
    await this.page.waitForTimeout(1000)
  }

  /**
   * Get task count for selected date
   */
  async getSelectedDateTaskCount(): Promise<number> {
    if (await this.isSelectedDatePanelVisible()) {
      const tasks = this.selectedDatePanel.locator('.task, [class*="task"]')
      return await tasks.count()
    }
    return 0
  }

  /**
   * Check if empty state is shown for selected date
   */
  async isEmptyStateVisible(): Promise<boolean> {
    const emptyState = this.selectedDatePanel.locator('.empty-state, [class*="empty"]').filter({
      hasText: /нет задач|no tasks/i
    })
    return await emptyState.isVisible().catch(() => false)
  }
}
