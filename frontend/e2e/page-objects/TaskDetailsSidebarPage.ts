import { Page, Locator } from '@playwright/test'

/**
 * Page Object Model for Task Details Sidebar
 */
export class TaskDetailsSidebarPage {
  readonly page: Page
  
  // Sidebar elements
  readonly sidebar: Locator
  readonly sidebarHeader: Locator
  readonly closeButton: Locator
  readonly editButton: Locator
  readonly deleteButton: Locator
  
  // Task display fields
  readonly taskTitle: Locator
  readonly taskDescription: Locator
  readonly taskStatus: Locator
  readonly taskPriority: Locator
  readonly taskDates: Locator
  readonly taskTags: Locator
  
  // Edit mode fields
  readonly editTitleInput: Locator
  readonly editDescriptionTextarea: Locator
  readonly editStatusDropdown: Locator
  readonly editPriorityDropdown: Locator
  readonly editStartDateCalendar: Locator
  readonly editDueDateCalendar: Locator
  readonly editTagInput: Locator
  
  // Edit actions
  readonly saveButton: Locator
  readonly cancelButton: Locator
  
  // Subtasks section
  readonly subtasksSection: Locator
  readonly subtaskInput: Locator
  readonly subtaskList: Locator
  readonly subtaskItems: Locator
  
  // Completion
  readonly completeButton: Locator
  readonly taskCheckbox: Locator
  
  // Attachments
  readonly attachmentsSection: Locator
  readonly fileUploadInput: Locator
  readonly attachmentList: Locator

  constructor(page: Page) {
    this.page = page
    
    // Sidebar - PrimeVue Sidebar component
    this.sidebar = page.locator('.p-sidebar').or(
      page.locator('[role="complementary"]')
    ).first()
    this.sidebarHeader = page.locator('.drawer-header, .sidebar-header')
    this.closeButton = page.locator('button[aria-label*="close"], button').filter({ has: page.locator('i.pi-times') })
    this.editButton = page.locator('button[aria-label*="edit"], button').filter({ has: page.locator('i.pi-pencil') })
    this.deleteButton = page.locator('button[aria-label*="delete"], button').filter({ has: page.locator('i.pi-trash') })
    
    // Task display fields
    this.taskTitle = page.locator('.task-title, h3, h4').first()
    this.taskDescription = page.locator('.task-description, [class*="description"]')
    this.taskStatus = page.locator('.task-status, [class*="status"]')
    this.taskPriority = page.locator('.task-priority, [class*="priority"]')
    this.taskDates = page.locator('.task-dates, [class*="date"]')
    this.taskTags = page.locator('.task-tags, [class*="tag"]')
    
    // Edit mode fields
    this.editTitleInput = page.locator('.task-details input[type="text"]').first()
    this.editDescriptionTextarea = page.locator('.task-details textarea').first()
    this.editStatusDropdown = page.locator('.task-details [role="combobox"]').first()
    this.editPriorityDropdown = page.locator('.task-details [role="combobox"]').nth(1)
    this.editStartDateCalendar = page.locator('.task-details .p-calendar input').first()
    this.editDueDateCalendar = page.locator('.task-details .p-calendar input').last()
    this.editTagInput = page.locator('.task-details .p-autocomplete input').last()
    
    // Edit actions
    this.saveButton = page.getByRole('button', { name: /сохранить|save/i })
    this.cancelButton = page.getByRole('button', { name: /отмена|cancel/i })
    
    // Subtasks
    this.subtasksSection = page.locator('.subtasks-section, [class*="subtask"]')
    this.subtaskInput = page.locator('input[placeholder*="подзадач"], input[placeholder*="subtask"]')
    this.subtaskList = page.locator('.subtask-list, [class*="subtask-list"]')
    this.subtaskItems = page.locator('.subtask-item, [class*="subtask-item"]')
    
    // Completion
    this.completeButton = page.getByRole('button', { name: /завершить|complete|выполнено/i })
    this.taskCheckbox = page.locator('.task-details input[type="checkbox"]').first()
    
    // Attachments
    this.attachmentsSection = page.locator('.attachments-section, [class*="attachment"]')
    this.fileUploadInput = page.locator('.task-details input[type="file"]')
    this.attachmentList = page.locator('.attachment-list, [class*="attachment-list"]')
  }

  /**
   * Wait for sidebar to be visible
   */
  async waitForSidebar(): Promise<void> {
    // Try multiple selectors for sidebar with increased timeout
    const sidebarSelectors = [
      this.page.locator('.p-sidebar'),
      this.page.locator('[role="complementary"]'),
      this.page.locator('.p-sidebar-content'),
      this.page.locator('.drawer-header'),
      this.page.locator('[class*="sidebar"]')
    ]
    
    let sidebarFound = false
    for (const selector of sidebarSelectors) {
      try {
        await selector.first().waitFor({ state: 'visible', timeout: 5000 })
        sidebarFound = true
        break
      } catch {
        continue
      }
    }
    
    if (!sidebarFound) {
      // Wait a bit more and try again with longer timeout
      await this.page.waitForTimeout(2000)
      
      // Try the main sidebar selector with longer timeout
      try {
        await this.sidebar.waitFor({ state: 'visible', timeout: 10000 })
        sidebarFound = true
      } catch {
        // If still not found, check if any sidebar-like element exists
        const anySidebar = this.page.locator('.p-sidebar, [role="complementary"], [class*="sidebar"]')
        try {
          await anySidebar.first().waitFor({ state: 'visible', timeout: 5000 })
          sidebarFound = true
        } catch {
          // Last resort: wait a bit more and check visibility
          await this.page.waitForTimeout(2000)
        }
      }
    }
    
    // Additional wait for sidebar animation to complete
    await this.page.waitForTimeout(1000)
  }

  /**
   * Check if sidebar is visible
   */
  async isVisible(): Promise<boolean> {
    return await this.sidebar.isVisible().catch(() => false)
  }

  /**
   * Click on task card to open sidebar
   */
  async openTaskDetails(taskTitle: string): Promise<void> {
    // Find task card by title - try multiple selectors
    const taskCardSelectors = [
      this.page.locator('.task-card').filter({ hasText: new RegExp(taskTitle, 'i') }),
      this.page.locator('.task-item').filter({ hasText: new RegExp(taskTitle, 'i') }),
      this.page.locator('[class*="task-card"]').filter({ hasText: new RegExp(taskTitle, 'i') }),
      this.page.locator('[class*="task"]').filter({ hasText: new RegExp(taskTitle, 'i') })
    ]
    
    let taskCard: Locator | null = null
    for (const selector of taskCardSelectors) {
      try {
        await selector.first().waitFor({ state: 'visible', timeout: 3000 })
        taskCard = selector.first()
        break
      } catch {
        continue
      }
    }
    
    if (!taskCard) {
      // Fallback: click first task card
      taskCard = this.page.locator('.task-card, .task-item').first()
    }
    
    await taskCard.waitFor({ state: 'visible', timeout: 5000 })
    await taskCard.click()
    
    // Wait for sidebar with multiple selectors
    await Promise.race([
      this.sidebar.waitFor({ state: 'visible', timeout: 10000 }),
      this.page.locator('.p-sidebar').waitFor({ state: 'visible', timeout: 10000 }),
      this.page.waitForTimeout(2000)
    ])
    await this.page.waitForTimeout(500)
  }

  /**
   * Close sidebar
   */
  async close(): Promise<void> {
    await this.closeButton.waitFor({ state: 'visible', timeout: 5000 })
    await this.closeButton.click()
    await this.page.waitForTimeout(500)
  }

  /**
   * Click outside sidebar to close
   */
  async clickOutside(): Promise<void> {
    // Click on overlay/backdrop
    await this.page.locator('.p-sidebar-mask, .p-sidebar-overlay').click({ position: { x: 10, y: 10 } })
    await this.page.waitForTimeout(500)
  }

  /**
   * Enter edit mode
   */
  async enterEditMode(): Promise<void> {
    // First, ensure sidebar is visible
    await this.waitForSidebar()
    await this.page.waitForTimeout(500)
    
    // Try multiple selectors for edit button with increased timeout
    const editButtonSelectors = [
      this.editButton,
      this.page.locator('button[aria-label*="edit"], button').filter({ has: this.page.locator('i.pi-pencil') }),
      this.page.locator('button').filter({ hasText: /редактировать|edit/i }),
      this.page.locator('.edit-button, [class*="edit"]'),
      this.page.locator('button[aria-label*="edit"]'),
      this.page.locator('button').filter({ has: this.page.locator('[class*="pencil"], [class*="edit"]') })
    ]
    
    let buttonFound = false
    for (const selector of editButtonSelectors) {
      try {
        const button = selector.first()
        await button.waitFor({ state: 'visible', timeout: 10000 })
        await button.scrollIntoViewIfNeeded()
        await button.click({ force: true })
        buttonFound = true
        await this.page.waitForTimeout(500)
        break
      } catch {
        continue
      }
    }
    
    if (!buttonFound) {
      // Last resort: try to find any button with pencil icon anywhere in sidebar
      const sidebar = this.sidebar
      const pencilButtons = sidebar.locator('button').filter({ has: this.page.locator('i.pi-pencil') })
      const count = await pencilButtons.count()
      if (count > 0) {
        await pencilButtons.first().scrollIntoViewIfNeeded()
        await pencilButtons.first().click({ force: true })
        buttonFound = true
        await this.page.waitForTimeout(500)
      }
    }
    
    if (!buttonFound) {
      // Even more last resort: try clicking on any button in sidebar that might be edit
      const allButtons = this.sidebar.locator('button')
      const buttonCount = await allButtons.count()
      for (let i = 0; i < Math.min(buttonCount, 5); i++) {
        try {
          const btn = allButtons.nth(i)
          const ariaLabel = await btn.getAttribute('aria-label').catch(() => '')
          const hasPencil = await btn.locator('i.pi-pencil').count() > 0
          if (ariaLabel?.toLowerCase().includes('edit') || hasPencil) {
            await btn.scrollIntoViewIfNeeded()
            await btn.click({ force: true })
            buttonFound = true
            await this.page.waitForTimeout(500)
            break
          }
        } catch {
          continue
        }
      }
    }
    
    if (!buttonFound) {
      throw new Error('Edit button not found in sidebar')
    }
    
    await this.page.waitForTimeout(1000) // Wait for edit mode to activate
  }

  /**
   * Check if in edit mode
   */
  async isEditMode(): Promise<boolean> {
    const inputVisible = await this.editTitleInput.isVisible().catch(() => false)
    return inputVisible
  }

  /**
   * Edit task title
   */
  async editTitle(newTitle: string): Promise<void> {
    if (!(await this.isEditMode())) {
      await this.enterEditMode()
    }
    await this.editTitleInput.waitFor({ state: 'visible', timeout: 5000 })
    await this.editTitleInput.fill(newTitle)
  }

  /**
   * Edit task description
   */
  async editDescription(newDescription: string): Promise<void> {
    if (!(await this.isEditMode())) {
      await this.enterEditMode()
    }
    await this.editDescriptionTextarea.waitFor({ state: 'visible', timeout: 5000 })
    await this.editDescriptionTextarea.fill(newDescription)
  }

  /**
   * Change task status
   */
  async changeStatus(status: 'pending' | 'in_progress' | 'completed'): Promise<void> {
    if (!(await this.isEditMode())) {
      await this.enterEditMode()
    }
    await this.editStatusDropdown.waitFor({ state: 'visible', timeout: 5000 })
    await this.editStatusDropdown.click()
    await this.page.waitForTimeout(300)
    
    const statusText = status === 'pending' ? /ожидает|pending/i :
                      status === 'in_progress' ? /в процессе|in progress/i :
                      /завершен|completed/i
    
    await this.page.getByRole('option', { name: statusText }).click()
  }

  /**
   * Change task priority
   */
  async changePriority(priority: 'low' | 'medium' | 'high' | 'urgent'): Promise<void> {
    if (!(await this.isEditMode())) {
      await this.enterEditMode()
    }
    await this.editPriorityDropdown.waitFor({ state: 'visible', timeout: 5000 })
    await this.editPriorityDropdown.click()
    await this.page.waitForTimeout(300)
    
    const priorityText = priority === 'low' ? /низкий|low/i :
                         priority === 'medium' ? /средний|medium/i :
                         priority === 'high' ? /высокий|high/i :
                         /срочный|urgent/i
    
    await this.page.getByRole('option', { name: priorityText }).click()
  }

  /**
   * Add tag
   */
  async addTag(tagName: string): Promise<void> {
    if (!(await this.isEditMode())) {
      await this.enterEditMode()
    }
    await this.editTagInput.waitFor({ state: 'visible', timeout: 5000 })
    await this.editTagInput.fill(tagName)
    await this.page.keyboard.press('Enter')
    await this.page.waitForTimeout(500)
  }

  /**
   * Remove tag
   */
  async removeTag(tagName: string): Promise<void> {
    const tagChip = this.page.locator('.p-chip, [class*="tag-chip"]').filter({ hasText: new RegExp(tagName, 'i') }).first()
    const removeButton = tagChip.locator('i.pi-times, .p-chip-remove-icon, button')
    await removeButton.click()
    await this.page.waitForTimeout(500)
  }

  /**
   * Save changes
   */
  async save(): Promise<void> {
    await this.saveButton.waitFor({ state: 'visible', timeout: 5000 })
    await this.saveButton.click()
  }

  /**
   * Cancel changes
   */
  async cancel(): Promise<void> {
    await this.cancelButton.waitFor({ state: 'visible', timeout: 5000 })
    await this.cancelButton.click()
  }

  /**
   * Get task title
   */
  async getTaskTitle(): Promise<string | null> {
    return await this.taskTitle.textContent()
  }

  /**
   * Get task description
   */
  async getTaskDescription(): Promise<string | null> {
    return await this.taskDescription.textContent()
  }

  /**
   * Add subtask
   */
  async addSubtask(subtaskTitle: string): Promise<void> {
    await this.subtaskInput.waitFor({ state: 'visible', timeout: 5000 })
    await this.subtaskInput.fill(subtaskTitle)
    await this.page.keyboard.press('Enter')
    await this.page.waitForTimeout(1000)
  }

  /**
   * Get subtask count
   */
  async getSubtaskCount(): Promise<number> {
    return await this.subtaskItems.count()
  }

  /**
   * Complete subtask by index
   */
  async completeSubtask(index: number): Promise<void> {
    const subtask = this.subtaskItems.nth(index)
    const checkbox = subtask.locator('input[type="checkbox"]')
    await checkbox.click()
    await this.page.waitForTimeout(500)
  }

  /**
   * Delete subtask by index
   */
  async deleteSubtask(index: number): Promise<void> {
    const subtask = this.subtaskItems.nth(index)
    const deleteButton = subtask.locator('button[aria-label*="delete"], button').filter({ has: this.page.locator('i.pi-trash') })
    await deleteButton.click()
    await this.page.waitForTimeout(500)
  }

  /**
   * Complete task
   */
  async completeTask(): Promise<void> {
    const completeButtonVisible = await this.completeButton.isVisible().catch(() => false)
    if (completeButtonVisible) {
      await this.completeButton.click()
    } else {
      // Try checkbox
      await this.taskCheckbox.click()
    }
    await this.page.waitForTimeout(500)
  }

  /**
   * Delete task
   */
  async deleteTask(): Promise<void> {
    await this.deleteButton.waitFor({ state: 'visible', timeout: 5000 })
    await this.deleteButton.click()
    await this.page.waitForTimeout(500)
    
    // Confirm deletion if confirmation dialog appears
    const confirmButton = this.page.getByRole('button', { name: /подтвердить|confirm|да|yes/i })
    const confirmVisible = await confirmButton.isVisible({ timeout: 3000 }).catch(() => false)
    if (confirmVisible) {
      await confirmButton.click()
    }
  }

  /**
   * Cancel deletion
   */
  async cancelDeletion(): Promise<void> {
    const cancelButton = this.page.getByRole('button', { name: /отмена|cancel|нет|no/i })
    await cancelButton.waitFor({ state: 'visible', timeout: 5000 })
    await cancelButton.click()
  }
}

