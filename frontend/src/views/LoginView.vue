<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import AuthLayout from '@/components/layout/AuthLayout.vue'
import LoginForm from '@/components/forms/LoginForm.vue'

const { t } = useI18n()
</script>

<template>
  <AuthLayout
    :title="t('auth.login_title')"
    :subtitle="t('auth.login_subtitle')"
  >
    <LoginForm />

    <template #footer>
      <p class="auth-footer-text">
        {{ t('auth.dont_have_account') }}
        <RouterLink to="/register" class="auth-footer-link">
          {{ t('auth.sign_up') }}
        </RouterLink>
      </p>
    </template>
  </AuthLayout>
</template>

<style scoped>
.auth-footer-text {
  color: var(--text-secondary);
  font-size: 0.9375rem;
}

.auth-footer-link {
  color: var(--primary-600);
  font-weight: 600;
  text-decoration: none;
  transition: color var(--transition-fast);
}

.auth-footer-link:hover {
  color: var(--primary-700);
  text-decoration: underline;
}
</style>

