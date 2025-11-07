import { Page, Locator } from '@playwright/test'

/**
 * Page Object Model for Task Dialog (Create/Edit)
 */
export class TaskDialogPage {
  readonly page: Page
  
  // Dialog elements
  readonly dialog: Locator
  readonly dialogTitle: Locator
  readonly closeButton: Locator
  
  // Form fields
  readonly titleInput: Locator
  readonly descriptionTextarea: Locator
  readonly statusDropdown: Locator
  readonly priorityDropdown: Locator
  readonly startDateCalendar: Locator
  readonly dueDateCalendar: Locator
  
  // Quick date buttons
  readonly todayButton: Locator
  readonly tomorrowButton: Locator
  readonly dayAfterButton: Locator
  
  // Advanced date toggle
  readonly advancedDateToggle: Locator
  
  // Tags
  readonly tagInput: Locator
  readonly popularTags: Locator
  readonly tagChips: Locator
  
  // Recurrence
  readonly recurrenceToggle: Locator
  readonly recurrenceTypeDropdown: Locator
  
  // Actions
  readonly saveButton: Locator
  readonly cancelButton: Locator
  
  // Validation errors
  readonly titleError: Locator

  constructor(page: Page) {
    this.page = page
    
    // Dialog
    this.dialog = page.locator('.p-dialog, [role="dialog"]')
    this.dialogTitle = page.locator('.dialog-title, h3').filter({ hasText: /новая задача|new task|редактирование|edit task/i })
    this.closeButton = page.locator('button[aria-label="Close"], .p-dialog-header-close')
    
    // Form fields - use more specific selectors
    this.titleInput = page.locator('input[type="text"]').filter({ 
      has: page.locator('label').filter({ hasText: /название|title/i })
    }).or(
      page.locator('.p-inputtext').first()
    ).or(
      page.locator('input').filter({ hasNot: page.locator('[type="time"], [type="checkbox"]') }).first()
    )
    this.descriptionTextarea = page.locator('textarea').first()
    this.statusDropdown = page.locator('[role="combobox"]').first()
    this.priorityDropdown = page.locator('[role="combobox"]').nth(1)
    this.startDateCalendar = page.locator('input[type="text"]').filter({ 
      has: page.locator('label').filter({ hasText: /начало|start/i })
    }).or(
      page.locator('.p-calendar input').first()
    )
    this.dueDateCalendar = page.locator('input[type="text"]').filter({ 
      has: page.locator('label').filter({ hasText: /срок|due/i })
    }).or(
      page.locator('.p-calendar input').last()
    )
    
    // Quick date buttons - in day-chips section
    this.todayButton = page.locator('.day-chip, button').filter({ hasText: /сегодня|today/i }).first()
    this.tomorrowButton = page.locator('.day-chip, button').filter({ hasText: /завтра|tomorrow/i }).first()
    this.dayAfterButton = page.locator('.day-chip, button').filter({ hasText: /послезавтра|day after|day after tomorrow/i }).first()
    
    // Advanced date toggle
    this.advancedDateToggle = page.locator('button').filter({ hasText: /расширен|advanced/i })
    
    // Tags - AutoComplete component
    this.tagInput = page.locator('.p-autocomplete input, input[placeholder*="тег"], input[placeholder*="tag"]').last()
    this.popularTags = page.locator('.popular-tags, [class*="popular-tag"]')
    this.tagChips = page.locator('.p-chip, [class*="tag-chip"], [class*="chip"], .p-autocomplete-token')
    
    // Recurrence - RecurrenceSettings component (ToggleButton)
    this.recurrenceToggle = page.locator('.recurrence-field input[type="checkbox"]').or(
      page.locator('.recurrence-toggle input[type="checkbox"]')
    ).or(
      page.locator('.p-togglebutton input[type="checkbox"]')
    ).first()
    this.recurrenceTypeDropdown = page.locator('.recurrence-field [role="combobox"]').or(
      page.locator('.recurrence-options [role="combobox"]')
    ).first()
    
    // Actions
    this.saveButton = page.getByRole('button', { name: /сохранить|save/i })
    this.cancelButton = page.getByRole('button', { name: /отмена|cancel/i })
    
    // Validation
    this.titleError = page.locator('.p-error, small.p-error').first()
  }

  /**
   * Wait for dialog to be visible
   */
  async waitForDialog(): Promise<void> {
    await this.dialog.waitFor({ state: 'visible', timeout: 10000 })
    await this.page.waitForTimeout(500)
  }

  /**
   * Check if dialog is visible
   */
  async isVisible(): Promise<boolean> {
    return await this.dialog.isVisible().catch(() => false)
  }

  /**
   * Fill task title
   */
  async fillTitle(title: string): Promise<void> {
    // Try multiple selectors
    const selectors = [
      this.page.locator('input[type="text"]').filter({ has: this.page.locator('label').filter({ hasText: /название|title/i }) }),
      this.page.locator('.p-inputtext').first(),
      this.page.locator('input').filter({ hasNot: this.page.locator('[type="time"], [type="checkbox"]') }).first(),
      this.page.locator('input[autofocus]')
    ]
    
    let input: Locator | null = null
    for (const selector of selectors) {
      try {
        await selector.first().waitFor({ state: 'visible', timeout: 2000 })
        input = selector.first()
        break
      } catch {
        continue
      }
    }
    
    if (!input) {
      throw new Error('Title input not found')
    }
    
    await input.fill(title)
  }

  /**
   * Fill task description
   */
  async fillDescription(description: string): Promise<void> {
    await this.descriptionTextarea.waitFor({ state: 'visible', timeout: 5000 })
    await this.descriptionTextarea.fill(description)
  }

  /**
   * Select status
   */
  async selectStatus(status: 'pending' | 'in_progress' | 'completed'): Promise<void> {
    await this.statusDropdown.waitFor({ state: 'visible', timeout: 5000 })
    await this.statusDropdown.click()
    await this.page.waitForTimeout(300)
    
    const statusText = status === 'pending' ? /ожидает|pending/i :
                      status === 'in_progress' ? /в процессе|in progress/i :
                      /завершен|completed/i
    
    await this.page.getByRole('option', { name: statusText }).click()
  }

  /**
   * Select priority
   */
  async selectPriority(priority: 'low' | 'medium' | 'high' | 'urgent'): Promise<void> {
    await this.priorityDropdown.waitFor({ state: 'visible', timeout: 5000 })
    await this.priorityDropdown.click()
    await this.page.waitForTimeout(300)
    
    const priorityText = priority === 'low' ? /низкий|low/i :
                         priority === 'medium' ? /средний|medium/i :
                         priority === 'high' ? /высокий|high/i :
                         /срочный|urgent/i
    
    await this.page.getByRole('option', { name: priorityText }).click()
  }

  /**
   * Click quick date button
   */
  async clickQuickDate(day: 'today' | 'tomorrow' | 'dayAfter'): Promise<void> {
    // Try multiple selectors for day buttons
    const dayTexts = {
      'today': /сегодня|today/i,
      'tomorrow': /завтра|tomorrow/i,
      'dayAfter': /послезавтра|day after|day after tomorrow/i
    }
    
    const dayPattern = dayTexts[day]
    const dayButtons = this.page.locator('.day-chip, button').filter({ hasText: dayPattern })
    
    let button: Locator | null = null
    const buttonCount = await dayButtons.count()
    
    for (let i = 0; i < buttonCount; i++) {
      const btn = dayButtons.nth(i)
      const text = await btn.textContent().catch(() => '')
      if (dayPattern.test(text || '')) {
        button = btn
        break
      }
    }
    
    if (!button) {
      // Fallback to predefined selectors
      switch (day) {
        case 'today':
          button = this.todayButton
          break
        case 'tomorrow':
          button = this.tomorrowButton
          break
        case 'dayAfter':
          button = this.dayAfterButton
          break
      }
    }
    
    if (!button) {
      throw new Error(`Quick date button for "${day}" not found`)
    }
    
    await button.waitFor({ state: 'visible', timeout: 5000 })
    await button.scrollIntoViewIfNeeded()
    await button.click()
    await this.page.waitForTimeout(500)
  }

  /**
   * Toggle advanced date settings
   */
  async toggleAdvancedDate(): Promise<void> {
    await this.advancedDateToggle.waitFor({ state: 'visible', timeout: 5000 })
    await this.advancedDateToggle.click()
    await this.page.waitForTimeout(500)
  }

  /**
   * Add tag by clicking popular tag
   */
  async clickPopularTag(tagName: string): Promise<void> {
    const tag = this.popularTags.locator('button, .tag-item').filter({ hasText: new RegExp(tagName, 'i') }).first()
    await tag.waitFor({ state: 'visible', timeout: 5000 })
    await tag.click()
    await this.page.waitForTimeout(500)
  }

  /**
   * Add tag by typing
   */
  async addTagByTyping(tagName: string): Promise<void> {
    await this.tagInput.waitFor({ state: 'visible', timeout: 5000 })
    await this.tagInput.fill(tagName)
    await this.page.keyboard.press('Enter')
    await this.page.waitForTimeout(500)
  }

  /**
   * Remove tag by clicking X
   */
  async removeTag(tagName: string): Promise<void> {
    const tagChip = this.tagChips.filter({ hasText: new RegExp(tagName, 'i') }).first()
    const removeButton = tagChip.locator('i.pi-times, .p-chip-remove-icon, button')
    await removeButton.click()
    await this.page.waitForTimeout(500)
  }

  /**
   * Enable recurrence
   */
  async enableRecurrence(): Promise<void> {
    // Try multiple selectors for recurrence toggle
    const toggleSelectors = [
      this.page.locator('.recurrence-field input[type="checkbox"]'),
      this.page.locator('.recurrence-toggle input[type="checkbox"]'),
      this.page.locator('.p-togglebutton input[type="checkbox"]'),
      this.page.locator('input[type="checkbox"]').filter({ 
        has: this.page.locator('label, span').filter({ hasText: /повторяющ|repeat/i })
      })
    ]
    
    let toggle: Locator | null = null
    for (const selector of toggleSelectors) {
      try {
        await selector.first().waitFor({ state: 'visible', timeout: 2000 })
        toggle = selector.first()
        break
      } catch {
        continue
      }
    }
    
    if (!toggle) {
      throw new Error('Recurrence toggle not found')
    }
    
    if (!(await toggle.isChecked())) {
      await toggle.click()
      await this.page.waitForTimeout(1000) // Wait for recurrence options to appear
    }
  }

  /**
   * Select recurrence type
   */
  async selectRecurrenceType(type: 'daily' | 'weekly' | 'monthly' | 'yearly' | 'custom'): Promise<void> {
    // Try multiple selectors for recurrence type dropdown
    const dropdownSelectors = [
      this.page.locator('.recurrence-field [role="combobox"]'),
      this.page.locator('.recurrence-options [role="combobox"]'),
      this.page.locator('.recurrence-settings [role="combobox"]')
    ]
    
    let dropdown: Locator | null = null
    for (const selector of dropdownSelectors) {
      try {
        await selector.first().waitFor({ state: 'visible', timeout: 2000 })
        dropdown = selector.first()
        break
      } catch {
        continue
      }
    }
    
    if (!dropdown) {
      throw new Error('Recurrence type dropdown not found')
    }
    
    await dropdown.click()
    await this.page.waitForTimeout(500)
    
    const typeText = type === 'daily' ? /ежедневно|daily/i :
                     type === 'weekly' ? /еженедельно|weekly/i :
                     type === 'monthly' ? /ежемесячно|monthly/i :
                     type === 'yearly' ? /ежегодно|yearly/i :
                     /настраиваем|custom/i
    
    // Try to find option
    const option = this.page.getByRole('option', { name: typeText })
    await option.waitFor({ state: 'visible', timeout: 3000 })
    await option.click()
    await this.page.waitForTimeout(500)
  }

  /**
   * Click save button
   */
  async save(): Promise<void> {
    await this.saveButton.waitFor({ state: 'visible', timeout: 5000 })
    await this.saveButton.click()
  }

  /**
   * Click cancel button
   */
  async cancel(): Promise<void> {
    await this.cancelButton.waitFor({ state: 'visible', timeout: 5000 })
    await this.cancelButton.click()
  }

  /**
   * Check if title error is visible
   */
  async hasTitleError(): Promise<boolean> {
    return await this.titleError.isVisible().catch(() => false)
  }

  /**
   * Get title error text
   */
  async getTitleErrorText(): Promise<string | null> {
    if (await this.hasTitleError()) {
      return await this.titleError.textContent()
    }
    return null
  }

  /**
   * Check if save button is disabled
   */
  async isSaveDisabled(): Promise<boolean> {
    return await this.saveButton.isDisabled().catch(() => false)
  }

  /**
   * Get count of selected tags
   */
  async getTagCount(): Promise<number> {
    return await this.tagChips.count()
  }

  /**
   * Check if tag is selected
   */
  async hasTag(tagName: string): Promise<boolean> {
    const tag = this.tagChips.filter({ hasText: new RegExp(tagName, 'i') }).first()
    return await tag.isVisible().catch(() => false)
  }
}

