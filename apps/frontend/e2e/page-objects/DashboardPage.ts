import { Page, Locator } from '@playwright/test'

/**
 * Page Object Model for Dashboard Page
 */
export class DashboardPage {
  readonly page: Page
  readonly logoutButton: Locator
  readonly userEmail: Locator
  readonly profileButton: Locator
  
  // View navigation buttons
  readonly allTasksButton: Locator
  readonly todayButton: Locator
  readonly upcomingButton: Locator
  readonly overdueButton: Locator
  readonly unscheduledButton: Locator
  
  // Display mode buttons
  readonly cardsViewButton: Locator
  readonly listViewButton: Locator
  
  // Task containers
  readonly taskCards: Locator
  readonly taskList: Locator
  readonly taskGroups: Locator
  readonly emptyState: Locator
  readonly createTaskButton: Locator
  
  // Loading states
  readonly loadingSkeletons: Locator

  constructor(page: Page) {
    this.page = page
    // Logout button can be in different places, try multiple selectors
    this.logoutButton = page.getByRole('button', { name: /выйти|logout|sign out/i }).or(
      page.locator('button[aria-label*="logout"]').or(
        page.locator('button').filter({ has: page.locator('i.pi-sign-out') })
      )
    ).first()
    this.userEmail = page.locator('.profile-button span, .header-subtitle, [class*="user-email"]')
    this.profileButton = page.getByRole('button', { name: /profile|профиль/i }).or(
      page.locator('button').filter({ has: page.locator('i.pi-user') })
    ).first()
    
    // View navigation buttons (in sidebar) - use view-item class
    this.allTasksButton = page.locator('button.view-item').filter({ hasText: /все задачи|all tasks/i }).first()
    this.todayButton = page.locator('button.view-item').filter({ hasText: /сегодня|today/i }).first()
    this.upcomingButton = page.locator('button.view-item').filter({ hasText: /предстоящие|upcoming/i }).first()
    this.overdueButton = page.locator('button.view-item').filter({ hasText: /просроченные|overdue/i }).first()
    this.unscheduledButton = page.locator('button.view-item').filter({ hasText: /без срока|unscheduled/i }).first()
    
    // Display mode buttons - buttons with icons pi-th-large and pi-list
    this.cardsViewButton = page.locator('button').filter({ has: page.locator('i.pi-th-large') }).first()
    this.listViewButton = page.locator('button').filter({ has: page.locator('i.pi-list') }).filter({ 
      hasNot: page.locator('span.view-label') 
    }).first()
    
    // Task containers - try multiple selectors based on actual structure
    // TaskCard component renders tasks, check for any clickable task elements
    this.taskCards = page.locator('[class*="task-card"], [class*="TaskCard"], .task-card')
    this.taskList = page.locator('.task-item, [class*="task-item"]')
    // Task groups contain day headers and tasks
    this.taskGroups = page.locator('.task-group, [class*="task-group"]')
    this.emptyState = page.locator('.empty-state, [class*="empty-state"]')
    this.createTaskButton = page.getByRole('button', { name: /создать задачу|create task/i }).or(
      page.locator('button').filter({ has: page.locator('i.pi-plus, i.pi-file-edit') })
    ).first()
    
    // Loading states
    this.loadingSkeletons = page.locator('.p-skeleton, [class*="skeleton"]')
  }

  /**
   * Navigate to dashboard
   */
  async goto(): Promise<void> {
    const baseURL = (globalThis as any).process?.env?.PLAYWRIGHT_BASE_URL || 'http://localhost:3000'
    await this.page.goto(`${baseURL}/dashboard`)
    await this.page.waitForLoadState('networkidle')
  }

  /**
   * Click logout button
   */
  async logout(): Promise<void> {
    await this.logoutButton.click()
  }

  /**
   * Wait for redirect to login page after logout
   */
  async waitForLoginRedirect(): Promise<void> {
    await this.page.waitForURL('**/login', { timeout: 10000 })
  }

  /**
   * Check if currently on dashboard page
   */
  async isOnDashboard(): Promise<boolean> {
    return this.page.url().includes('/dashboard')
  }

  /**
   * Get user email from header
   */
  async getUserEmail(): Promise<string | null> {
    const emailElement = await this.userEmail.first()
    if (await emailElement.isVisible().catch(() => false)) {
      return await emailElement.textContent()
    }
    return null
  }

  /**
   * Navigate to a specific view
   */
  async selectView(viewId: 'all' | 'today' | 'upcoming' | 'overdue' | 'unscheduled'): Promise<void> {
    // Build text pattern for the view
    const viewTexts: Record<string, RegExp> = {
      'all': /все задачи|all tasks/i,
      'today': /сегодня|today/i,
      'upcoming': /предстоящие|upcoming/i,
      'overdue': /просроченные|overdue/i,
      'unscheduled': /без срока|unscheduled/i
    }
    
    const viewPattern = viewTexts[viewId]
    
    // Try to find button by text pattern (more reliable)
    const allButtons = this.page.locator('button.view-item')
    const buttonCount = await allButtons.count()
    
    let button: Locator | null = null
    for (let i = 0; i < buttonCount; i++) {
      const btn = allButtons.nth(i)
      const text = await btn.textContent().catch(() => '')
      if (viewPattern.test(text || '')) {
        button = btn
        break
      }
    }
    
    // Fallback to predefined selectors
    if (!button) {
      switch (viewId) {
        case 'all':
          button = this.allTasksButton
          break
        case 'today':
          button = this.todayButton
          break
        case 'upcoming':
          button = this.upcomingButton
          break
        case 'overdue':
          button = this.overdueButton
          break
        case 'unscheduled':
          button = this.unscheduledButton
          break
      }
    }
    
    if (!button) {
      throw new Error(`View button for "${viewId}" not found`)
    }
    
    // Wait for button to be visible and enabled
    try {
      await button.waitFor({ state: 'visible', timeout: 10000 })
    } catch {
      // If not visible, try to scroll to it
      await button.scrollIntoViewIfNeeded()
      await this.page.waitForTimeout(500)
    }
    
    // Scroll into view if needed
    await button.scrollIntoViewIfNeeded()
    
    await button.click()
    
    // Wait for view to load - longer wait for unscheduled/overdue as they use pagination
    if (viewId === 'unscheduled' || viewId === 'overdue') {
      await this.page.waitForTimeout(2000)
    } else {
      await this.page.waitForTimeout(1000)
    }
    
    // Wait for network to be idle, but with timeout
    try {
      await this.page.waitForLoadState('networkidle', { timeout: 10000 })
    } catch {
      // If networkidle times out, just wait a bit more
      await this.page.waitForTimeout(2000)
    }
  }

  /**
   * Check if a view is selected
   */
  async isViewSelected(viewId: string): Promise<boolean> {
    const button = this.page.locator(`button.view-item[data-view="${viewId}"]`).or(
      this.page.locator('button.view-item-active')
    )
    return await button.isVisible().catch(() => false)
  }

  /**
   * Switch to cards view
   */
  async switchToCardsView(): Promise<void> {
    // Try multiple selectors with shorter timeout
    const selectors = [
      this.page.locator('button[aria-label*="cards"], button[aria-label*="карточек"]'),
      this.page.locator('.view-toggle button').filter({ has: this.page.locator('i.pi-th-large') }),
      this.page.locator('button').filter({ has: this.page.locator('i.pi-th-large') }).filter({ hasNot: this.page.locator('.view-item') })
    ]
    
    let button: Locator | null = null
    for (const selector of selectors) {
      try {
        const btn = selector.first()
        await btn.waitFor({ state: 'visible', timeout: 2000 })
        button = btn
        break
      } catch {
        continue
      }
    }
    
    if (!button) {
      throw new Error('Cards view button not found')
    }
    
    await button.scrollIntoViewIfNeeded()
    await button.click({ timeout: 5000 })
    await this.page.waitForTimeout(1000) // Wait for view to switch
  }

  /**
   * Switch to list view
   */
  async switchToListView(): Promise<void> {
    // Try multiple selectors (exclude sidebar buttons with pi-list) with shorter timeout
    const selectors = [
      this.page.locator('button[aria-label*="table"], button[aria-label*="таблицы"]'),
      this.page.locator('.view-toggle button').filter({ has: this.page.locator('i.pi-list') }),
      this.page.locator('button').filter({ has: this.page.locator('i.pi-list') }).filter({ hasNot: this.page.locator('.view-item, .sidebar') })
    ]
    
    let button: Locator | null = null
    for (const selector of selectors) {
      try {
        const btn = selector.first()
        await btn.waitFor({ state: 'visible', timeout: 2000 })
        button = btn
        break
      } catch {
        continue
      }
    }
    
    if (!button) {
      throw new Error('List view button not found')
    }
    
    await button.scrollIntoViewIfNeeded()
    await button.click({ timeout: 5000 })
    await this.page.waitForTimeout(1000) // Wait for view to switch
  }

  /**
   * Get count of displayed tasks
   */
  async getTaskCount(): Promise<number> {
    // Wait a bit for tasks to render
    await this.page.waitForTimeout(1000)
    
    // Try multiple selectors to find tasks
    // Check for task cards (TaskCard component)
    const cardsCount = await this.taskCards.count()
    if (cardsCount > 0) return cardsCount
    
    // Check for task list items
    const listCount = await this.taskList.count()
    if (listCount > 0) return listCount
    
    // Check for checkboxes inside task containers (more reliable)
    const taskCheckboxes = await this.page.locator('.task-group input[type="checkbox"], [class*="task"] input[type="checkbox"]').count()
    if (taskCheckboxes > 0) return taskCheckboxes
    
    // Check for any clickable task elements (tasks are clickable)
    const clickableTasks = await this.page.locator('[class*="task"]').filter({ 
      has: this.page.locator('h3, h4, [class*="title"]')
    }).count()
    if (clickableTasks > 0) return clickableTasks
    
    return 0
  }

  /**
   * Check if tasks are displayed as cards
   */
  async isCardsView(): Promise<boolean> {
    const cardsVisible = await this.taskCards.first().isVisible().catch(() => false)
    return cardsVisible && (await this.taskCards.count()) > 0
  }

  /**
   * Check if tasks are displayed as list
   */
  async isListView(): Promise<boolean> {
    const listVisible = await this.taskList.first().isVisible().catch(() => false)
    return listVisible && (await this.taskList.count()) > 0
  }

  /**
   * Get count of task groups (grouped by date)
   */
  async getTaskGroupCount(): Promise<number> {
    return await this.taskGroups.count()
  }

  /**
   * Check if empty state is displayed
   */
  async isEmptyStateVisible(): Promise<boolean> {
    return await this.emptyState.isVisible().catch(() => false)
  }

  /**
   * Check if loading skeletons are visible
   */
  async isLoading(): Promise<boolean> {
    return await this.loadingSkeletons.first().isVisible().catch(() => false)
  }

  /**
   * Wait for tasks to load (no loading skeletons)
   */
  async waitForTasksToLoad(): Promise<void> {
    // Wait for loading to finish
    await this.page.waitForFunction(
      () => {
        const skeletons = document.querySelectorAll('.p-skeleton, [class*="skeleton"]')
        return skeletons.length === 0
      },
      { timeout: 10000 }
    )
    await this.page.waitForTimeout(1000)
  }

  /**
   * Get console errors
   */
  async getConsoleErrors(): Promise<string[]> {
    const errors: string[] = []
    this.page.on('console', msg => {
      if (msg.type() === 'error') {
        errors.push(msg.text())
      }
    })
    return errors
  }

  /**
   * Get network errors (401, 500, etc.)
   */
  async getNetworkErrors(): Promise<number[]> {
    const errors: number[] = []
    this.page.on('response', response => {
      const status = response.status()
      if (status >= 400) {
        errors.push(status)
      }
    })
    return errors
  }

  /**
   * Expand completed tasks section if it's collapsed
   */
  async expandCompletedSection(): Promise<boolean> {
    // Look for completed section button with different possible texts
    const completedButtons = [
      this.page.locator('button, [role="button"]').filter({ hasText: /✅\s*Completed/i }),
      this.page.locator('button, [role="button"]').filter({ hasText: /✅\s*Завершен/i }),
      this.page.locator('[class*="completed"]').filter({ hasText: /✅/ }),
      this.page.locator('*[cursor="pointer"]').filter({ hasText: /✅\s*Completed/i })
    ]

    for (const selector of completedButtons) {
      try {
        const count = await selector.count()
        if (count > 0) {
          // Click all completed section toggles to expand them
          for (let i = 0; i < count; i++) {
            const btn = selector.nth(i)
            const isVisible = await btn.isVisible().catch(() => false)
            if (isVisible) {
              await btn.scrollIntoViewIfNeeded()
              await this.page.waitForTimeout(300)
              await btn.click({ force: true })
              await this.page.waitForTimeout(500)
            }
          }
          return true
        }
      } catch {
        continue
      }
    }

    return false
  }

  /**
   * Get first visible task card (either active or completed)
   */
  async getFirstVisibleTask(): Promise<Locator | null> {
    // First try to expand completed sections
    await this.expandCompletedSection()
    await this.page.waitForTimeout(1000)

    // Try multiple selectors to find tasks
    const taskSelectors = [
      this.taskCards,
      this.taskList,
      this.page.locator('[class*="task-card"]'),
      this.page.locator('.task-item, [class*="task-item"]'),
      this.page.locator('[class*="task"]').filter({
        has: this.page.locator('input[type="checkbox"]')
      })
    ]

    for (const selector of taskSelectors) {
      try {
        const count = await selector.count()
        if (count > 0) {
          const task = selector.first()
          const isVisible = await task.isVisible({ timeout: 2000 }).catch(() => false)
          if (isVisible) {
            return task
          }
        }
      } catch {
        continue
      }
    }

    return null
  }

  /**
   * Find task by title (searches in both active and completed sections)
   */
  async findTaskByTitle(title: string): Promise<Locator | null> {
    // First try to expand completed sections
    await this.expandCompletedSection()
    await this.page.waitForTimeout(1000)

    // Escape special regex characters in title
    const escapedTitle = title.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
    const titlePattern = new RegExp(escapedTitle, 'i')

    // Try multiple selectors to find the task
    const taskSelectors = [
      // Try exact text match first
      this.page.getByText(titlePattern, { exact: false }),
      // Try in task cards
      this.page.locator('.task-card').filter({ hasText: titlePattern }),
      this.page.locator('[class*="task-card"]').filter({ hasText: titlePattern }),
      // Try in task items
      this.page.locator('.task-item').filter({ hasText: titlePattern }),
      this.page.locator('[class*="task-item"]').filter({ hasText: titlePattern }),
      // Try in any task container
      this.page.locator('[class*="task"]').filter({ hasText: titlePattern }),
    ]

    for (const selector of taskSelectors) {
      try {
        const count = await selector.count()
        if (count > 0) {
          const task = selector.first()
          const isVisible = await task.isVisible({ timeout: 2000 }).catch(() => false)
          if (isVisible) {
            return task
          }
        }
      } catch {
        continue
      }
    }

    return null
  }
}
