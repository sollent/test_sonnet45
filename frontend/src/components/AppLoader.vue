<script setup lang="ts">
import { ref, onMounted } from 'vue'

const emit = defineEmits<{
  'loaded': []
}>()

const showLoader = ref(true)

onMounted(() => {
  // Hide loader after animation completes
  setTimeout(() => {
    showLoader.value = false
    setTimeout(() => emit('loaded'), 400) // Wait for fade out
  }, 3400)
})
</script>

<template>
  <Transition name="loader-fade">
    <div v-if="showLoader" class="app-loader">
      <div class="loader-content">
        <!-- Animated SVG Icon -->
        <svg class="loader-icon" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
          <!-- Background Circle with Gradient -->
          <defs>
            <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" style="stop-color:#667eea;stop-opacity:1" />
              <stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" />
            </linearGradient>
            <linearGradient id="grad2" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" style="stop-color:#f093fb;stop-opacity:1" />
              <stop offset="100%" style="stop-color:#f5576c;stop-opacity:1" />
            </linearGradient>
            <filter id="glow">
              <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
              <feMerge>
                <feMergeNode in="coloredBlur"/>
                <feMergeNode in="SourceGraphic"/>
              </feMerge>
            </filter>
          </defs>

          <!-- Rotating gradient background -->
          <circle class="loader-bg-circle" cx="100" cy="100" r="80" fill="url(#grad1)" opacity="0.1" />
          
          <!-- Clipboard Background -->
          <g class="clipboard">
            <!-- Clipboard body -->
            <rect x="60" y="50" width="80" height="110" rx="8" fill="white" stroke="url(#grad1)" stroke-width="3" />
            
            <!-- Clipboard clip -->
            <rect x="85" y="45" width="30" height="8" rx="4" fill="url(#grad1)" />
            <circle cx="95" cy="48" r="3" fill="white" />
            <circle cx="105" cy="48" r="3" fill="white" />
          </g>

          <!-- Checklist Items (will animate sequentially) -->
          <g class="checklist">
            <!-- Item 1 -->
            <g class="checklist-item item-1">
              <circle class="checkbox" cx="75" cy="75" r="8" fill="none" stroke="#cbd5e1" stroke-width="2" />
              <polyline class="checkmark" points="71,75 74,78 79,72" fill="none" stroke="url(#grad1)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
              <line class="task-line" x1="90" y1="75" x2="125" y2="75" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" />
            </g>

            <!-- Item 2 -->
            <g class="checklist-item item-2">
              <circle class="checkbox" cx="75" cy="95" r="8" fill="none" stroke="#cbd5e1" stroke-width="2" />
              <polyline class="checkmark" points="71,95 74,98 79,92" fill="none" stroke="url(#grad1)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
              <line class="task-line" x1="90" y1="95" x2="115" y2="95" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" />
            </g>

            <!-- Item 3 -->
            <g class="checklist-item item-3">
              <circle class="checkbox" cx="75" cy="115" r="8" fill="none" stroke="#cbd5e1" stroke-width="2" />
              <polyline class="checkmark" points="71,115 74,118 79,112" fill="none" stroke="url(#grad1)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
              <line class="task-line" x1="90" y1="115" x2="120" y2="115" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" />
            </g>

            <!-- Item 4 -->
            <g class="checklist-item item-4">
              <circle class="checkbox" cx="75" cy="135" r="8" fill="none" stroke="#cbd5e1" stroke-width="2" />
              <polyline class="checkmark" points="71,135 74,138 79,132" fill="none" stroke="url(#grad1)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
              <line class="task-line" x1="90" y1="135" x2="110" y2="135" stroke="#94a3b8" stroke-width="2" stroke-linecap="round" />
            </g>
          </g>

          <!-- Floating particles -->
          <circle class="particle particle-1" cx="40" cy="60" r="3" fill="url(#grad2)" opacity="0.6" />
          <circle class="particle particle-2" cx="160" cy="80" r="2" fill="url(#grad1)" opacity="0.5" />
          <circle class="particle particle-3" cx="45" cy="140" r="2.5" fill="url(#grad2)" opacity="0.7" />
          <circle class="particle particle-4" cx="155" cy="120" r="2" fill="url(#grad1)" opacity="0.6" />
        </svg>

        <!-- Loading Text -->
        <div class="loader-text">
          <span class="letter">З</span>
          <span class="letter">а</span>
          <span class="letter">г</span>
          <span class="letter">р</span>
          <span class="letter">у</span>
          <span class="letter">з</span>
          <span class="letter">к</span>
          <span class="letter">а</span>
          <span class="letter">.</span>
          <span class="letter">.</span>
          <span class="letter">.</span>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.app-loader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 99999;
  overflow: hidden;
}

.loader-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2rem;
}

/* SVG Icon Animations */
.loader-icon {
  width: 200px;
  height: 200px;
  filter: drop-shadow(0 10px 30px rgba(102, 126, 234, 0.2));
}

/* Background circle pulse */
.loader-bg-circle {
  animation: pulse 2s ease-in-out infinite;
  transform-origin: center;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    opacity: 0.1;
  }
  50% {
    transform: scale(1.1);
    opacity: 0.2;
  }
}

/* Clipboard entrance */
.clipboard {
  animation: clipboardDrop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
  transform-origin: center top;
}

@keyframes clipboardDrop {
  0% {
    transform: translateY(-100px) rotate(-10deg);
    opacity: 0;
  }
  60% {
    transform: translateY(5px) rotate(2deg);
  }
  100% {
    transform: translateY(0) rotate(0);
    opacity: 1;
  }
}

/* Checklist items appear sequentially */
.checklist-item {
  opacity: 0;
}

.checklist-item.item-1 {
  animation: itemAppear 0.5s ease-out 0.8s forwards;
}

.checklist-item.item-2 {
  animation: itemAppear 0.5s ease-out 1.2s forwards;
}

.checklist-item.item-3 {
  animation: itemAppear 0.5s ease-out 1.6s forwards;
}

.checklist-item.item-4 {
  animation: itemAppear 0.5s ease-out 2s forwards;
}

@keyframes itemAppear {
  0% {
    opacity: 0;
    transform: translateX(-20px);
  }
  100% {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Checkmarks draw animation */
.checkmark {
  stroke-dasharray: 20;
  stroke-dashoffset: 20;
}

.item-1 .checkmark {
  animation: drawCheck 0.4s ease-out 1.1s forwards;
}

.item-2 .checkmark {
  animation: drawCheck 0.4s ease-out 1.5s forwards;
}

.item-3 .checkmark {
  animation: drawCheck 0.4s ease-out 1.9s forwards;
}

.item-4 .checkmark {
  animation: drawCheck 0.4s ease-out 2.3s forwards;
}

@keyframes drawCheck {
  to {
    stroke-dashoffset: 0;
  }
}

/* Checkbox fill on check */
.checkbox {
  transition: fill 0.3s ease;
}

.item-1 .checkbox {
  animation: fillCheckbox 0.3s ease-out 1.3s forwards;
}

.item-2 .checkbox {
  animation: fillCheckbox 0.3s ease-out 1.7s forwards;
}

.item-3 .checkbox {
  animation: fillCheckbox 0.3s ease-out 2.1s forwards;
}

.item-4 .checkbox {
  animation: fillCheckbox 0.3s ease-out 2.5s forwards;
}

@keyframes fillCheckbox {
  to {
    fill: url(#grad1);
    stroke: url(#grad1);
  }
}

/* Task lines grow */
.task-line {
  stroke-dasharray: 35;
  stroke-dashoffset: 35;
}

.item-1 .task-line {
  animation: drawLine 0.4s ease-out 0.9s forwards;
}

.item-2 .task-line {
  animation: drawLine 0.4s ease-out 1.3s forwards;
}

.item-3 .task-line {
  animation: drawLine 0.4s ease-out 1.7s forwards;
}

.item-4 .task-line {
  animation: drawLine 0.4s ease-out 2.1s forwards;
}

@keyframes drawLine {
  to {
    stroke-dashoffset: 0;
  }
}

/* Floating particles */
.particle {
  animation: float 3s ease-in-out infinite;
}

.particle-1 {
  animation: float 2.5s ease-in-out infinite;
}

.particle-2 {
  animation: float 3s ease-in-out 0.5s infinite;
}

.particle-3 {
  animation: float 2.8s ease-in-out 1s infinite;
}

.particle-4 {
  animation: float 3.2s ease-in-out 1.5s infinite;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0) translateX(0);
    opacity: 0.6;
  }
  25% {
    transform: translateY(-15px) translateX(5px);
    opacity: 0.8;
  }
  50% {
    transform: translateY(-8px) translateX(-5px);
    opacity: 1;
  }
  75% {
    transform: translateY(-20px) translateX(3px);
    opacity: 0.7;
  }
}

/* Loading Text Animation */
.loader-text {
  display: flex;
  gap: 0.2rem;
  font-size: 1.25rem;
  font-weight: 600;
  color: #667eea;
}

.letter {
  display: inline-block;
  animation: letterBounce 1.5s ease-in-out infinite;
  opacity: 0.7;
}

.letter:nth-child(1) { animation-delay: 0s; }
.letter:nth-child(2) { animation-delay: 0.1s; }
.letter:nth-child(3) { animation-delay: 0.2s; }
.letter:nth-child(4) { animation-delay: 0.3s; }
.letter:nth-child(5) { animation-delay: 0.4s; }
.letter:nth-child(6) { animation-delay: 0.5s; }
.letter:nth-child(7) { animation-delay: 0.6s; }
.letter:nth-child(8) { animation-delay: 0.7s; }
.letter:nth-child(9) { animation-delay: 0.8s; }
.letter:nth-child(10) { animation-delay: 0.9s; }
.letter:nth-child(11) { animation-delay: 1s; }

@keyframes letterBounce {
  0%, 60%, 100% {
    transform: translateY(0);
    opacity: 0.7;
  }
  30% {
    transform: translateY(-10px);
    opacity: 1;
  }
}

/* Fade transition */
.loader-fade-enter-active,
.loader-fade-leave-active {
  transition: opacity 0.4s ease;
}

.loader-fade-enter-from,
.loader-fade-leave-to {
  opacity: 0;
}

/* Final success pulse */
@keyframes successPulse {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
  100% {
    transform: scale(1);
  }
}

.checklist {
  animation: successPulse 0.6s ease-in-out 2.8s;
}

/* Mobile optimizations */
@media (max-width: 768px) {
  .loader-icon {
    width: 160px;
    height: 160px;
  }

  .loader-text {
    font-size: 1rem;
  }
}

/* Reduce motion for accessibility */
@media (prefers-reduced-motion: reduce) {
  .app-loader * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>


