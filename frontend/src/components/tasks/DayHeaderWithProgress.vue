<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import Badge from 'primevue/badge'
import type { Task } from '@/types/task.types'

interface Props {
  label: string
  tasks: Task[]
  uncompletedTasks: Task[]
  completedTasks: Task[]
  isSticky?: boolean
}

const props = defineProps<Props>()
const { t } = useI18n()

// Calculate progress
const progress = computed(() => {
  const total = props.tasks.length
  const completed = props.completedTasks.length

  if (total === 0) return 0
  return Math.round((completed / total) * 100)
})

// Get emoji based on progress
const progressEmoji = computed(() => {
  const p = progress.value
  if (p === 100) return '🎉'
  if (p >= 80) return '🔥'
  if (p >= 60) return '💪'
  if (p >= 40) return '⚡'
  if (p >= 20) return '🚀'
  return '💫'
})

// Get gradient colors based on progress
const gradientColors = computed(() => {
  const p = progress.value
  if (p === 100) {
    return 'linear-gradient(135deg, #10b981 0%, #34d399 100%)' // Green
  }
  if (p >= 80) {
    return 'linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%)' // Purple
  }
  if (p >= 60) {
    return 'linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%)' // Blue
  }
  if (p >= 40) {
    return 'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)' // Amber
  }
  return 'linear-gradient(135deg, #6b7280 0%, #9ca3af 100%)' // Gray
})

// Animation trigger
const isAnimating = ref(false)

onMounted(() => {
  setTimeout(() => {
    isAnimating.value = true
  }, 100)
})

// Calculate stroke dashoffset for circular progress
const strokeDashoffset = computed(() => {
  const circumference = 2 * Math.PI * 18 // radius = 18
  return circumference - (progress.value / 100) * circumference
})
</script>

<template>
  <div
    :class="['day-header-container', { 'is-sticky': isSticky }]"
  >
    <div class="day-header-content">
      <!-- Left section: Date and emoji -->
      <div class="header-left">
        <h3 class="day-label">
          {{ label }}
          <span class="progress-emoji">{{ progressEmoji }}</span>
        </h3>
      </div>

      <!-- Right section: Stats and progress -->
      <div class="header-right">
        <!-- Task count badges -->
        <div class="task-badges">
          <Badge
            :value="`${uncompletedTasks.length}`"
            severity="info"
            class="uncompleted-badge"
          />
          <span class="divider">/</span>
          <Badge
            :value="`${tasks.length}`"
            severity="secondary"
            class="total-badge"
          />
        </div>

        <!-- Circular progress -->
        <div class="circular-progress">
          <svg width="44" height="44" viewBox="0 0 44 44">
            <!-- Background circle -->
            <circle
              cx="22"
              cy="22"
              r="18"
              stroke="#e5e7eb"
              stroke-width="3"
              fill="none"
            />
            <!-- Progress circle -->
            <circle
              cx="22"
              cy="22"
              r="18"
              :stroke="progress === 100 ? '#10b981' : '#6366f1'"
              stroke-width="3"
              fill="none"
              stroke-linecap="round"
              :stroke-dasharray="`${2 * Math.PI * 18}`"
              :stroke-dashoffset="strokeDashoffset"
              transform="rotate(-90 22 22)"
              :class="{ 'progress-animated': isAnimating }"
            />
          </svg>
          <div class="progress-text">
            {{ progress }}<span class="percent">%</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Linear progress bar -->
    <div class="linear-progress-container">
      <div class="linear-progress-background">
        <div
          class="linear-progress-fill"
          :style="{
            width: `${progress}%`,
            background: gradientColors
          }"
          :class="{ 'progress-animated': isAnimating }"
        >
          <div class="progress-glow" v-if="progress > 0 && progress < 100" />
        </div>
      </div>

      <!-- Progress status text -->
      <div class="progress-status">
        <span v-if="progress === 100" class="status-complete">
          ✨ {{ t('tasks.all_completed') || 'All tasks completed!' }}
        </span>
        <span v-else-if="progress >= 80" class="status-almost">
          🎯 {{ t('tasks.almost_done') || 'Almost there!' }}
        </span>
        <span v-else-if="completedTasks.length > 0" class="status-progress">
          {{ completedTasks.length }} {{ t('tasks.of') || 'of' }} {{ tasks.length }} {{ t('tasks.completed') || 'completed' }}
        </span>
        <span v-else class="status-start">
          {{ t('tasks.lets_start') || "Let's get started!" }}
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.day-header-container {
  background: white;
  border-radius: 16px;
  padding: 1rem 1.25rem 0.75rem;
  margin-bottom: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  z-index: 10;
}

.day-header-container.is-sticky {
  position: sticky;
  top: 0;
  backdrop-filter: blur(12px);
  background: rgba(255, 255, 255, 0.98);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
  z-index: 20;
}

.day-header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.header-left {
  flex: 1;
}

.day-label {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.progress-emoji {
  font-size: 1.25rem;
  animation: bounce 2s infinite;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-4px); }
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.task-badges {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.divider {
  color: #9ca3af;
  font-size: 0.875rem;
  font-weight: 500;
}

.uncompleted-badge :deep(.p-badge) {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  font-weight: 600;
  min-width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.total-badge :deep(.p-badge) {
  background: #e5e7eb;
  color: #6b7280;
  font-weight: 600;
  min-width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Circular progress */
.circular-progress {
  position: relative;
  width: 44px;
  height: 44px;
}

.circular-progress svg {
  transform: scale(1);
  transition: transform 0.3s;
}

.circular-progress:hover svg {
  transform: scale(1.1);
}

.progress-animated {
  animation: progress-fill 1.5s ease-out forwards;
}

@keyframes progress-fill {
  from {
    stroke-dashoffset: 113; /* 2 * PI * 18 (circumference) */
  }
}

.progress-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 0.75rem;
  font-weight: 700;
  color: #1f2937;
}

.percent {
  font-size: 0.625rem;
  color: #6b7280;
}

/* Linear progress bar */
.linear-progress-container {
  position: relative;
}

.linear-progress-background {
  height: 6px;
  background: #f3f4f6;
  border-radius: 100px;
  overflow: hidden;
  position: relative;
}

.linear-progress-fill {
  height: 100%;
  border-radius: 100px;
  transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.linear-progress-fill.progress-animated {
  animation: slide-in 1.5s ease-out;
}

@keyframes slide-in {
  from {
    width: 0 !important;
  }
}

.progress-glow {
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 10px;
  background: rgba(255, 255, 255, 0.8);
  border-radius: 50%;
  filter: blur(8px);
  animation: glow-pulse 2s infinite;
}

@keyframes glow-pulse {
  0%, 100% { opacity: 0.4; transform: translateY(-50%) scale(1); }
  50% { opacity: 1; transform: translateY(-50%) scale(1.2); }
}

/* Progress status */
.progress-status {
  margin-top: 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  text-align: center;
}

.status-complete {
  color: #10b981;
  font-weight: 600;
}

.status-almost {
  color: #8b5cf6;
  font-weight: 600;
}

.status-progress {
  color: #6b7280;
}

.status-start {
  color: #9ca3af;
  font-style: italic;
}

/* Hover effects */
.day-header-container:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.day-header-container.is-sticky:hover {
  transform: none;
}

/* Mobile responsiveness */
@media (max-width: 640px) {
  .day-header-container {
    padding: 0.875rem 1rem 0.625rem;
  }

  .day-label {
    font-size: 1.125rem;
  }

  .progress-emoji {
    font-size: 1.125rem;
  }

  .circular-progress {
    width: 40px;
    height: 40px;
  }

  .circular-progress svg {
    width: 40px;
    height: 40px;
  }

  .progress-status {
    font-size: 0.7rem;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .day-header-container {
    background: #1f2937;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  }

  .day-header-container.is-sticky {
    background: rgba(31, 41, 55, 0.98);
  }

  .day-label {
    color: #f3f4f6;
  }

  .linear-progress-background {
    background: #374151;
  }

  .progress-text {
    color: #f3f4f6;
  }

  .status-progress,
  .status-start {
    color: #9ca3af;
  }
}
</style>