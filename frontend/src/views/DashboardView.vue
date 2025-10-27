<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Card from 'primevue/card'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useAuth } from '@/composables/useAuth'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const { t } = useI18n()
const { user, logout } = useAuth()
const { showSuccess } = useToast()

function handleLogout(): void {
  logout()
  showSuccess(t('success.logout_success'), t('common.welcome'))
  router.push('/login')
}
</script>

<template>
  <div class="dashboard-view">
    <div class="dashboard-header">
      <div class="container">
        <div class="header-content">
          <div class="header-left">
            <h1 class="dashboard-title">{{ t('dashboard.title') }}</h1>
            <p class="dashboard-subtitle">{{ t('dashboard.welcome_back') }}, {{ user?.email }}</p>
          </div>
          <div class="header-right">
            <BaseButton variant="text" @click="handleLogout">
              <i class="pi pi-sign-out"></i>
              {{ t('dashboard.logout_button') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </div>

    <div class="dashboard-content">
      <div class="container">
        <div class="dashboard-grid">
          <Card class="dashboard-card animate-slide-up">
            <template #header>
              <div class="card-header-icon">
                <i class="pi pi-user" style="font-size: 2rem"></i>
              </div>
            </template>
            <template #title>{{ t('dashboard.user_info') }}</template>
            <template #content>
              <div class="info-list">
                <div class="info-item">
                  <span class="info-label">{{ t('dashboard.email') }}</span>
                  <span class="info-value">{{ user?.email }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">{{ t('dashboard.name') }}</span>
                  <span class="info-value">{{ user?.name || t('dashboard.not_set') }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">{{ t('dashboard.roles') }}</span>
                  <span class="info-value">{{ user?.roles.join(', ') }}</span>
                </div>
              </div>
            </template>
          </Card>

          <Card class="dashboard-card animate-slide-up" style="animation-delay: 0.1s">
            <template #header>
              <div class="card-header-icon">
                <i class="pi pi-calendar" style="font-size: 2rem"></i>
              </div>
            </template>
            <template #title>{{ t('dashboard.account_details') }}</template>
            <template #content>
              <div class="info-list">
                <div class="info-item">
                  <span class="info-label">{{ t('dashboard.created') }}</span>
                  <span class="info-value">
                    {{ new Date(user?.createdAt || '').toLocaleDateString() }}
                  </span>
                </div>
                <div class="info-item">
                  <span class="info-label">{{ t('dashboard.last_updated') }}</span>
                  <span class="info-value">
                    {{ new Date(user?.updatedAt || '').toLocaleDateString() }}
                  </span>
                </div>
                <div class="info-item">
                  <span class="info-label">{{ t('dashboard.email_verified') }}</span>
                  <span class="info-value">
                    <i
                      :class="[
                        'pi',
                        user?.isEmailVerified ? 'pi-check-circle' : 'pi-times-circle'
                      ]"
                      :style="{
                        color: user?.isEmailVerified ? 'var(--success)' : 'var(--error)'
                      }"
                    ></i>
                    {{ user?.isEmailVerified ? t('dashboard.yes') : t('dashboard.no') }}
                  </span>
                </div>
              </div>
            </template>
          </Card>

          <Card class="dashboard-card animate-slide-up" style="animation-delay: 0.2s">
            <template #header>
              <div class="card-header-icon">
                <i class="pi pi-shield" style="font-size: 2rem"></i>
              </div>
            </template>
            <template #title>{{ t('dashboard.security') }}</template>
            <template #content>
              <div class="security-info">
                <p>{{ t('dashboard.security_description') }}</p>
                <BaseButton variant="outline" full-width class="mt-3">
                  <i class="pi pi-lock"></i>
                  {{ t('dashboard.change_password') }}
                </BaseButton>
              </div>
            </template>
          </Card>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dashboard-view {
  min-height: 100vh;
  background: var(--bg-secondary);
}

.dashboard-header {
  background: var(--bg-primary);
  box-shadow: var(--shadow-sm);
  padding: 2rem 0;
  border-bottom: 1px solid var(--border-color);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
}

.header-left {
  flex: 1;
}

.dashboard-title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.dashboard-subtitle {
  color: var(--text-secondary);
  font-size: 1rem;
}

.header-right {
  display: flex;
  gap: 1rem;
}

.dashboard-content {
  padding: 3rem 0;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.dashboard-card {
  border-radius: 16px;
  box-shadow: var(--shadow-md);
  transition: all var(--transition-base);
}

.dashboard-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

.dashboard-card :deep(.p-card-header) {
  padding: 2rem 2rem 0;
}

.dashboard-card :deep(.p-card-body) {
  padding: 1.5rem 2rem 2rem;
}

.dashboard-card :deep(.p-card-title) {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 1rem;
}

.card-header-icon {
  width: 64px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--primary-100) 0%, var(--secondary-100) 100%);
  border-radius: 16px;
  color: var(--primary-600);
  margin: 0 auto;
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--border-color);
}

.info-item:last-child {
  border-bottom: none;
}

.info-label {
  font-weight: 600;
  color: var(--text-secondary);
  font-size: 0.9375rem;
}

.info-value {
  color: var(--text-primary);
  font-size: 0.9375rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.security-info {
  color: var(--text-secondary);
  line-height: 1.6;
}

.mt-3 {
  margin-top: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    align-items: flex-start;
  }

  .header-right {
    width: 100%;
    flex-direction: column;
  }

  .dashboard-header {
    padding: 1.5rem 0;
  }

  .dashboard-title {
    font-size: 1.5rem;
  }

  .dashboard-content {
    padding: 2rem 0;
  }

  .dashboard-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }

  .info-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
}
</style>

