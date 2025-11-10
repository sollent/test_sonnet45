import { apiClient } from '@/services/api.service'
import type { TaskAttachment } from '@/types/task.types'

const API_ENDPOINTS = {
  ATTACHMENTS: (taskId: number) => `/api/tasks/${taskId}/attachments`
}

class AttachmentService {
  /**
   * Upload file to task
   */
  async uploadFile(taskId: number, file: File): Promise<TaskAttachment> {
    const formData = new FormData()
    formData.append('file', file)

    const { data } = await apiClient.post<TaskAttachment>(
      API_ENDPOINTS.ATTACHMENTS(taskId),
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
   * Delete attachment
   */
  async deleteAttachment(taskId: number, attachmentId: number): Promise<void> {
    await apiClient.delete(`${API_ENDPOINTS.ATTACHMENTS(taskId)}/${attachmentId}`)
  }

  /**
   * Get task attachments
   */
  async getAttachments(taskId: number): Promise<TaskAttachment[]> {
    const { data } = await apiClient.get<TaskAttachment[]>(
      API_ENDPOINTS.ATTACHMENTS(taskId)
    )

    return data
  }

  /**
   * Get file URL
   */
  getFileUrl(filePath: string): string {
    return `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000'}${filePath}`
  }

  /**
   * Check if file is image
   */
  isImage(mimeType: string): boolean {
    return mimeType.startsWith('image/')
  }

  /**
   * Check if file is document
   */
  isDocument(mimeType: string): boolean {
    const documentTypes = [
      'application/pdf',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ]
    return documentTypes.includes(mimeType)
  }
}

export default new AttachmentService()

