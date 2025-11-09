export interface UserProfile {
  id: number
  email: string
  name: string | null
  avatar: string | null
  theme: string
  language: string
  timezone: string
  notifications: NotificationSettings
  hasPassword: boolean
  isGoogleAuth: boolean
  createdAt: string
  updatedAt: string
}

export interface NotificationSettings {
  email: boolean
  push: boolean
  taskReminders: boolean
  taskAssignments: boolean
  taskCompletion: boolean
  weeklyDigest: boolean
}

export interface UpdateProfileRequest {
  name?: string
  language?: string
  timezone?: string
}

export interface UpdatePasswordRequest {
  currentPassword?: string  // Optional if user doesn't have password yet
  newPassword: string
  confirmPassword: string
}

export interface UpdateNotificationsRequest {
  notifications: NotificationSettings
}
