import { useI18n } from 'vue-i18n'
import { useConfirm } from 'primevue/useconfirm'
import { useTaskStore } from '@/stores/task.store'
import { useToast } from '@/composables/useToast'
import type { Task } from '@/types/task.types'
import { TaskStatus } from '@/types/task.types'

export function useTaskCompletion() {
  const { t } = useI18n()
  const confirm = useConfirm()
  const taskStore = useTaskStore()
  const { showSuccess, showError } = useToast()

  /**
   * Count uncompleted subtasks recursively
   */
  function countUncompletedSubtasks(task: Task): number {
    let count = 0
    
    if (task.subtasks && Array.isArray(task.subtasks)) {
      for (const subtask of task.subtasks) {
        if (!subtask.isCompleted) {
          count++
        }
        // Count nested subtasks recursively
        count += countUncompletedSubtasks(subtask)
      }
    }
    
    return count
  }

  /**
   * Complete task and all its subtasks recursively
   */
  async function completeTaskWithSubtasks(taskId: number): Promise<Task> {
    try {
      // First complete the main task
      await taskStore.toggleTaskCompletion(taskId)
      
      // Get the updated task with subtasks
      const task = await taskStore.fetchTask(taskId)
      
      // Complete all subtasks recursively
      if (task.subtasks && Array.isArray(task.subtasks)) {
        await completeSubtasksRecursively(task.subtasks)
      }
      
      const updatedTask = await taskStore.fetchTask(taskId)
      // Note: Success notification is already shown in toggleTaskCompletion before calling this
      return updatedTask
    } catch (error: any) {
      showError(error.message || t('errors.unknown_error'))
      throw error
    }
  }

  /**
   * Complete subtasks recursively
   */
  async function completeSubtasksRecursively(subtasks: Task[]): Promise<void> {
    for (const subtask of subtasks) {
      if (!subtask.isCompleted) {
        // Complete this subtask
        await taskStore.updateTask(subtask.id, { status: TaskStatus.COMPLETED })
        
        // Complete its children recursively
        if (subtask.subtasks && Array.isArray(subtask.subtasks)) {
          await completeSubtasksRecursively(subtask.subtasks)
        }
      }
    }
  }

  /**
   * Toggle task completion with confirmation if there are uncompleted subtasks
   */
  async function toggleTaskCompletion(task: Task | number, onSuccess?: (updatedTask: Task) => void): Promise<void> {
    // Get task if only ID was provided
    let taskData: Task
    if (typeof task === 'number') {
      taskData = await taskStore.fetchTask(task)
    } else {
      taskData = task
    }

    // If task is already completed, just uncomplete it (no confirmation needed)
    if (taskData.isCompleted) {
      // Show success notification immediately (before API call)
      showSuccess(t('tasks.task_reopened'))
      
      try {
        await taskStore.toggleTaskCompletion(taskData.id)
        const updatedTask = findTaskInStore(taskData.id) ?? {
          ...taskData,
          isCompleted: false
        }
        if (onSuccess) onSuccess(updatedTask)
      } catch (error: any) {
        // Show error notification (this will replace the success notification)
        showError(error.message || t('errors.unknown_error'))
      }
      return
    }

    // Count uncompleted subtasks
    const uncompletedCount = countUncompletedSubtasks(taskData)
    
    // If there are uncompleted subtasks, show confirmation dialog
    if (uncompletedCount > 0) {
      confirm.require({
        message: t('tasks.complete_with_subtasks_message', { count: uncompletedCount }),
        header: t('tasks.complete_with_subtasks_title'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-success',
        acceptLabel: t('common.yes'),
        rejectLabel: t('common.no'),
        accept: async () => {
          // Show success notification immediately (before API calls)
          showSuccess(t('tasks.task_completed'))
          
          try {
            const updatedTask = await completeTaskWithSubtasks(taskData.id)
            if (onSuccess) onSuccess(updatedTask)
          } catch (error: any) {
            // Show error notification (this will replace the success notification)
            showError(error.message || t('errors.unknown_error'))
          }
        }
      })
    } else {
      // No subtasks or all subtasks are completed, just complete the task
      // Show success notification immediately (before API call)
      showSuccess(t('tasks.task_completed'))
      
      try {
        await taskStore.toggleTaskCompletion(taskData.id)
        const updatedTask = findTaskInStore(taskData.id) ?? {
          ...taskData,
          isCompleted: true
        }
        if (onSuccess) onSuccess(updatedTask)
      } catch (error: any) {
        // Show error notification (this will replace the success notification)
        showError(error.message || t('errors.unknown_error'))
      }
    }
  }

  /**
   * Find task in any store location (main tasks, overdue, unscheduled)
   */
  function findTaskInStore(taskId: number): Task | undefined {
    return taskStore.tasks.find(t => t.id === taskId) 
      ?? taskStore.overdueTasksPaginated.tasks.find(t => t.id === taskId)
      ?? taskStore.unscheduledTasksPaginated.tasks.find(t => t.id === taskId)
  }

  /**
   * Mark task as completed from checkbox (used in lists)
   */
  async function handleCheckboxChange(task: Task, checked: boolean, onSuccess?: (updatedTask: Task) => void): Promise<void> {
    // If unchecking, just toggle without confirmation
    if (!checked) {
      // Show success notification immediately (before API call)
      showSuccess(t('tasks.task_reopened'))
      
      try {
        await taskStore.toggleTaskCompletion(task.id)
        const updatedTask = findTaskInStore(task.id) ?? {
          ...task,
          isCompleted: false
        }
        if (onSuccess) onSuccess(updatedTask)
      } catch (error: any) {
        // Show error notification (this will replace the success notification)
        showError(error.message || t('errors.unknown_error'))
      }
      return
    }

    // If checking, use the confirmation flow
    await toggleTaskCompletion(task, onSuccess)
  }

  return {
    countUncompletedSubtasks,
    toggleTaskCompletion,
    handleCheckboxChange,
    completeTaskWithSubtasks
  }
}
