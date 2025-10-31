<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const emit = defineEmits<{
  'loaded': []
}>()

const showLoader = ref(true)
const progress = ref(0)
const completedTasks = ref(0)
const currentPhaseIndex = ref(0)

const tasks = computed(() => [
  t('loader.tasks.init_system'),
  t('loader.tasks.load_components'),
  t('loader.tasks.setup_interface'),
  t('loader.tasks.prepare_data'),
  t('loader.tasks.final_check')
])

const loadingPhases = computed(() => [
  t('loader.phases.connecting'),
  t('loader.phases.loading_profile'),
  t('loader.phases.syncing_tasks'),
  t('loader.phases.preparing_interface'),
  t('loader.phases.applying_settings'),
  t('loader.phases.optimizing'),
  t('loader.phases.finishing')
])

const currentPhase = computed(() => loadingPhases.value[currentPhaseIndex.value])

onMounted(() => {
  // Update phase more frequently
  const phaseInterval = setInterval(() => {
    if (currentPhaseIndex.value < loadingPhases.value.length - 1) {
      currentPhaseIndex.value++
    }
  }, 600)

  // Task completion animation
  const taskInterval = setInterval(() => {
    if (completedTasks.value < tasks.value.length) {
      setTimeout(() => {
        completedTasks.value++
        progress.value = (completedTasks.value / tasks.value.length) * 100
      }, 400)
    } else {
      clearInterval(taskInterval)
      clearInterval(phaseInterval)
      setTimeout(() => {
        showLoader.value = false
        setTimeout(() => emit('loaded'), 400)
      }, 600)
    }
  }, 750)
})
</script>

<template>
  <Transition name="loader-fade">
    <div v-if="showLoader" class="app-loader">
      <!-- Animated background -->
      <div class="bg-gradient">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
      </div>

      <!-- Main content -->
      <div class="loader-content">
        <!-- 3D Notebook -->
        <div class="notebook-scene">
          <div class="notebook">
            <!-- Cover -->
            <div class="cover">
              <div class="cover-decoration"></div>
            </div>
            
            <!-- Page -->
            <div class="page">
              <!-- Page header -->
              <div class="page-header">
                <div class="header-line"></div>
                <div class="header-title">Task Manager</div>
                <div class="header-line"></div>
              </div>

              <!-- Tasks -->
              <div class="task-list">
                <div 
                  v-for="(task, idx) in tasks" 
                  :key="idx"
                  class="task-item"
                  :class="{ 
                    'is-completed': idx < completedTasks,
                    'is-active': idx === completedTasks
                  }"
                >
                  <div class="task-checkbox">
                    <svg class="check-icon" viewBox="0 0 20 20">
                      <path d="M4 10l4 4 8-8" 
                            fill="none" 
                            stroke="currentColor" 
                            stroke-width="2.5" 
                            stroke-linecap="round" 
                            stroke-linejoin="round"/>
                    </svg>
                  </div>
                  <span class="task-name">{{ task }}</span>
                </div>
              </div>

              <!-- Progress -->
              <div class="page-progress">
                <div class="progress-track">
                  <div class="progress-bar" :style="{ width: progress + '%' }"></div>
                </div>
                <div class="progress-percent">{{ Math.round(progress) }}%</div>
              </div>
            </div>

            <!-- Spiral -->
            <div class="spiral">
              <div v-for="n in 10" :key="n" class="spiral-ring"></div>
            </div>
          </div>

        </div>

        <!-- Status bar -->
        <div class="status-bar">
          <!-- 3D Crystal -->
          <div class="crystal-wrap">
            <div class="crystal">
              <div class="crystal-face f-front"></div>
              <div class="crystal-face f-back"></div>
              <div class="crystal-face f-left"></div>
              <div class="crystal-face f-right"></div>
              <div class="crystal-face f-top"></div>
              <div class="crystal-face f-bottom"></div>
            </div>
          </div>

          <!-- Status text -->
          <div class="status-text-wrap">
            <Transition name="phase-slide" mode="out-in">
              <div :key="currentPhaseIndex" class="status-text">
                {{ currentPhase }}
              </div>
            </Transition>
            <div class="status-mini-progress">
              <div class="mini-bar">
                <div class="mini-fill" :style="{ width: progress + '%' }"></div>
              </div>
              <span class="mini-percent">{{ Math.round(progress) }}%</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
/* Container */
.app-loader {
  position: fixed;
  inset: 0;
  z-index: 99999;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  overflow: hidden;
  perspective: 1500px;
}

/* Background */
.bg-gradient {
  position: absolute;
  inset: 0;
  overflow: hidden;
}

.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.3;
  animation: orbFloat 18s ease-in-out infinite;
}

.orb-1 {
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, rgba(240, 147, 251, 0.6), transparent 70%);
  top: -150px;
  left: -150px;
  animation-duration: 16s;
}

.orb-2 {
  width: 450px;
  height: 450px;
  background: radial-gradient(circle, rgba(0, 210, 255, 0.5), transparent 70%);
  bottom: -120px;
  right: -120px;
  animation-duration: 20s;
  animation-delay: -5s;
}

.orb-3 {
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(245, 87, 108, 0.4), transparent 70%);
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  animation-duration: 22s;
  animation-delay: -10s;
}

@keyframes orbFloat {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(40px, -40px) scale(1.08); }
  66% { transform: translate(-40px, 30px) scale(0.92); }
}

/* Content */
.loader-content {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2.5rem;
  z-index: 1;
}

/* Notebook Scene */
.notebook-scene {
  position: relative;
  animation: sceneEntrance 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}

@keyframes sceneEntrance {
  0% {
    opacity: 0;
    transform: translateY(-80px) rotateX(60deg);
  }
  100% {
    opacity: 1;
    transform: translateY(0) rotateX(0);
  }
}

/* 3D Notebook */
.notebook {
  position: relative;
  width: 420px;
  height: 520px;
  transform-style: preserve-3d;
  animation: notebookFloat 5s ease-in-out infinite;
  filter: drop-shadow(0 25px 50px rgba(0, 0, 0, 0.25));
}

@keyframes notebookFloat {
  0%, 100% { transform: translateY(0) rotateY(-3deg) rotateX(3deg); }
  50% { transform: translateY(-15px) rotateY(3deg) rotateX(-2deg); }
}

/* Cover - hidden, only page visible */
.cover {
  display: none;
}

.cover-decoration {
  display: none;
}

/* Page */
.page {
  position: absolute;
  top: 0;
  left: 60px;
  right: 0;
  bottom: 0;
  background: #ffffff;
  border-radius: 10px;
  padding: 32px 28px;
  box-sizing: border-box;
  transform: translateZ(0);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
}

/* Page header */
.page-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 24px;
  padding-bottom: 12px;
  border-bottom: 2px solid #f1f3f5;
}

.header-line {
  flex: 1;
  height: 2px;
  background: linear-gradient(90deg, transparent, #667eea 50%, transparent);
}

.header-title {
  font-size: 1.35rem;
  font-weight: 700;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* Task list */
.task-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
  flex: 1;
}

.task-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 13px;
  background: #f8f9fa;
  border-radius: 8px;
  opacity: 0;
  transform: translateX(-25px);
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.task-item.is-active {
  animation: taskAppear 0.5s ease-out forwards, taskPulse 1.2s ease-in-out infinite;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
  box-shadow: 0 3px 10px rgba(102, 126, 234, 0.15);
}

.task-item.is-completed {
  animation: taskComplete 0.55s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}

@keyframes taskAppear {
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes taskPulse {
  0%, 100% { box-shadow: 0 3px 10px rgba(102, 126, 234, 0.15); }
  50% { box-shadow: 0 5px 18px rgba(102, 126, 234, 0.3); }
}

@keyframes taskComplete {
  0% { transform: translateX(0) scale(1); }
  40% { transform: translateX(4px) scale(1.03); }
  100% {
    transform: translateX(0) scale(1);
    opacity: 0.65;
  }
}

/* Checkbox */
.task-checkbox {
  width: 22px;
  height: 22px;
  border: 2px solid #cbd5e1;
  border-radius: 5px;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.3s ease;
}

.task-item.is-active .task-checkbox {
  border-color: #667eea;
  animation: checkboxShake 0.45s ease;
}

.task-item.is-completed .task-checkbox {
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-color: #667eea;
  animation: checkboxPop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes checkboxShake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-2px); }
  75% { transform: translateX(2px); }
}

@keyframes checkboxPop {
  0% { transform: scale(0.75); }
  50% { transform: scale(1.15); }
  100% { transform: scale(1); }
}

/* Check icon */
.check-icon {
  width: 13px;
  height: 13px;
  color: #fff;
  opacity: 0;
  stroke-dasharray: 20;
  stroke-dashoffset: 20;
}

.task-item.is-completed .check-icon {
  animation: drawCheck 0.35s ease-out 0.15s forwards;
}

@keyframes drawCheck {
  to {
    opacity: 1;
    stroke-dashoffset: 0;
  }
}

/* Task name */
.task-name {
  flex: 1;
  font-size: 0.88rem;
  font-weight: 500;
  color: #334155;
  line-height: 1.3;
  transition: all 0.3s ease;
}

.task-item.is-completed .task-name {
  color: #94a3b8;
  text-decoration: line-through;
}

/* Page progress */
.page-progress {
  display: flex;
  align-items: center;
  gap: 12px;
  padding-top: 16px;
  margin-top: auto;
  border-top: 2px solid #f1f3f5;
}

.progress-track {
  flex: 1;
  height: 7px;
  background: #e9ecef;
  border-radius: 4px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #667eea, #764ba2);
  border-radius: 4px;
  transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.progress-bar::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3) 50%, transparent);
  animation: shimmer 1.3s ease-in-out infinite;
}

@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.progress-percent {
  font-size: 0.95rem;
  font-weight: 700;
  background: linear-gradient(135deg, #667eea, #764ba2);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  min-width: 45px;
  text-align: right;
}

/* Spiral */
.spiral {
  position: absolute;
  left: 48px;
  top: 22px;
  bottom: 22px;
  width: 18px;
  display: flex;
  flex-direction: column;
  justify-content: space-evenly;
  transform: translateZ(23px);
}

.spiral-ring {
  width: 16px;
  height: 18px;
  border: 2.5px solid #8b99ab;
  border-radius: 50%;
  background: linear-gradient(90deg, rgba(139, 153, 171, 0.25), transparent 50%);
  animation: spiralRotate 3.5s linear infinite;
}

.spiral-ring:nth-child(odd) {
  animation-delay: -0.2s;
}

@keyframes spiralRotate {
  0%, 100% { transform: rotateY(0deg); }
  50% { transform: rotateY(180deg); }
}

/* Floating icons - removed */

/* Status bar */
.status-bar {
  display: flex;
  align-items: center;
  gap: 1.8rem;
}

/* 3D Crystal */
.crystal-wrap {
  perspective: 600px;
  width: 50px;
  height: 50px;
  flex-shrink: 0;
}

.crystal {
  position: relative;
  width: 100%;
  height: 100%;
  transform-style: preserve-3d;
  animation: crystalSpin 6s linear infinite;
}

@keyframes crystalSpin {
  0% { transform: rotateX(0deg) rotateY(0deg); }
  100% { transform: rotateX(360deg) rotateY(360deg); }
}

.crystal-face {
  position: absolute;
  width: 50px;
  height: 50px;
  border-radius: 5px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 0 15px rgba(102, 126, 234, 0.2);
  backface-visibility: visible;
}

.f-front {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.4), rgba(118, 75, 162, 0.4));
  transform: translateZ(25px);
}

.f-back {
  background: linear-gradient(135deg, rgba(240, 147, 251, 0.35), rgba(245, 87, 108, 0.35));
  transform: translateZ(-25px) rotateY(180deg);
}

.f-left {
  background: linear-gradient(135deg, rgba(245, 87, 108, 0.38), rgba(240, 147, 251, 0.38));
  transform: rotateY(-90deg) translateZ(25px);
}

.f-right {
  background: linear-gradient(135deg, rgba(0, 210, 255, 0.35), rgba(58, 71, 213, 0.35));
  transform: rotateY(90deg) translateZ(25px);
}

.f-top {
  background: linear-gradient(135deg, rgba(118, 75, 162, 0.4), rgba(102, 126, 234, 0.4));
  transform: rotateX(90deg) translateZ(25px);
}

.f-bottom {
  background: linear-gradient(135deg, rgba(58, 71, 213, 0.35), rgba(0, 210, 255, 0.35));
  transform: rotateX(-90deg) translateZ(25px);
}

/* Status text */
.status-text-wrap {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  color: #fff;
}

.status-text {
  font-size: 1.05rem;
  font-weight: 500;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
  min-height: 1.4rem;
  min-width: 280px;
}

.phase-slide-enter-active,
.phase-slide-leave-active {
  transition: all 0.25s ease;
}

.phase-slide-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.phase-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.status-mini-progress {
  display: flex;
  align-items: center;
  gap: 0.9rem;
}

.mini-bar {
  flex: 1;
  height: 5px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
  overflow: hidden;
}

.mini-fill {
  height: 100%;
  background: #fff;
  border-radius: 3px;
  transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 0 8px rgba(255, 255, 255, 0.4);
}

.mini-percent {
  font-size: 0.95rem;
  font-weight: 700;
  min-width: 45px;
  text-align: right;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

/* Fade transition */
.loader-fade-enter-active,
.loader-fade-leave-active {
  transition: all 0.45s cubic-bezier(0.4, 0, 0.2, 1);
}

.loader-fade-leave-to {
  opacity: 0;
  transform: scale(0.92);
}

/* Mobile */
@media (max-width: 768px) {
  .loader-content {
    gap: 2rem;
    width: 100%;
    padding: 0 1rem;
  }

  .notebook-scene {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .notebook {
    width: 320px;
    height: 400px;
  }

  .page {
    padding: 22px 18px;
    left: 0;
    right: 0;
  }

  .spiral {
    display: none;
  }

  .header-title {
    font-size: 1.15rem;
  }

  .task-item {
    padding: 9px 11px;
    gap: 10px;
  }

  .task-name {
    font-size: 0.8rem;
  }

  .task-list {
    gap: 12px;
  }

  .status-bar {
    flex-direction: column;
    gap: 0.8rem;
    width: 100%;
    max-width: 340px;
  }

  .crystal-wrap {
    display: none;
  }

  .status-text-wrap {
    width: 100%;
  }

  .status-text {
    font-size: 0.95rem;
    text-align: center;
    min-width: auto;
    width: 100%;
  }

  .status-mini-progress {
    width: 100%;
  }

  .mini-percent {
    min-width: 50px;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  .app-loader *,
  .app-loader *::before,
  .app-loader *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Performance - GPU */
.notebook,
.crystal {
  will-change: transform;
  transform: translateZ(0);
  backface-visibility: hidden;
}

/* Low-end optimization */
@media (max-width: 768px) and (max-device-pixel-ratio: 1.5) {
  .orb {
    filter: blur(50px);
  }

  .crystal {
    animation-duration: 9s;
  }
}

/* Very low-end devices */
@media (max-device-width: 480px) and (max-device-height: 800px) {
  .orb {
    display: none;
  }

  .spiral-ring {
    animation: none;
  }

  .crystal-face {
    box-shadow: none;
  }

  .crystal {
    animation: crystalSpinSimple 8s linear infinite;
  }
}

@keyframes crystalSpinSimple {
  0% { transform: rotateY(0deg); }
  100% { transform: rotateY(360deg); }
}
</style>
