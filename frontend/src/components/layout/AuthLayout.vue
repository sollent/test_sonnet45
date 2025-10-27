<script setup lang="ts">
interface Props {
  title?: string
  subtitle?: string
}

defineProps<Props>()
</script>

<template>
  <div class="auth-layout">
    <div class="auth-layout__background">
      <div class="auth-layout__gradient"></div>
      <div class="auth-layout__shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
      </div>
    </div>

    <div class="auth-layout__content">
      <div class="auth-layout__card animate-scale-in">
        <div class="auth-layout__logo">
          <i class="pi pi-shield" style="font-size: 3rem"></i>
        </div>

        <div v-if="title || subtitle" class="auth-layout__header">
          <h1 v-if="title" class="auth-layout__title">{{ title }}</h1>
          <p v-if="subtitle" class="auth-layout__subtitle">{{ subtitle }}</p>
        </div>

        <div class="auth-layout__body">
          <slot />
        </div>

        <div class="auth-layout__footer">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-layout {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.auth-layout__background {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  opacity: 0.05;
}

.auth-layout__gradient {
  position: absolute;
  inset: 0;
  background: radial-gradient(
    circle at 20% 50%,
    rgba(102, 126, 234, 0.1) 0%,
    transparent 50%
  ),
  radial-gradient(
    circle at 80% 80%,
    rgba(118, 75, 162, 0.1) 0%,
    transparent 50%
  );
}

.auth-layout__shapes {
  position: absolute;
  inset: 0;
  overflow: hidden;
}

.shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  animation: float 20s ease-in-out infinite;
}

.shape-1 {
  width: 400px;
  height: 400px;
  background: rgba(102, 126, 234, 0.15);
  top: -200px;
  left: -200px;
  animation-delay: 0s;
}

.shape-2 {
  width: 300px;
  height: 300px;
  background: rgba(118, 75, 162, 0.15);
  bottom: -150px;
  right: -150px;
  animation-delay: 5s;
}

.shape-3 {
  width: 250px;
  height: 250px;
  background: rgba(14, 165, 233, 0.1);
  top: 50%;
  right: 10%;
  animation-delay: 10s;
}

@keyframes float {
  0%, 100% {
    transform: translate(0, 0) scale(1);
  }
  33% {
    transform: translate(30px, -30px) scale(1.1);
  }
  66% {
    transform: translate(-20px, 20px) scale(0.9);
  }
}

.auth-layout__content {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 480px;
  padding: 2rem;
}

.auth-layout__card {
  background: var(--bg-primary);
  border-radius: 24px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
  padding: 3rem 2.5rem;
  transition: transform var(--transition-base), box-shadow var(--transition-base);
}

.auth-layout__card:hover {
  transform: translateY(-4px);
  box-shadow: 0 24px 70px rgba(0, 0, 0, 0.12);
}

.auth-layout__logo {
  text-align: center;
  margin-bottom: 2rem;
  color: var(--primary-600);
}

.auth-layout__header {
  text-align: center;
  margin-bottom: 2.5rem;
}

.auth-layout__title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
  letter-spacing: -0.02em;
}

.auth-layout__subtitle {
  font-size: 1rem;
  color: var(--text-secondary);
  line-height: 1.6;
}

.auth-layout__body {
  margin-bottom: 1.5rem;
}

.auth-layout__footer {
  text-align: center;
  padding-top: 1.5rem;
  border-top: 1px solid var(--border-color);
}

/* Responsive */
@media (max-width: 768px) {
  .auth-layout__content {
    padding: 1rem;
  }

  .auth-layout__card {
    padding: 2rem 1.5rem;
    border-radius: 20px;
  }

  .auth-layout__title {
    font-size: 1.75rem;
  }

  .shape {
    filter: blur(60px);
  }

  .shape-1 {
    width: 300px;
    height: 300px;
  }

  .shape-2 {
    width: 250px;
    height: 250px;
  }

  .shape-3 {
    width: 200px;
    height: 200px;
  }
}
</style>

