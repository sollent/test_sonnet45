<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
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

// Refs
const headerRef = ref<HTMLElement | null>(null)
const isStickyInternal = ref(false)

// Use prop if provided, otherwise use internal state
const isSticky = computed(() => props.isSticky !== undefined ? props.isSticky : isStickyInternal.value)

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

// Sticky detection using scroll position
const updateStickyState = () => {
  if (headerRef.value) {
    const rect = headerRef.value.getBoundingClientRect()
    isStickyInternal.value = rect.top <= 0
  }
}

onMounted(() => {
  setTimeout(() => {
    isAnimating.value = true
  }, 100)

  // Add scroll listener to detect sticky state (if not controlled by prop)
  if (props.isSticky === undefined) {
    window.addEventListener('scroll', updateStickyState, { passive: true })
    updateStickyState() // Initial check

    onBeforeUnmount(() => {
      window.removeEventListener('scroll', updateStickyState)
    })
  }
})

// Calculate stroke dashoffset for circular progress
const strokeDashoffset = computed(() => {
  const circumference = 2 * Math.PI * 15 // radius = 15
  return circumference - (progress.value / 100) * circumference
})
</script>

<template>
  <div
    ref="headerRef"
    :class="['day-header-container', { 'is-sticky': isSticky }]"
  >
    <div class="day-header-content">
      <!-- Left section: Date and emoji -->
      <div class="header-left">
        <!-- Date label - changes style based on sticky state -->
        <h3 :class="['day-label', { 'day-label--sticky': isSticky }]">
          {{ label }}
          <span :class="['progress-emoji', { 'progress-emoji--sticky': isSticky }]">{{ progressEmoji }}</span>
        </h3>
      </div>

      <!-- Right section: Stats and progress -->
      <div class="header-right">
        <!-- Task count badges with warm colors -->
        <div class="task-badges">
          <span class="badge-text">
            <span class="completed-count">{{ completedTasks.length }}</span>
            <span class="divider">/</span>
            <span class="total-count">{{ tasks.length }}</span>
          </span>
        </div>

        <!-- Circular progress -->
        <div class="circular-progress">
          <svg width="36" height="36" viewBox="0 0 36 36">
            <!-- Background circle -->
            <circle
              cx="18"
              cy="18"
              r="15"
              stroke="#e5e7eb"
              stroke-width="2.5"
              fill="none"
            />
            <!-- Progress circle -->
            <circle
              cx="18"
              cy="18"
              r="15"
              :stroke="progress === 100 ? '#10b981' : '#34d399'"
              stroke-width="2.5"
              fill="none"
              stroke-linecap="round"
              :stroke-dasharray="`${2 * Math.PI * 15}`"
              :stroke-dashoffset="strokeDashoffset"
              transform="rotate(-90 18 18)"
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
      <div class="progress-status" :class="{ 'status-sticky': isSticky }">
        <span v-if="progress === 100" class="status-complete">
          ✨ {{ t('tasks.all_completed') }}
        </span>
        <span v-else-if="progress >= 80" class="status-almost">
          🎯 {{ t('tasks.almost_done') }}
        </span>
        <span v-else-if="completedTasks.length > 0" class="status-progress">
          {{ completedTasks.length }} {{ t('tasks.tasks_of') }} {{ tasks.length }} {{ t('tasks.tasks_completed') }}
        </span>
        <span v-else class="status-start">
          {{ t('tasks.lets_start') }}
        </span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.day-header-container {
  background: white;
  border-radius: 12px;
  padding: 0.75rem 1rem 0.5rem;
  margin-bottom: 0.75rem;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: -webkit-sticky; /* Safari */
  position: sticky;
  top: 0;
  z-index: 100;
}

.day-header-container.is-sticky {
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px); /* Safari */
  background: rgba(255, 255, 255, 0.85);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  z-index: 100;
  border-radius: 0;
  margin-top: 0;
  padding: 0.5rem 1rem;
  margin-bottom: 0.5rem;
}

.day-header-container.is-sticky .day-header-content {
  margin-bottom: 0;
}

.day-header-container.is-sticky .circular-progress {
  width: 28px;
  height: 28px;
}

.day-header-container.is-sticky .circular-progress svg {
  width: 28px;
  height: 28px;
}

.day-header-container.is-sticky .progress-text {
  font-size: 0.625rem;
}

.day-header-container.is-sticky .percent {
  font-size: 0.5rem;
}

.day-header-container.is-sticky .task-badges {
  padding: 0.2rem 0.5rem;
  border: 1px solid rgba(134, 239, 172, 0.6);
}

.day-header-container.is-sticky .badge-text {
  font-size: 0.75rem;
}

.day-header-container.is-sticky .header-right {
  gap: 0.625rem;
}

.day-header-container.is-sticky .linear-progress-background {
  height: 3px;
  margin-top: 0.5rem;
}

.day-header-container.is-sticky .linear-progress-container {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 0;
}

.day-header-container.is-sticky .progress-status {
  display: none;
}

.day-header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.header-left {
  flex: 1;
}

.day-label {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.day-label--sticky {
  font-size: 0.875rem;
  font-weight: 600;
  gap: 0.375rem;
}

.progress-emoji {
  font-size: 1.125rem;
  animation: bounce 2s infinite;
  transition: all 0.3s ease;
}

.progress-emoji--sticky {
  font-size: 0.875rem;
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
  padding: 0.25rem 0.625rem;
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
  border-radius: 20px;
  border: 1px solid #86efac;
}

.badge-text {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  font-weight: 600;
}

.completed-count {
  color: #059669;
  font-weight: 700;
}

.divider {
  color: #6ee7b7;
  font-size: 0.75rem;
  margin: 0 0.125rem;
}

.total-count {
  color: #10b981;
  font-weight: 600;
}

/* Circular progress */
.circular-progress {
  position: relative;
  width: 36px;
  height: 36px;
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
    stroke-dashoffset: 94.25; /* 2 * PI * 15 (circumference) */
  }
}

.progress-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 0.625rem;
  font-weight: 700;
  color: #1f2937;
  line-height: 1;
}

.percent {
  font-size: 0.5rem;
  color: #6b7280;
  font-weight: 600;
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
  margin-top: 0.625rem;
  font-size: 0.75rem;
  font-weight: 500;
  text-align: center;
  padding-top: 0.525rem;
}

.progress-status.status-sticky {
  margin-top: 0.75rem;
  padding-top: 0.25rem;
}

/* Sticky mode: add more padding on desktop */
.day-header-container.is-sticky .progress-status {
  padding-top: 0.725rem;
}

/* Mobile: keep normal padding in sticky mode */
@media (max-width: 768px) {
  .day-header-container.is-sticky .progress-status {
    padding-top: 0.725rem;
  }
  .progress-status {
    padding-top: 0.025rem !important;
  }
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