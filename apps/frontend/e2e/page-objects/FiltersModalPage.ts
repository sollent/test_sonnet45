import { Page, Locator } from '@playwright/test'

/**
 * Page Object Model for Filters Modal
 */
export class FiltersModalPage {
  readonly page: Page

  // Modal elements
  readonly modal: Locator
  readonly heading: Locator
  readonly closeButton: Locator

  // Preset buttons (top row)
  readonly presetAllTasks: Locator
  readonly presetThisWeek: Locator
  readonly presetImportant: Locator

  // Task Type section
  readonly taskTypeAllTasks: Locator
  readonly taskTypeActive: Locator
  readonly taskTypeCompleted: Locator

  // Priority section
  readonly priorityLow: Locator
  readonly priorityMedium: Locator
  readonly priorityHigh: Locator
  readonly priorityUrgent: Locator

  // Status section
  readonly statusPending: Locator
  readonly statusInProgress: Locator
  readonly statusCompleted: Locator
  readonly statusCanceled: Locator

  // Tags section
  readonly tagsSearchInput: Locator
  readonly popularTagsHeading: Locator

  // Period section
  readonly periodDropdown: Locator
  readonly periodDatePicker: Locator

  // Action buttons
  readonly clearButton: Locator
  readonly applyButton: Locator

  constructor(page: Page) {
    this.page = page

    // Modal
    this.modal = page.locator('.p-dialog, [role="dialog"]').filter({ hasText: /фильтры/i })
    this.heading = page.getByRole('heading', { name: /фильтры/i })
    this.closeButton = this.modal.locator('button').filter({ has: page.locator('.pi-times') })

    // Preset buttons
    this.presetAllTasks = page.getByRole('button', { name: /все задачи/i }).first()
    this.presetThisWeek = page.getByRole('button', { name: /на этой неделе/i })
    this.presetImportant = page.getByRole('button', { name: /важные/i })

    // Task Type
    this.taskTypeAllTasks = page.getByRole('button', { name: /^все задачи$/i }).nth(1)
    this.taskTypeActive = page.getByRole('button', { name: /активные/i })
    this.taskTypeCompleted = page.getByRole('button', { name: /завершенные/i })

    // Priority
    this.priorityLow = page.getByRole('button', { name: /низкий/i })
    this.priorityMedium = page.getByRole('button', { name: /средний/i })
    this.priorityHigh = page.getByRole('button', { name: /высокий/i })
    this.priorityUrgent = page.getByRole('button', { name: /срочный/i })

    // Status
    this.statusPending = page.getByRole('button', { name: /в ожидании/i })
    this.statusInProgress = page.getByRole('button', { name: /в процессе/i })
    this.statusCompleted = page.getByRole('button', { name: /завершена/i })
    this.statusCanceled = page.getByRole('button', { name: /отменена/i })

    // Tags
    this.tagsSearchInput = page.locator('input[placeholder*="Поиск тегов"]')
    this.popularTagsHeading = page.getByRole('heading', { name: /популярные теги/i })

    // Period
    this.periodDropdown = page.locator('select, .p-dropdown').filter({ hasText: /выберите период/i })
    this.periodDatePicker = page.getByRole('button', { name: /выбрать дату/i })

    // Action buttons
    this.clearButton = page.getByRole('button', { name: /очистить/i }).filter({ hasNot: page.locator('.pi-filter-slash') })
    this.applyButton = page.getByRole('button', { name: /применить/i })
  }

  /**
   * Check if modal is visible
   */
  async isVisible(): Promise<boolean> {
    return await this.modal.isVisible().catch(() => false)
  }

  /**
   * Wait for modal to open
   */
  async waitForModal(): Promise<void> {
    await this.modal.waitFor({ state: 'visible', timeout: 5000 })
    await this.page.waitForTimeout(500)
  }

  /**
   * Close modal via close button
   */
  async close(): Promise<void> {
    await this.closeButton.click()
    await this.modal.waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {})
    await this.page.waitForTimeout(500)
  }

  /**
   * Click a preset button
   */
  async clickPreset(preset: 'all' | 'week' | 'important'): Promise<void> {
    const presetMap = {
      all: this.presetAllTasks,
      week: this.presetThisWeek,
      important: this.presetImportant
    }
    await presetMap[preset].click()
    await this.page.waitForTimeout(300)
  }

  /**
   * Select task type
   */
  async selectTaskType(type: 'all' | 'active' | 'completed'): Promise<void> {
    const typeMap = {
      all: this.taskTypeAllTasks,
      active: this.taskTypeActive,
      completed: this.taskTypeCompleted
    }
    await typeMap[type].click()
    await this.page.waitForTimeout(300)
  }

  /**
   * Select priority
   */
  async selectPriority(priority: 'low' | 'medium' | 'high' | 'urgent'): Promise<void> {
    const priorityMap = {
      low: this.priorityLow,
      medium: this.priorityMedium,
      high: this.priorityHigh,
      urgent: this.priorityUrgent
    }
    await priorityMap[priority].click()
    await this.page.waitForTimeout(300)
  }

  /**
   * Select status
   */
  async selectStatus(status: 'pending' | 'in_progress' | 'completed' | 'canceled'): Promise<void> {
    const statusMap = {
      pending: this.statusPending,
      in_progress: this.statusInProgress,
      completed: this.statusCompleted,
      canceled: this.statusCanceled
    }
    await statusMap[status].click()
    await this.page.waitForTimeout(300)
  }

  /**
   * Search for tags
   */
  async searchTags(query: string): Promise<void> {
    await this.tagsSearchInput.fill(query)
    await this.page.waitForTimeout(500)
  }

  /**
   * Click a tag by name
   */
  async clickTag(tagName: string): Promise<void> {
    const tag = this.page.locator('button, .tag').filter({ hasText: new RegExp(tagName, 'i') })
    await tag.click()
    await this.page.waitForTimeout(300)
  }

  /**
   * Apply filters
   */
  async apply(): Promise<void> {
    await this.applyButton.click()
    await this.modal.waitFor({ state: 'hidden', timeout: 5000 }).catch(() => {})
    await this.page.waitForTimeout(1000)
  }

  /**
   * Clear filters within modal
   */
  async clear(): Promise<void> {
    await this.clearButton.click()
    await this.page.waitForTimeout(500)
  }

  /**
   * Check if a filter is active
   */
  async isFilterActive(filterButton: Locator): Promise<boolean> {
    const classes = await filterButton.getAttribute('class') || ''
    return classes.includes('active') || classes.includes('selected')
  }

  /**
   * Get count of active filters from badge
   */
  async getActiveFiltersCount(): Promise<number> {
    const filterButton = this.page.getByRole('button', { name: /фильтры/i })
    const text = await filterButton.textContent()
    const match = text?.match(/\d+/)
    return match ? parseInt(match[0]) : 0
  }
}
