<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import type { TaskAttachment } from '@/types/task.types'
import Button from 'primevue/button'
import { API_BASE_URL } from '@/config/constants'

const { t } = useI18n()

const props = defineProps<{
  attachments?: TaskAttachment[]
  disabled?: boolean
}>()

const emit = defineEmits<{
  (e: 'upload', file: File): void
  (e: 'delete', attachmentId: number): void
  (e: 'view', attachment: TaskAttachment): void
}>()

const fileInputRef = ref<HTMLInputElement>()
const isDragging = ref(false)

function handleFileSelect(event: Event) {
  const target = event.target as HTMLInputElement
  const files = target.files
  
  if (files && files.length > 0) {
    for (let i = 0; i < files.length; i++) {
      emit('upload', files[i])
    }
    // Clear input
    if (fileInputRef.value) {
      fileInputRef.value.value = ''
    }
  }
}

function handleDrop(event: DragEvent) {
  isDragging.value = false
  event.preventDefault()
  
  const files = event.dataTransfer?.files
  if (files && files.length > 0) {
    for (let i = 0; i < files.length; i++) {
      emit('upload', files[i])
    }
  }
}

function handleDragOver(event: DragEvent) {
  event.preventDefault()
  isDragging.value = true
}

function handleDragLeave() {
  isDragging.value = false
}

function triggerFileInput() {
  fileInputRef.value?.click()
}

function handleDelete(attachmentId: number) {
  emit('delete', attachmentId)
}

function handleView(attachment: TaskAttachment) {
  emit('view', attachment)
}

function getFileIcon(fileType: string): string {
  switch (fileType) {
    case 'image': return 'pi-image'
    case 'document': return 'pi-file-pdf'
    case 'video': return 'pi-video'
    default: return 'pi-file'
  }
}
</script>

<template>
  <div class="simple-file-uploader">
    <!-- Upload Zone -->
    <div 
      :class="['upload-zone', { 'dragging': isDragging, 'disabled': disabled }]"
      @drop="handleDrop"
      @dragover="handleDragOver"
      @dragleave="handleDragLeave"
      @click="triggerFileInput"
    >
      <input
        ref="fileInputRef"
        type="file"
        multiple
        accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt"
        @change="handleFileSelect"
        style="display: none"
      />
      
      <i class="pi pi-cloud-upload upload-icon"></i>
      <p class="upload-text">{{ t('tasks.drag_files_here') }}</p>
      <span class="upload-hint">{{ t('tasks.max_file_size', { size: '10MB' }) }}</span>
    </div>

    <!-- Attachments List -->
    <div v-if="attachments && attachments.length > 0" class="attachments-list">
      <h4 class="list-title">
        <i class="pi pi-paperclip"></i>
        {{ t('tasks.attachments') }} ({{ attachments.length }})
      </h4>

      <div class="files-grid">
        <!-- Images -->
        <div
          v-for="file in attachments.filter(a => a.fileType === 'image')"
          :key="file.id"
          class="file-card image-card"
          @click="handleView(file)"
        >
          <div class="image-preview">
            <img :src="`${API_BASE_URL}${file.filePath}`" :alt="file.originalName" />
            <button @click.stop="handleDelete(file.id)" class="delete-overlay">
              <i class="pi pi-trash"></i>
            </button>
          </div>
          <span class="file-name">{{ file.originalName }}</span>
        </div>

        <!-- Documents -->
        <div
          v-for="file in attachments.filter(a => a.fileType !== 'image')"
          :key="file.id"
          class="file-card doc-card"
        >
          <div class="doc-info" @click="handleView(file)">
            <i :class="['pi', getFileIcon(file.fileType), 'doc-icon']"></i>
            <div class="doc-details">
              <span class="doc-name">{{ file.originalName }}</span>
              <span class="doc-size">{{ file.fileSizeHuman }}</span>
            </div>
          </div>
          <button @click="handleDelete(file.id)" class="delete-btn">
            <i class="pi pi-times"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.simple-file-uploader {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Upload Zone */
.upload-zone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  background: #f8f9fa;
  border: 2px dashed #dee2e6;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.upload-zone:hover {
  background: #e9ecef;
  border-color: #6366f1;
}

.upload-zone.dragging {
  background: #e7e9fc;
  border-color: #6366f1;
  border-style: solid;
}

.upload-zone.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.upload-icon {
  font-size: 2.5rem;
  color: #6366f1;
  margin-bottom: 0.75rem;
}

.upload-text {
  margin: 0;
  font-size: 0.938rem;
  font-weight: 500;
  color: #495057;
}

.upload-hint {
  font-size: 0.813rem;
  color: #6c757d;
  margin-top: 0.25rem;
}

/* Attachments List */
.attachments-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.list-title {
  margin: 0;
  font-size: 0.938rem;
  font-weight: 600;
  color: #495057;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.list-title i {
  color: #6366f1;
}

/* Files Grid */
.files-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 0.75rem;
}

/* Image Card */
.image-card {
  aspect-ratio: 1;
  border-radius: 10px;
  overflow: hidden;
  position: relative;
}

.image-preview {
  width: 100%;
  height: 100%;
  position: relative;
  cursor: pointer;
}

.image-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.delete-overlay {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(220, 53, 69, 0.9);
  border: none;
  border-radius: 50%;
  color: white;
  cursor: pointer;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.image-card:hover .delete-overlay {
  opacity: 1;
}

.file-name {
  display: block;
  font-size: 0.75rem;
  color: #6c757d;
  margin-top: 0.375rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Document Card */
.doc-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem;
  background: white;
  border: 1.5px solid #e9ecef;
  border-radius: 10px;
  grid-column: 1 / -1;
}

.doc-card:hover {
  background: #f8f9fa;
}

.doc-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  flex: 1;
}

.doc-icon {
  font-size: 1.5rem;
  color: #6366f1;
}

.doc-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.doc-name {
  font-size: 0.875rem;
  font-weight: 500;
  color: #495057;
}

.doc-size {
  font-size: 0.75rem;
  color: #6c757d;
}

.delete-btn {
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

.delete-btn:hover {
  background: #fff5f5;
}

/* Mobile */
@media (max-width: 768px) {
  .upload-zone {
    padding: 1.5rem 1rem;
  }

  .upload-icon {
    font-size: 2rem;
  }

  .files-grid {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.5rem;
  }
}
</style>

