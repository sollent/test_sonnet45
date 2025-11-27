import { apiClient } from '@/services/api.service'
import type { TaskAttachment } from '@/types/task.types'

const API_ENDPOINTS = {
  MEDIA: '/api/media'
}

class MediaService {
  /**
   * Upload audio blob (for voice recording)
   */
  async uploadAudio(audioBlob: Blob, filename: string = 'voice-command.webm'): Promise<TaskAttachment> {
    const formData = new FormData()
    formData.append('file', audioBlob, filename)

    const { data } = await apiClient.post<TaskAttachment>(
      API_ENDPOINTS.MEDIA,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }
    )

    return data
  }

  /**
   * Upload file and get MediaObject ID
   */
  async uploadFile(file: File): Promise<TaskAttachment> {
    const formData = new FormData()
    formData.append('file', file)

    const { data } = await apiClient.post<TaskAttachment>(
      API_ENDPOINTS.MEDIA,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }
    )

    return data
  }

  /**
   * Delete media object
   */
  async deleteMedia(mediaId: number): Promise<void> {
    await apiClient.delete(`${API_ENDPOINTS.MEDIA}/${mediaId}`)
  }

  /**
   * Get file URL
   */
  getFileUrl(filePath: string): string {
    // Remove /public prefix if present
    const cleanPath = filePath.replace('/public', '')
    return `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8089'}${cleanPath}`
  }
}

export default new MediaService()

