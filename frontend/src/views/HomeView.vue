<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuth } from '@/composables/useAuth'
import BaseButton from '@/components/ui/BaseButton.vue'

const router = useRouter()
const { t } = useI18n()
const { isAuthenticated } = useAuth()

function navigateTo(path: string): void {
  router.push(path)
}
</script>

<template>
  <div class="home-view">
    <div class="home-hero">
      <div class="home-hero__background">
        <div class="hero-gradient"></div>
        <div class="hero-shapes">
          <div class="hero-shape hero-shape-1"></div>
          <div class="hero-shape hero-shape-2"></div>
          <div class="hero-shape hero-shape-3"></div>
        </div>
      </div>

      <div class="container">
        <div class="home-content animate-fade-in">
          <div class="home-logo">
            <i class="pi pi-shield" style="font-size: 4rem"></i>
          </div>

          <h1 class="home-title">
            {{ t('home.title') }}
          </h1>

          <p class="home-subtitle">
            {{ t('home.subtitle') }}
          </p>

          <div class="home-actions">
            <BaseButton
              v-if="!isAuthenticated"
              size="large"
              @click="navigateTo('/register')"
            >
              <i class="pi pi-user-plus"></i>
              {{ t('home.get_started') }}
            </BaseButton>
            <BaseButton
              v-if="!isAuthenticated"
              variant="outline"
              size="large"
              @click="navigateTo('/login')"
            >
              <i class="pi pi-sign-in"></i>
              {{ t('auth.sign_in') }}
            </BaseButton>
            <BaseButton
              v-if="isAuthenticated"
              size="large"
              @click="navigateTo('/dashboard')"
            >
              <i class="pi pi-th-large"></i>
              {{ t('profile.title') }}
            </BaseButton>
          </div>

          <div class="home-features">
            <div class="feature-card">
              <div class="feature-icon">
                <i class="pi pi-lock"></i>
              </div>
              <h3 class="feature-title">{{ t('home.feature_jwt_title') }}</h3>
              <p class="feature-description">
                {{ t('home.feature_jwt_desc') }}
              </p>
            </div>

            <div class="feature-card">
              <div class="feature-icon">
                <i class="pi pi-mobile"></i>
              </div>
              <h3 class="feature-title">{{ t('home.feature_google_title') }}</h3>
              <p class="feature-description">
                {{ t('home.feature_google_desc') }}
              </p>
            </div>

            <div class="feature-card">
              <div class="feature-icon">
                <i class="pi pi-bolt"></i>
              </div>
              <h3 class="feature-title">{{ t('home.feature_modern_title') }}</h3>
              <p class="feature-description">
                {{ t('home.feature_modern_desc') }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.home-view {
  min-height: 100vh;
}

.home-hero {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  overflow: hidden;
}

.home-hero__background {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  opacity: 0.05;
}

.hero-gradient {
  position: absolute;
  inset: 0;
  background: radial-gradient(
    circle at 20% 50%,
    rgba(102, 126, 234, 0.15) 0%,
    transparent 50%
  ),
  radial-gradient(
    circle at 80% 80%,
    rgba(118, 75, 162, 0.15) 0%,
    transparent 50%
  );
}

.hero-shapes {
  position: absolute;
  inset: 0;
}

.hero-shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  animation: heroFloat 25s ease-in-out infinite;
}

.hero-shape-1 {
  width: 500px;
  height: 500px;
  background: rgba(102, 126, 234, 0.2);
  top: -250px;
  left: -250px;
}

.hero-shape-2 {
  width: 400px;
  height: 400px;
  background: rgba(118, 75, 162, 0.2);
  bottom: -200px;
  right: -200px;
  animation-delay: 8s;
}

.hero-shape-3 {
  width: 350px;
  height: 350px;
  background: rgba(14, 165, 233, 0.15);
  top: 40%;
  right: 15%;
  animation-delay: 15s;
}

@keyframes heroFloat {
  0%, 100% {
    transform: translate(0, 0) scale(1);
  }
  33% {
    transform: translate(40px, -40px) scale(1.15);
  }
  66% {
    transform: translate(-30px, 30px) scale(0.9);
  }
}

.home-content {
  position: relative;
  z-index: 1;
  text-align: center;
  max-width: 900px;
  margin: 0 auto;
  padding: 4rem 1rem;
}

.home-logo {
  color: var(--primary-600);
  margin-bottom: 2rem;
  animation: scaleIn 0.5s ease-out;
}

.home-title {
  font-size: 3.5rem;
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 1.5rem;
  letter-spacing: -0.03em;
  line-height: 1.2;
  animation: slideUp 0.6s ease-out 0.2s backwards;
}

.text-gradient {
  background: linear-gradient(135deg, var(--primary-600) 0%, var(--secondary-600) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.home-subtitle {
  font-size: 1.25rem;
  color: var(--text-secondary);
  margin-bottom: 3rem;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
  line-height: 1.7;
  animation: slideUp 0.6s ease-out 0.3s backwards;
}

.home-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 5rem;
  animation: slideUp 0.6s ease-out 0.4s backwards;
}

.home-features {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  animation: slideUp 0.6s ease-out 0.5s backwards;
}

.feature-card {
  background: var(--bg-primary);
  border-radius: 20px;
  padding: 2.5rem 2rem;
  box-shadow: var(--shadow-lg);
  transition: all var(--transition-base);
}

.feature-card:hover {
  transform: translateY(-8px);
  box-shadow: var(--shadow-xl);
}

.feature-icon {
  width: 64px;
  height: 64px;
  margin: 0 auto 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--primary-100) 0%, var(--secondary-100) 100%);
  border-radius: 16px;
  color: var(--primary-600);
  font-size: 2rem;
}

.feature-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 0.75rem;
}

.feature-description {
  color: var(--text-secondary);
  line-height: 1.6;
}

/* Responsive */
@media (max-width: 768px) {
  .home-content {
    padding: 3rem 1rem;
  }

  .home-title {
    font-size: 2.5rem;
  }

  .home-subtitle {
    font-size: 1.125rem;
  }

  .home-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .home-features {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }

  .feature-card {
    padding: 2rem 1.5rem;
  }

  .hero-shape {
    filter: blur(80px);
  }
}

@media (max-width: 480px) {
  .home-title {
    font-size: 2rem;
  }

  .home-subtitle {
    font-size: 1rem;
  }
}
</style>

