import { apiClient } from './api.service'
import type {
  UserProfile,
  UpdateProfileRequest,
  UpdatePasswordRequest,
  UpdateNotificationsRequest
} from '@/types/profile.types'

class ProfileService {
  private readonly BASE_URL = '/api/users/profile'

  async getProfile(): Promise<UserProfile> {
    const response = await apiClient.get<UserProfile>(this.BASE_URL)
    return response.data
  }

  async updateProfile(data: UpdateProfileRequest): Promise<UserProfile> {
    const response = await apiClient.patch<UserProfile>(this.BASE_URL, data)
    return response.data
  }

  async updatePassword(data: UpdatePasswordRequest): Promise<void> {
    await apiClient.post(`${this.BASE_URL}/password`, data)
  }

  async updateNotifications(data: UpdateNotificationsRequest): Promise<UserProfile> {
    const response = await apiClient.patch<UserProfile>(`${this.BASE_URL}/notifications`, data)
    return response.data
  }
}

export const profileService = new ProfileService()
