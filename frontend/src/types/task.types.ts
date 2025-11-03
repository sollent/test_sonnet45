/**
 * Task Management Types
 */

export enum TaskStatus {
  PENDING = 'pending',
  IN_PROGRESS = 'in_progress',
  COMPLETED = 'completed',
  CANCELLED = 'cancelled'
}

export enum TaskPriority {
  LOW = 'low',
  MEDIUM = 'medium',
  HIGH = 'high',
  URGENT = 'urgent'
}

export interface Tag {
  id: number
  name: string
  color: string
  icon?: string | null
  usageCount?: number
  createdAt?: string
  updatedAt?: string
}

export interface TaskAttachment {
  id: number
  fileName: string
  originalName: string
  mimeType: string
  fileSize: number
  fileSizeHuman: string
  fileType: 'image' | 'document' | 'video' | 'other'
  filePath: string
  uploadedAt: string
}

export type RecurrenceType = 'daily' | 'weekly' | 'monthly' | 'yearly' | 'custom'

export interface RecurrenceRule {
  id?: number
  recurrenceType: RecurrenceType
  interval?: number // For custom type - every N days
  daysOfWeek?: number[] // For weekly [1,2,3,4,5] = Mon-Fri
  dayOfMonth?: number // For monthly
  monthOfYear?: number // For yearly
  endDate?: string | null
  maxOccurrences?: number | null
  timeOfDay?: string | null // HH:mm format
  currentOccurrences?: number
  nextOccurrenceDate?: string
  isActive?: boolean
  previewDates?: string[]
}

export interface RecurrenceSettings {
  enabled: boolean
  rule?: RecurrenceRule
}

export interface Task {
  id: number
  title: string
  description: string | null
  status: TaskStatus
  priority: TaskPriority
  startDate: string | null
  dueDate: string | null
  completedAt: string | null
  parentTaskId: number | null
  subtasks?: Task[]
  tags: Tag[]
  attachments?: TaskAttachment[]
  sortOrder?: number
  isArchived?: boolean
  isCompleted: boolean
  isOverdue: boolean
  completionProgress?: number
  createdAt?: string | null
  updatedAt?: string | null
  subtaskCount?: number
  completedSubtaskCount?: number
  hasNestedSubtasks?: boolean
  isRecurringTemplate?: boolean
  recurrenceRule?: RecurrenceRule | null
  // Translated labels from backend
  priorityLabel?: string
  statusLabel?: string
}

export interface CreateTaskRequest {
  title: string
  description?: string | null
  status?: TaskStatus
  priority?: TaskPriority
  startDate?: string | null
  dueDate?: string | null
  parentTaskId?: number | null
  tags?: string[]
  mediaIds?: number[]
  sortOrder?: number
  isArchived?: boolean
  recurrence?: RecurrenceRule | null
}

export interface UpdateTaskRequest {
  title?: string
  description?: string | null
  status?: TaskStatus
  priority?: TaskPriority
  startDate?: string | null
  dueDate?: string | null
  tags?: string[]
  mediaIds?: number[]
  sortOrder?: number
  isArchived?: boolean
}

export interface TaskStatistics {
  total: number
  pending: number
  in_progress: number
  completed: number
  cancelled: number
  overdue: number
}

export interface TaskFilters {
  status?: TaskStatus
  archived?: boolean
  tag?: number
  search?: string
  view?: 'today' | 'overdue' | 'upcoming' | 'all' | 'unscheduled'
  tags?: number[]
  completed?: boolean
  dateFrom?: string
  dateTo?: string
  priorities?: TaskPriority[]
  statuses?: TaskStatus[]
}

export interface TaskFiltersState {
  tags: number[]
  completed: boolean | null
  dateFrom: string | null
  dateTo: string | null
  priorities: TaskPriority[]
  statuses: TaskStatus[]
}

export interface CreateTagRequest {
  name: string
  color?: string
  icon?: string | null
}

export interface UpdateTagRequest {
  name?: string
  color?: string
  icon?: string | null
}

// Helper types for UI
export interface TaskGroup {
  title: string
  date: string | null
  tasks: Task[]
}

export interface TaskPriorityConfig {
  label: string
  color: string
  icon: string
  weight: number
}

export interface TaskStatusConfig {
  label: string
  color: string
  icon: string
}

// Constants
export const TASK_PRIORITY_CONFIG: Record<TaskPriority, TaskPriorityConfig> = {
  [TaskPriority.LOW]: {
    label: 'Low',
    color: '#6B7280',
    icon: 'pi pi-chevron-down',
    weight: 1
  },
  [TaskPriority.MEDIUM]: {
    label: 'Medium',
    color: '#3B82F6',
    icon: 'pi pi-minus',
    weight: 2
  },
  [TaskPriority.HIGH]: {
    label: 'High',
    color: '#F59E0B',
    icon: 'pi pi-chevron-up',
    weight: 3
  },
  [TaskPriority.URGENT]: {
    label: 'Urgent',
    color: '#EF4444',
    icon: 'pi pi-exclamation-triangle',
    weight: 4
  }
}

export const TASK_STATUS_CONFIG: Record<TaskStatus, TaskStatusConfig> = {
  [TaskStatus.PENDING]: {
    label: 'Pending',
    color: '#6B7280',
    icon: 'pi pi-clock'
  },
  [TaskStatus.IN_PROGRESS]: {
    label: 'In Progress',
    color: '#3B82F6',
    icon: 'pi pi-play'
  },
  [TaskStatus.COMPLETED]: {
    label: 'Completed',
    color: '#10B981',
    icon: 'pi pi-check'
  },
  [TaskStatus.CANCELLED]: {
    label: 'Cancelled',
    color: '#EF4444',
    icon: 'pi pi-times'
  }
}

