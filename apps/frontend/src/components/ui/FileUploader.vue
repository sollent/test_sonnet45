<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from 'primevue/usetoast'
import type { TaskAttachment } from '@/types/task.types'
import FileUpload from 'primevue/fileupload'
import Button from 'primevue/button'
import ProgressBar from 'primevue/progressbar'
import Image from 'primevue/image'

const { t } = useI18n()
const toast = useToast()

const props = defineProps<{
  taskId: number
  attachments?: TaskAttachment[]
  maxFiles?: number
  maxFileSize?: number
}>()

const emit = defineEmits<{
  (e: 'upload', file: File): void
  (e: 'delete', attachmentId: number): void
  (e: 'view', attachment: TaskAttachment): void
}>()

const fileUploadRef = ref()
const isUploading = ref(false)
const uploadProgress = ref(0)

const maxSize = props.maxFileSize || 10 * 1024 * 1024 // 10MB
const maxCount = props.maxFiles || 10

const images = computed(() => 
  props.attachments?.filter(a => a.fileType === 'image') || []
)

const documents = computed(() => 
  props.attachments?.filter(a => a.fileType === 'document') || []
)

const otherFiles = computed(() => 
  props.attachments?.filter(a => !['image', 'document'].includes(a.fileType)) || []
)

function handleUpload(event: any) {
  const files = event.files
  
  if (!files || files.length === 0) {
    return
  }

  isUploading.value = true
  uploadProgress.value = 0

  files.forEach((file: File, index: number) => {
    setTimeout(() => {
      emit('upload', file)
      uploadProgress.value = ((index + 1) / files.length) * 100
      
      if (index === files.length - 1) {
        isUploading.value = false
        fileUploadRef.value?.clear()
      }
    }, index * 100)
  })
}

function handleDelete(attachment: TaskAttachment) {
  emit('delete', attachment.id)
}

function handleView(attachment: TaskAttachment) {
  emit('view', attachment)
}

function getFileIcon(fileType: string): string {
  switch (fileType) {
    case 'image':
      return 'pi pi-image'
    case 'document':
      return 'pi pi-file-pdf'
    case 'video':
      return 'pi pi-video'
    default:
      return 'pi pi-file'
  }
}

function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}
</script>

<template>
  <div class="file-uploader">
    <!-- Upload Area -->
    <div class="upload-section">
      <FileUpload
        ref="fileUploadRef"
        mode="advanced"
        :multiple="true"
        :maxFileSize="maxSize"
        :auto="false"
        :customUpload="true"
        @uploader="handleUpload"
        :chooseLabel="t('tasks.choose_files')"
        :uploadLabel="t('tasks.upload')"
        :cancelLabel="t('tasks.cancel')"
        class="file-upload-component"
      >
        <template #empty>
          <div class="upload-empty">
            <i class="pi pi-cloud-upload"></i>
            <p>{{ t('tasks.drag_files_here') }}</p>
            <span class="upload-hint">{{ t('tasks.max_file_size', { size: '10MB' }) }}</span>
          </div>
        </template>
      </FileUpload>

      <!-- Upload Progress -->
      <div v-if="isUploading" class="upload-progress">
        <ProgressBar :value="uploadProgress" :showValue="false" />
        <span class="progress-text">{{ t('tasks.uploading') }}... {{ Math.round(uploadProgress) }}%</span>
      </div>
    </div>

    <!-- Attachments List -->
    <div v-if="attachments && attachments.length > 0" class="attachments-section">
      <h4 class="section-title">
        <i class="pi pi-paperclip"></i>
        {{ t('tasks.attachments') }} ({{ attachments.length }})
      </h4>

      <!-- Images Gallery -->
      <div v-if="images.length > 0" class="images-gallery">
        <div
          v-for="img in images"
          :key="img.id"
          class="image-item"
          @click="handleView(img)"
        >
          <img :src="img.filePath" :alt="img.originalName" />
          <div class="image-overlay">
            <button @click.stop="handleDelete(img)" class="delete-btn">
              <i class="pi pi-trash"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Documents List -->
      <div v-if="documents.length > 0" class="files-list">
        <div
          v-for="doc in documents"
          :key="doc.id"
          class="file-item"
        >
          <div class="file-info" @click="handleView(doc)">
            <i :class="['file-icon', getFileIcon(doc.fileType)]"></i>
            <div class="file-details">
              <span class="file-name">{{ doc.originalName }}</span>
              <span class="file-meta">{{ doc.fileSizeHuman }}</span>
            </div>
          </div>
          <button @click="handleDelete(doc)" class="file-delete">
            <i class="pi pi-times"></i>
          </button>
        </div>
      </div>

      <!-- Other Files -->
      <div v-if="otherFiles.length > 0" class="files-list">
        <div
          v-for="file in otherFiles"
          :key="file.id"
          class="file-item"
        >
          <div class="file-info" @click="handleView(file)">
            <i :class="['file-icon', getFileIcon(file.fileType)]"></i>
            <div class="file-details">
              <span class="file-name">{{ file.originalName }}</span>
              <span class="file-meta">{{ file.fileSizeHuman }}</span>
            </div>
          </div>
          <button @click="handleDelete(file)" class="file-delete">
            <i class="pi pi-times"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.file-uploader {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Upload Section */
.upload-section {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.file-upload-component :deep(.p-fileupload) {
  border: 2px dashed #dee2e6;
  border-radius: 12px;
  background: #f8f9fa;
}

.file-upload-component :deep(.p-fileupload-buttonbar) {
  background: transparent;
  border: none;
  padding: 1rem;
}

.upload-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  gap: 0.75rem;
}

.upload-empty i {
  font-size: 3rem;
  color: #adb5bd;
}

.upload-empty p {
  margin: 0;
  font-size: 0.938rem;
  font-weight: 500;
  color: #495057;
}

.upload-hint {
  font-size: 0.813rem;
  color: #6c757d;
}

/* Upload Progress */
.upload-progress {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.progress-text {
  font-size: 0.813rem;
  font-weight: 500;
  color: #6366f1;
  text-align: center;
}

/* Attachments Section */
.attachments-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.section-title {
  margin: 0;
  font-size: 0.938rem;
  font-weight: 600;
  color: #495057;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-title i {
  color: #6366f1;
  font-size: 1rem;
}

/* Images Gallery */
.images-gallery {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 0.75rem;
}

.image-item {
  position: relative;
  aspect-ratio: 1;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.image-item:hover {
  transform: scale(1.05);
}

.image-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.image-item:hover .image-overlay {
  opacity: 1;
}

.delete-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  background: #dc3545;
  border: none;
  border-radius: 50%;
  color: white;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.delete-btn:hover {
  transform: scale(1.1);
}

/* Files List */
.files-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.file-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.875rem 1rem;
  background: white;
  border: 1.5px solid #e9ecef;
  border-radius: 10px;
  transition: all 0.2s ease;
}

.file-item:hover {
  background: #f8f9fa;
  border-color: #dee2e6;
}

.file-info {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  cursor: pointer;
  flex: 1;
}

.file-icon {
  font-size: 1.5rem;
  color: #6366f1;
}

.file-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.file-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: #495057;
}

.file-meta {
  font-size: 0.75rem;
  color: #6c757d;
}

.file-delete {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  background: transparent;
  border: none;
  border-radius: 50%;
  color: #dc3545;
  cursor: pointer;
  transition: all 0.2s ease;
}

.file-delete:hover {
  background: #fff5f5;
}

/* Mobile */
@media (max-width: 768px) {
  .images-gallery {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.5rem;
  }

  .file-item {
    padding: 0.75rem;
  }

  .file-icon {
    font-size: 1.25rem;
  }

  .file-name {
    font-size: 0.813rem;
  }
}
</style>

