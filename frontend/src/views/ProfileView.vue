<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { profileService } from '@/services/profile.service'
import { useToast } from '@/composables/useToast'
import type { UserProfile } from '@/types/profile.types'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputSwitch from 'primevue/inputswitch'
import Password from 'primevue/password'
import Skeleton from 'primevue/skeleton'

const router = useRouter()
const { t } = useI18n()
const { showSuccess, showError } = useToast()

const profile = ref<UserProfile | null>(null)
const isLoading = ref(true)
const isSaving = ref(false)
const activeSection = ref<'general' | 'security' | 'notifications'>('general')

// Form data
const profileForm = ref({
  name: '',
  email: ''
})

const passwordForm = ref({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
})

const notifications = ref({
  email: true,
  push: true,
  taskReminders: true,
  taskAssignments: true,
  taskCompletion: true,
  weeklyDigest: false
})

// Computed
const hasUnsavedChanges = computed(() => {
  if (!profile.value) return false
  return (
    profileForm.value.name !== (profile.value.name || '') ||
    JSON.stringify(notifications.value) !== JSON.stringify(profile.value.notifications)
  )
})

// Methods
async function loadProfile() {
  try {
    isLoading.value = true
    profile.value = await profileService.getProfile()

    // Initialize forms
    profileForm.value.name = profile.value.name || ''
    profileForm.value.email = profile.value.email
    notifications.value = { ...profile.value.notifications }
  } catch (error) {
    console.error('Failed to load profile:', error)
    showError(t('profile.profile_load_failed'))
  } finally {
    isLoading.value = false
  }
}

async function saveProfile() {
  try {
    isSaving.value = true

    await profileService.updateProfile({
      name: profileForm.value.name || null
    })

    await profileService.updateNotifications({
      notifications: notifications.value
    })

    showSuccess(t('profile.profile_updated'))
    await loadProfile()
  } catch (error) {
    console.error('Failed to save profile:', error)
    showError(t('profile.profile_update_failed'))
  } finally {
    isSaving.value = false
  }
}

async function changePassword() {
  if (passwordForm.value.newPassword !== passwordForm.value.confirmPassword) {
    showError(t('profile.passwords_not_match'))
    return
  }

  if (passwordForm.value.newPassword.length < 8) {
    showError(t('profile.password_min_length'))
    return
  }

  try {
    isSaving.value = true

    await profileService.updatePassword({
      currentPassword: profile.value?.hasPassword ? passwordForm.value.currentPassword : undefined,
      newPassword: passwordForm.value.newPassword,
      confirmPassword: passwordForm.value.confirmPassword
    })

    showSuccess(profile.value?.hasPassword ? t('profile.password_changed') : t('profile.password_created'))

    // Clear form
    passwordForm.value = {
      currentPassword: '',
      newPassword: '',
      confirmPassword: ''
    }

    await loadProfile()
  } catch (error: any) {
    console.error('Failed to change password:', error)
    showError(error.response?.data?.message || t('profile.password_change_failed'))
  } finally {
    isSaving.value = false
  }
}

function goBack() {
  router.push('/dashboard')
}

onMounted(() => {
  loadProfile()
})
</script>

<template>
  <div class="profile-view">
    <!-- Background Gradient -->
    <div class="profile-background">
      <div class="gradient-orb orb-1"></div>
      <div class="gradient-orb orb-2"></div>
      <div class="gradient-orb orb-3"></div>
    </div>

    <!-- Header -->
    <header class="profile-header">
      <div class="header-content">
        <button @click="goBack" class="back-button">
          <i class="pi pi-arrow-left"></i>
          <span>{{ t('common.back') }}</span>
        </button>
        <h1 class="header-title">{{ t('profile.settings_title') }}</h1>
        <div class="header-actions">
          <Button
            v-if="hasUnsavedChanges"
            @click="saveProfile"
            :loading="isSaving"
            :label="t('common.save')"
            icon="pi pi-check"
            class="save-button"
          />
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <div class="profile-container">
      <!-- Sidebar Navigation -->
      <aside class="profile-sidebar">
        <nav class="sidebar-nav">
          <button
            :class="['nav-item', { active: activeSection === 'general' }]"
            @click="activeSection = 'general'"
          >
            <i class="pi pi-user"></i>
            <span>{{ t('profile.general') }}</span>
          </button>
          <button
            :class="['nav-item', { active: activeSection === 'security' }]"
            @click="activeSection = 'security'"
          >
            <i class="pi pi-shield"></i>
            <span>{{ t('profile.security') }}</span>
          </button>
          <button
            :class="['nav-item', { active: activeSection === 'notifications' }]"
            @click="activeSection = 'notifications'"
          >
            <i class="pi pi-bell"></i>
            <span>{{ t('profile.notifications') }}</span>
          </button>
        </nav>
      </aside>

      <!-- Content Area -->
      <main class="profile-content">
        <!-- Loading State -->
        <div v-if="isLoading" class="loading-state">
          <Skeleton height="200px" class="mb-4" borderRadius="16px" />
          <Skeleton height="150px" class="mb-4" borderRadius="16px" />
          <Skeleton height="150px" borderRadius="16px" />
        </div>

        <!-- General Settings -->
        <div v-else-if="activeSection === 'general'" class="content-section">
          <h2 class="section-title">{{ t('profile.general_settings') }}</h2>

          <!-- Profile Info Card -->
          <div class="settings-card">
            <div class="card-header">
              <i class="pi pi-user card-icon"></i>
              <h3>{{ t('profile.personal_info') }}</h3>
            </div>
            <div class="card-content">
              <div class="form-field">
                <label>{{ t('profile.name_label') }}</label>
                <InputText
                  v-model="profileForm.name"
                  :placeholder="t('profile.name_placeholder')"
                  class="field-input"
                />
              </div>
              <div class="form-field">
                <label>{{ t('profile.email_label') }}</label>
                <InputText
                  v-model="profileForm.email"
                  disabled
                  class="field-input"
                />
                <p class="field-hint">{{ t('profile.email_cannot_change') }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Security Settings -->
        <div v-else-if="activeSection === 'security'" class="content-section">
          <h2 class="section-title">{{ t('profile.security_settings') }}</h2>

          <div class="settings-card">
            <div class="card-header">
              <i class="pi pi-lock card-icon"></i>
              <h3>{{ profile?.hasPassword ? t('profile.change_password') : t('profile.create_password') }}</h3>
            </div>
            <div class="card-content">
              <p v-if="!profile?.hasPassword" class="info-message">
                <i class="pi pi-info-circle"></i>
                {{ t('profile.google_auth_info') }}
              </p>

              <div v-if="profile?.hasPassword" class="form-field">
                <label>{{ t('profile.current_password') }}</label>
                <Password
                  v-model="passwordForm.currentPassword"
                  :placeholder="t('profile.current_password_placeholder')"
                  :feedback="false"
                  toggleMask
                  class="field-input"
                />
              </div>

              <div class="form-field">
                <label>{{ t('profile.new_password') }}</label>
                <Password
                  v-model="passwordForm.newPassword"
                  :placeholder="t('profile.new_password_placeholder')"
                  toggleMask
                  class="field-input"
                />
              </div>

              <div class="form-field">
                <label>{{ t('profile.confirm_password') }}</label>
                <Password
                  v-model="passwordForm.confirmPassword"
                  :placeholder="t('profile.confirm_password_placeholder')"
                  :feedback="false"
                  toggleMask
                  class="field-input"
                />
              </div>

              <Button
                @click="changePassword"
                :label="profile?.hasPassword ? t('profile.change_password') : t('profile.create_password')"
                :loading="isSaving"
                icon="pi pi-lock"
                class="action-button"
              />
            </div>
          </div>

          <!-- Login Methods Card -->
          <div class="settings-card">
            <div class="card-header">
              <i class="pi pi-sign-in card-icon"></i>
              <h3>{{ t('profile.login_methods') }}</h3>
            </div>
            <div class="card-content">
              <div class="login-method">
                <div class="method-info">
                  <i class="pi pi-envelope method-icon"></i>
                  <div>
                    <p class="method-name">{{ t('profile.email_password') }}</p>
                    <p class="method-status">{{ profile?.hasPassword ? t('profile.active') : t('profile.not_configured') }}</p>
                  </div>
                </div>
                <span :class="['status-badge', { active: profile?.hasPassword }]">
                  {{ profile?.hasPassword ? t('profile.enabled') : t('profile.disabled') }}
                </span>
              </div>

              <div class="login-method">
                <div class="method-info">
                  <i class="pi pi-google method-icon"></i>
                  <div>
                    <p class="method-name">{{ t('profile.google') }}</p>
                    <p class="method-status">{{ profile?.isGoogleAuth ? t('profile.connected') : t('profile.not_connected') }}</p>
                  </div>
                </div>
                <span :class="['status-badge', { active: profile?.isGoogleAuth }]">
                  {{ profile?.isGoogleAuth ? t('profile.enabled') : t('profile.disabled') }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Notifications Settings -->
        <div v-else-if="activeSection === 'notifications'" class="content-section">
          <h2 class="section-title">{{ t('profile.notifications_settings') }}</h2>

          <div class="settings-card">
            <div class="card-header">
              <i class="pi pi-bell card-icon"></i>
              <h3>{{ t('profile.notifications_settings') }}</h3>
            </div>
            <div class="card-content">
              <div class="notification-item">
                <div class="notification-info">
                  <i class="pi pi-envelope"></i>
                  <div>
                    <p class="notification-name">{{ t('profile.email_notifications') }}</p>
                    <p class="notification-desc">{{ t('profile.email_notifications_desc') }}</p>
                  </div>
                </div>
                <InputSwitch v-model="notifications.email" />
              </div>

              <div class="notification-item">
                <div class="notification-info">
                  <i class="pi pi-bell"></i>
                  <div>
                    <p class="notification-name">{{ t('profile.push_notifications') }}</p>
                    <p class="notification-desc">{{ t('profile.push_notifications_desc') }}</p>
                  </div>
                </div>
                <InputSwitch v-model="notifications.push" />
              </div>

              <div class="divider"></div>

              <div class="notification-item">
                <div class="notification-info">
                  <i class="pi pi-clock"></i>
                  <div>
                    <p class="notification-name">{{ t('profile.task_reminders') }}</p>
                    <p class="notification-desc">{{ t('profile.task_reminders_desc') }}</p>
                  </div>
                </div>
                <InputSwitch v-model="notifications.taskReminders" />
              </div>

              <div class="notification-item">
                <div class="notification-info">
                  <i class="pi pi-user-plus"></i>
                  <div>
                    <p class="notification-name">{{ t('profile.task_assignments') }}</p>
                    <p class="notification-desc">{{ t('profile.task_assignments_desc') }}</p>
                  </div>
                </div>
                <InputSwitch v-model="notifications.taskAssignments" />
              </div>

              <div class="notification-item">
                <div class="notification-info">
                  <i class="pi pi-check-circle"></i>
                  <div>
                    <p class="notification-name">{{ t('profile.task_completion') }}</p>
                    <p class="notification-desc">{{ t('profile.task_completion_desc') }}</p>
                  </div>
                </div>
                <InputSwitch v-model="notifications.taskCompletion" />
              </div>

              <div class="notification-item">
                <div class="notification-info">
                  <i class="pi pi-calendar"></i>
                  <div>
                    <p class="notification-name">{{ t('profile.weekly_digest') }}</p>
                    <p class="notification-desc">{{ t('profile.weekly_digest_desc') }}</p>
                  </div>
                </div>
                <InputSwitch v-model="notifications.weeklyDigest" />
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
/* ===== Layout ===== */
.profile-view {
  min-height: 100vh;
  position: relative;
  background: #f8f9fa;
  overflow-x: hidden;
}

/* Background */
.profile-background {
  position: fixed;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  overflow: hidden;
}

.gradient-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.3;
  animation: float 20s ease-in-out infinite;
}

.orb-1 {
  width: 500px;
  height: 500px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  top: -100px;
  right: -100px;
  animation-delay: 0s;
}

.orb-2 {
  width: 400px;
  height: 400px;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  bottom: -100px;
  left: -100px;
  animation-delay: 7s;
}

.orb-3 {
  width: 300px;
  height: 300px;
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  top: 50%;
  left: 50%;
  animation-delay: 14s;
}

@keyframes float {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(30px, -30px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
}

/* Header */
.profile-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
  padding: 1.5rem 0;
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  align-items: center;
  gap: 2rem;
}

.back-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  background: white;
  border: 1.5px solid #e9ecef;
  border-radius: 12px;
  font-size: 0.9375rem;
  font-weight: 500;
  color: #495057;
  cursor: pointer;
  transition: all 0.2s ease;
}

.back-button:hover {
  background: #f8f9fa;
  border-color: #dee2e6;
  transform: translateX(-2px);
}

.header-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0;
  flex: 1;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

.save-button {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  padding: 0.625rem 1.5rem;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.save-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

/* Container */
.profile-container {
  position: relative;
  z-index: 1;
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 2rem;
  align-items: flex-start;
}

/* Sidebar */
.profile-sidebar {
  position: sticky;
  top: 120px;
  background: white;
  border-radius: 16px;
  padding: 1rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(226, 232, 240, 0.8);
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1.25rem;
  background: transparent;
  border: none;
  border-radius: 12px;
  font-size: 0.9375rem;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: left;
}

.nav-item i {
  font-size: 1.125rem;
}

.nav-item:hover {
  background: #f8f9fa;
  color: #495057;
}

.nav-item.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Content */
.profile-content {
  min-height: 500px;
}

.content-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.section-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0;
}

/* Settings Cards */
.settings-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(226, 232, 240, 0.8);
  transition: all 0.3s ease;
}

.settings-card:hover {
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.card-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.card-icon {
  font-size: 1.5rem;
  color: #667eea;
}

.card-header h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0;
}

.card-description {
  color: #64748b;
  margin-bottom: 1.5rem;
  font-size: 0.9375rem;
}

.card-content {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* Avatar Section */
.avatar-card {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
}

.avatar-section {
  display: flex;
  align-items: center;
  gap: 2rem;
}

.avatar-preview {
  flex-shrink: 0;
}

.avatar-initials {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  font-size: 2rem;
  font-weight: 700;
  color: white;
}

.avatar-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.avatar-hint {
  font-size: 0.8125rem;
  color: #94a3b8;
  margin: 0;
}

/* Form Fields */
.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-field label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
}

.field-input {
  width: 100%;
}

.field-input :deep(.p-inputtext) {
  width: 100%;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  border: 1.5px solid #e9ecef;
  font-size: 0.9375rem;
  transition: all 0.2s ease;
}

.field-input :deep(.p-inputtext:focus) {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.field-hint {
  font-size: 0.8125rem;
  color: #94a3b8;
  margin: 0;
}

.info-message {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem;
  background: #eff6ff;
  border-radius: 12px;
  color: #1e40af;
  font-size: 0.9375rem;
  line-height: 1.5;
}

.info-message i {
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.action-button {
  align-self: flex-start;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Login Methods */
.login-method {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 12px;
}

.method-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.method-icon {
  font-size: 1.5rem;
  color: #667eea;
}

.method-name {
  font-weight: 600;
  color: #1a202c;
  margin: 0 0 0.25rem 0;
}

.method-status {
  font-size: 0.8125rem;
  color: #64748b;
  margin: 0;
}

.status-badge {
  padding: 0.375rem 0.875rem;
  border-radius: 20px;
  font-size: 0.8125rem;
  font-weight: 600;
  background: #e2e8f0;
  color: #64748b;
}

.status-badge.active {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

/* Theme Selector */
.theme-selector {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.theme-selector :deep(.p-button) {
  flex: 1;
  min-width: 120px;
  padding: 0.875rem;
  border-radius: 12px;
  font-weight: 500;
}

.theme-preview {
  margin-top: 1rem;
  padding: 2rem;
  background: #f8f9fa;
  border-radius: 12px;
}

.preview-card {
  max-width: 300px;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.preview-header {
  height: 60px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.preview-content {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.preview-line {
  height: 12px;
  background: #e2e8f0;
  border-radius: 4px;
}

.preview-line.short {
  width: 60%;
}

/* Notifications */
.notification-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 0;
}

.notification-info {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  flex: 1;
}

.notification-info i {
  font-size: 1.25rem;
  color: #667eea;
  margin-top: 0.125rem;
}

.notification-name {
  font-weight: 600;
  color: #1a202c;
  margin: 0 0 0.25rem 0;
}

.notification-desc {
  font-size: 0.8125rem;
  color: #64748b;
  margin: 0;
}

.divider {
  height: 1px;
  background: #e2e8f0;
  margin: 0.5rem 0;
}

/* Loading State */
.loading-state {
  padding: 2rem 0;
}

/* Responsive */
@media (max-width: 1024px) {
  .profile-container {
    grid-template-columns: 1fr;
    gap: 1.5rem;
    padding: 1.5rem;
  }

  .profile-sidebar {
    position: static;
    padding: 0.75rem;
  }

  .sidebar-nav {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
  }

  .nav-item {
    flex-direction: column;
    gap: 0.5rem;
    padding: 1rem 0.75rem;
    text-align: center;
    font-size: 0.875rem;
  }

  .nav-item i {
    font-size: 1.5rem;
  }

  .nav-item span {
    font-size: 0.8125rem;
  }
}

@media (max-width: 768px) {
  .profile-header {
    padding: 1rem 0;
  }

  .header-content {
    padding: 0 1rem;
    gap: 1rem;
    flex-wrap: wrap;
  }

  .back-button {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
  }

  .header-title {
    font-size: 1.25rem;
    flex: 1 1 100%;
    order: -1;
  }

  .header-actions {
    margin-left: auto;
  }

  .profile-container {
    padding: 1rem;
    gap: 1rem;
  }

  .profile-sidebar {
    border-radius: 12px;
    padding: 0.5rem;
  }

  .sidebar-nav {
    grid-template-columns: repeat(3, 1fr);
    gap: 0.375rem;
  }

  .nav-item {
    padding: 0.875rem 0.5rem;
    border-radius: 10px;
  }

  .nav-item i {
    font-size: 1.375rem;
  }

  .nav-item span {
    font-size: 0.75rem;
  }

  .section-title {
    font-size: 1.25rem;
  }

  .settings-card {
    padding: 1.25rem;
    border-radius: 12px;
  }

  .card-header {
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
  }

  .card-header h3 {
    font-size: 1rem;
    flex: 1;
  }

  .form-field {
    margin-bottom: 1.25rem;
  }

  .form-field label {
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
  }

  .field-input {
    font-size: 0.9375rem;
  }

  .notification-item {
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 10px;
    margin-bottom: 0.5rem;
  }

  .notification-info {
    flex: 1 1 100%;
    gap: 0.875rem;
  }

  .notification-info i {
    font-size: 1.125rem;
  }

  .notification-name {
    font-size: 0.9375rem;
  }

  .notification-desc {
    font-size: 0.8125rem;
  }

  .login-method {
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 10px;
    margin-bottom: 0.5rem;
  }

  .method-info {
    flex: 1 1 100%;
  }

  .divider {
    margin: 0.75rem 0;
  }
}

@media (max-width: 480px) {
  .profile-header {
    padding: 0.75rem 0;
  }

  .header-content {
    padding: 0 0.75rem;
    gap: 0.75rem;
  }

  .back-button {
    padding: 0.5rem 0.875rem;
    font-size: 0.8125rem;
  }

  .back-button span {
    display: none;
  }

  .header-title {
    font-size: 1.125rem;
  }

  .save-button {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
  }

  .profile-container {
    padding: 0.75rem;
  }

  .profile-sidebar {
    padding: 0.375rem;
  }

  .sidebar-nav {
    gap: 0.25rem;
  }

  .nav-item {
    padding: 0.75rem 0.375rem;
    gap: 0.375rem;
  }

  .nav-item i {
    font-size: 1.25rem;
  }

  .nav-item span {
    font-size: 0.6875rem;
  }

  .content-section {
    gap: 1rem;
  }

  .section-title {
    font-size: 1.125rem;
  }

  .settings-card {
    padding: 1rem;
  }

  .card-header h3 {
    font-size: 0.9375rem;
  }

  .card-icon {
    font-size: 1.25rem;
  }

  .form-field label {
    font-size: 0.8125rem;
  }

  .field-input {
    font-size: 0.875rem;
    padding: 0.625rem 0.875rem;
  }

  .action-button {
    width: 100%;
    padding: 0.75rem;
    font-size: 0.9375rem;
  }

  .notification-item {
    padding: 0.875rem;
  }

  .login-method {
    padding: 0.875rem;
  }
}
</style>
