<template>
  <Transition name="offline-modal" @enter="onEnter" @leave="onLeave">
    <div
      v-if="isVisible"
      class="offline-modal-overlay"
      @click.self="close"
    >
      <div class="offline-modal-container" ref="modalContainer">
        <!-- Animated Background Layers -->
        <div class="bg-layer bg-layer-1"></div>
        <div class="bg-layer bg-layer-2"></div>
        <div class="bg-layer bg-layer-3"></div>

        <!-- Floating Particles -->
        <div class="particles">
          <div v-for="i in 20" :key="i" class="particle" :style="getParticleStyle(i)"></div>
        </div>

        <!-- Main Content -->
        <div class="offline-modal-content">
          <!-- Animated Icon -->
          <div class="icon-container">
            <!-- Pulse Rings -->
            <div class="pulse-ring pulse-ring-1"></div>
            <div class="pulse-ring pulse-ring-2"></div>
            <div class="pulse-ring pulse-ring-3"></div>

            <!-- Main Icon with 3D rotation -->
            <div class="icon-wrapper">
              <div class="icon-3d">
                <!-- WiFi Signal Animation -->
                <svg class="wifi-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                  <!-- Base -->
                  <circle cx="50" cy="75" r="5" class="wifi-dot"/>

                  <!-- Signal Waves -->
                  <path d="M 30 55 Q 50 40 70 55" class="wifi-wave wifi-wave-1"/>
                  <path d="M 20 45 Q 50 25 80 45" class="wifi-wave wifi-wave-2"/>
                  <path d="M 10 35 Q 50 10 90 35" class="wifi-wave wifi-wave-3"/>

                  <!-- Slash for offline -->
                  <line x1="20" y1="80" x2="80" y2="20" class="wifi-slash"/>
                </svg>

                <!-- Orbiting Elements -->
                <div class="orbit-container">
                  <div class="orbit orbit-1">
                    <div class="orbit-dot"></div>
                  </div>
                  <div class="orbit orbit-2">
                    <div class="orbit-dot"></div>
                  </div>
                  <div class="orbit orbit-3">
                    <div class="orbit-dot"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Glitch Effect Text -->
            <div class="glitch-container">
              <span class="glitch-text" data-text="OFFLINE">OFFLINE</span>
            </div>
          </div>

          <!-- Message -->
          <h2 class="offline-title">{{ t('offline.modal.title') }}</h2>
          <p class="offline-message">{{ t('offline.modal.message') }}</p>

          <!-- Animated Button -->
          <button
            class="offline-button"
            @click="close"
            @mouseenter="onButtonHover"
            @mouseleave="onButtonLeave"
          >
            <span class="button-text">{{ t('offline.modal.button') }}</span>
            <div class="button-bg"></div>
            <div class="button-glow"></div>
          </button>

          <!-- Network Status Indicator -->
          <div class="status-indicator">
            <div class="status-dot" :class="{ 'online': isOnline }"></div>
            <span class="status-text">{{ isOnline ? t('offline.modal.statusOnline') : t('offline.modal.statusOffline') }}</span>
          </div>
        </div>

        <!-- Decorative Elements -->
        <div class="corner-decoration corner-tl">
          <svg viewBox="0 0 100 100">
            <path d="M 0 0 L 50 0 Q 0 0 0 50 Z" fill="url(#gradient-tl)"/>
          </svg>
        </div>
        <div class="corner-decoration corner-br">
          <svg viewBox="0 0 100 100">
            <path d="M 100 100 L 50 100 Q 100 100 100 50 Z" fill="url(#gradient-br)"/>
          </svg>
        </div>

        <!-- SVG Definitions -->
        <svg width="0" height="0">
          <defs>
            <linearGradient id="gradient-tl" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" style="stop-color:#3B82F6;stop-opacity:0.3" />
              <stop offset="100%" style="stop-color:#8B5CF6;stop-opacity:0" />
            </linearGradient>
            <linearGradient id="gradient-br" x1="100%" y1="100%" x2="0%" y2="0%">
              <stop offset="0%" style="stop-color:#8B5CF6;stop-opacity:0.3" />
              <stop offset="100%" style="stop-color:#3B82F6;stop-opacity:0" />
            </linearGradient>
          </defs>
        </svg>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'

interface Props {
  modelValue: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const modalContainer = ref<HTMLElement>()
const isOnline = ref(navigator.onLine)

// Visibility control
const isVisible = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value)
})

// Translations
const { t } = useI18n()

// Close modal
const close = () => {
  isVisible.value = false
}

// Particle styles generator
const getParticleStyle = (index: number) => {
  const size = Math.random() * 4 + 2
  const duration = Math.random() * 20 + 10
  const delay = Math.random() * 5
  const startX = Math.random() * 100
  const endX = startX + (Math.random() * 40 - 20)

  return {
    width: `${size}px`,
    height: `${size}px`,
    left: `${startX}%`,
    animationDuration: `${duration}s`,
    animationDelay: `${delay}s`,
    '--end-x': `${endX}%`
  }
}

// Animation callbacks
const onEnter = (el: Element) => {
  if (modalContainer.value) {
    // Add entrance animation class
    modalContainer.value.classList.add('entering')
    setTimeout(() => {
      modalContainer.value?.classList.remove('entering')
    }, 1000)
  }
}

const onLeave = (el: Element) => {
  if (modalContainer.value) {
    modalContainer.value.classList.add('leaving')
  }
}

// Button hover effects
const onButtonHover = (e: MouseEvent) => {
  const button = e.currentTarget as HTMLElement
  button.classList.add('hover')
}

const onButtonLeave = (e: MouseEvent) => {
  const button = e.currentTarget as HTMLElement
  button.classList.remove('hover')
}

// Network status monitoring
const updateOnlineStatus = () => {
  isOnline.value = navigator.onLine
}

onMounted(() => {
  window.addEventListener('online', updateOnlineStatus)
  window.addEventListener('offline', updateOnlineStatus)
})

onUnmounted(() => {
  window.removeEventListener('online', updateOnlineStatus)
  window.removeEventListener('offline', updateOnlineStatus)
})
</script>

<style scoped>
/* Main Container */
.offline-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  overflow: hidden;
}

.offline-modal-container {
  position: relative;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  border-radius: 24px;
  padding: 48px;
  max-width: 500px;
  width: 100%;
  box-shadow:
    0 30px 60px rgba(59, 130, 246, 0.3),
    0 20px 40px rgba(139, 92, 246, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.2);
  transform-style: preserve-3d;
  perspective: 1000px;
  overflow: hidden;
}

/* Animated Background Layers */
.bg-layer {
  position: absolute;
  inset: 0;
  opacity: 0.1;
  pointer-events: none;
}

.bg-layer-1 {
  background: radial-gradient(circle at 20% 50%, #3B82F6 0%, transparent 70%);
  animation: bgFloat1 15s ease-in-out infinite;
}

.bg-layer-2 {
  background: radial-gradient(circle at 80% 50%, #8B5CF6 0%, transparent 70%);
  animation: bgFloat2 20s ease-in-out infinite;
}

.bg-layer-3 {
  background: radial-gradient(circle at 50% 50%, #06B6D4 0%, transparent 70%);
  animation: bgFloat3 25s ease-in-out infinite;
}

@keyframes bgFloat1 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(30px, -30px) scale(1.1); }
  66% { transform: translate(-30px, 20px) scale(0.9); }
}

@keyframes bgFloat2 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(-40px, 20px) scale(1.2); }
  66% { transform: translate(20px, -40px) scale(0.8); }
}

@keyframes bgFloat3 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(0, -50px) scale(1.3); }
}

/* Floating Particles */
.particles {
  position: absolute;
  inset: 0;
  pointer-events: none;
  overflow: hidden;
}

.particle {
  position: absolute;
  background: linear-gradient(135deg, #3B82F6, #8B5CF6);
  border-radius: 50%;
  opacity: 0;
  animation: particleFloat 20s linear infinite;
}

@keyframes particleFloat {
  0% {
    opacity: 0;
    transform: translateY(100%) translateX(0);
  }
  10% {
    opacity: 0.8;
  }
  90% {
    opacity: 0.8;
  }
  100% {
    opacity: 0;
    transform: translateY(-100vh) translateX(var(--end-x));
  }
}

/* Content */
.offline-modal-content {
  position: relative;
  z-index: 1;
  text-align: center;
}

/* Icon Container */
.icon-container {
  position: relative;
  width: 150px;
  height: 150px;
  margin: 0 auto 32px;
}

/* Pulse Rings */
.pulse-ring {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 100%;
  height: 100%;
  border: 2px solid #3B82F6;
  border-radius: 50%;
  transform: translate(-50%, -50%);
  opacity: 0;
}

.pulse-ring-1 {
  animation: pulseRing 3s ease-out infinite;
}

.pulse-ring-2 {
  animation: pulseRing 3s ease-out infinite 1s;
}

.pulse-ring-3 {
  animation: pulseRing 3s ease-out infinite 2s;
}

@keyframes pulseRing {
  0% {
    transform: translate(-50%, -50%) scale(0.5);
    opacity: 1;
  }
  100% {
    transform: translate(-50%, -50%) scale(2);
    opacity: 0;
  }
}

/* 3D Icon */
.icon-wrapper {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100px;
  height: 100px;
}

.icon-3d {
  width: 100%;
  height: 100%;
  position: relative;
  transform-style: preserve-3d;
  animation: icon3dRotate 10s linear infinite;
}

@keyframes icon3dRotate {
  0% { transform: rotateY(0deg) rotateX(0deg); }
  50% { transform: rotateY(180deg) rotateX(10deg); }
  100% { transform: rotateY(360deg) rotateX(0deg); }
}

/* WiFi Icon */
.wifi-icon {
  width: 100%;
  height: 100%;
  filter: drop-shadow(0 0 20px rgba(59, 130, 246, 0.8));
}

.wifi-dot {
  fill: #3B82F6;
  animation: wifiDotPulse 2s ease-in-out infinite;
}

@keyframes wifiDotPulse {
  0%, 100% { r: 5; opacity: 1; }
  50% { r: 8; opacity: 0.5; }
}

.wifi-wave {
  stroke: #3B82F6;
  stroke-width: 3;
  fill: none;
  opacity: 0.3;
}

.wifi-wave-1 {
  animation: wifiWave 2s ease-in-out infinite;
}

.wifi-wave-2 {
  animation: wifiWave 2s ease-in-out infinite 0.3s;
}

.wifi-wave-3 {
  animation: wifiWave 2s ease-in-out infinite 0.6s;
}

@keyframes wifiWave {
  0%, 100% { opacity: 0.3; stroke-width: 3; }
  50% { opacity: 1; stroke-width: 4; }
}

.wifi-slash {
  stroke: #EF4444;
  stroke-width: 4;
  stroke-linecap: round;
  animation: slashBlink 1s ease-in-out infinite;
}

@keyframes slashBlink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}

/* Orbiting Elements */
.orbit-container {
  position: absolute;
  inset: -25%;
  pointer-events: none;
}

.orbit {
  position: absolute;
  inset: 0;
  border: 1px dashed rgba(59, 130, 246, 0.2);
  border-radius: 50%;
}

.orbit-1 {
  animation: orbit1 8s linear infinite;
}

.orbit-2 {
  animation: orbit2 12s linear infinite reverse;
}

.orbit-3 {
  animation: orbit3 15s linear infinite;
}

.orbit-dot {
  position: absolute;
  width: 8px;
  height: 8px;
  background: linear-gradient(135deg, #3B82F6, #8B5CF6);
  border-radius: 50%;
  top: -4px;
  left: 50%;
  transform: translateX(-50%);
  box-shadow: 0 0 10px rgba(59, 130, 246, 0.8);
}

@keyframes orbit1 {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes orbit2 {
  0% { transform: rotate(0deg) scale(1.2); }
  100% { transform: rotate(360deg) scale(1.2); }
}

@keyframes orbit3 {
  0% { transform: rotate(0deg) scale(1.4); }
  100% { transform: rotate(360deg) scale(1.4); }
}

/* Glitch Text */
.glitch-container {
  margin-top: 16px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.glitch-text {
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 3px;
  color: #3B82F6;
  position: relative;
  animation: glitchText 2s ease-in-out infinite;
}

.glitch-text::before,
.glitch-text::after {
  content: attr(data-text);
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.glitch-text::before {
  animation: glitch1 0.5s ease-in-out infinite;
  color: #EF4444;
  z-index: -1;
}

.glitch-text::after {
  animation: glitch2 0.5s ease-in-out infinite;
  color: #06B6D4;
  z-index: -1;
}

@keyframes glitchText {
  0%, 100% { opacity: 1; }
  95% { opacity: 0.9; }
}

@keyframes glitch1 {
  0%, 100% { clip-path: inset(0 0 0 0); transform: translate(0); }
  20% { clip-path: inset(20% 0 30% 0); transform: translate(-2px, 2px); }
  40% { clip-path: inset(50% 0 20% 0); transform: translate(2px, -2px); }
  60% { clip-path: inset(10% 0 60% 0); transform: translate(-1px, 1px); }
  80% { clip-path: inset(80% 0 5% 0); transform: translate(1px, -1px); }
}

@keyframes glitch2 {
  0%, 100% { clip-path: inset(0 0 0 0); transform: translate(0); }
  20% { clip-path: inset(70% 0 10% 0); transform: translate(2px, -1px); }
  40% { clip-path: inset(20% 0 50% 0); transform: translate(-2px, 1px); }
  60% { clip-path: inset(60% 0 20% 0); transform: translate(1px, -2px); }
  80% { clip-path: inset(5% 0 80% 0); transform: translate(-1px, 2px); }
}

/* Text Content */
.offline-title {
  font-size: 28px;
  font-weight: 700;
  color: #fff;
  margin: 24px 0 12px;
  background: linear-gradient(135deg, #3B82F6, #8B5CF6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.offline-message {
  font-size: 16px;
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 32px;
  line-height: 1.5;
}

/* Button */
.offline-button {
  position: relative;
  padding: 14px 32px;
  background: transparent;
  border: 2px solid #3B82F6;
  border-radius: 12px;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  overflow: hidden;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.button-text {
  position: relative;
  z-index: 2;
}

.button-bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #3B82F6, #8B5CF6);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.button-glow {
  position: absolute;
  inset: -2px;
  background: linear-gradient(135deg, #3B82F6, #8B5CF6);
  border-radius: 12px;
  opacity: 0;
  filter: blur(10px);
  transition: opacity 0.3s ease;
  z-index: -1;
}

.offline-button.hover .button-bg {
  opacity: 1;
}

.offline-button.hover .button-glow {
  opacity: 0.5;
}

.offline-button:active {
  transform: scale(0.98);
}

/* Status Indicator */
.status-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 24px;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.5);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #EF4444;
  animation: statusPulse 2s ease-in-out infinite;
}

.status-dot.online {
  background: #10B981;
}

@keyframes statusPulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(1.2); }
}

/* Corner Decorations */
.corner-decoration {
  position: absolute;
  width: 100px;
  height: 100px;
  pointer-events: none;
}

.corner-tl {
  top: 0;
  left: 0;
  animation: cornerFloat1 10s ease-in-out infinite;
}

.corner-br {
  bottom: 0;
  right: 0;
  animation: cornerFloat2 10s ease-in-out infinite;
}

@keyframes cornerFloat1 {
  0%, 100% { transform: translate(0, 0) rotate(0deg); }
  50% { transform: translate(10px, 10px) rotate(90deg); }
}

@keyframes cornerFloat2 {
  0%, 100% { transform: translate(0, 0) rotate(0deg); }
  50% { transform: translate(-10px, -10px) rotate(-90deg); }
}

/* Modal Transitions */
.offline-modal-enter-active,
.offline-modal-leave-active {
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.offline-modal-enter-from {
  opacity: 0;
}

.offline-modal-leave-to {
  opacity: 0;
}

.offline-modal-enter-active .offline-modal-container {
  animation: modalBounceIn 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.offline-modal-leave-active .offline-modal-container {
  animation: modalBounceOut 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

@keyframes modalBounceIn {
  0% {
    opacity: 0;
    transform: scale(0.7) translateY(30px) rotateX(-30deg);
  }
  50% {
    transform: scale(1.05) translateY(-10px) rotateX(10deg);
  }
  100% {
    opacity: 1;
    transform: scale(1) translateY(0) rotateX(0);
  }
}

@keyframes modalBounceOut {
  0% {
    opacity: 1;
    transform: scale(1) translateY(0) rotateX(0);
  }
  100% {
    opacity: 0;
    transform: scale(0.7) translateY(30px) rotateX(-30deg);
  }
}

/* Special Effects on Enter */
.offline-modal-container.entering {
  animation: containerShake 0.5s ease-out;
}

@keyframes containerShake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
  20%, 40%, 60%, 80% { transform: translateX(2px); }
}

/* Mobile Responsive */
@media (max-width: 640px) {
  .offline-modal-container {
    padding: 32px 24px;
  }

  .icon-container {
    width: 120px;
    height: 120px;
  }

  .icon-wrapper {
    width: 80px;
    height: 80px;
  }

  .offline-title {
    font-size: 24px;
  }

  .offline-message {
    font-size: 14px;
  }

  .offline-button {
    padding: 12px 24px;
    font-size: 14px;
  }
}

/* Performance Optimizations */
@media (prefers-reduced-motion: reduce) {
  .bg-layer,
  .particle,
  .pulse-ring,
  .icon-3d,
  .wifi-wave,
  .wifi-dot,
  .wifi-slash,
  .orbit,
  .glitch-text::before,
  .glitch-text::after,
  .status-dot,
  .corner-decoration {
    animation: none !important;
  }

  .offline-modal-enter-active,
  .offline-modal-leave-active {
    transition: opacity 0.3s ease;
  }

  .offline-modal-container {
    animation: none !important;
  }
}
</style>