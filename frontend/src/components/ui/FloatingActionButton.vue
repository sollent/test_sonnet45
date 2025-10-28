<script setup lang="ts">
import { computed } from 'vue'
import Button from 'primevue/button'

interface Props {
  icon?: string
  label?: string
  position?: 'bottom-right' | 'bottom-left' | 'top-right' | 'top-left'
  size?: 'small' | 'medium' | 'large'
}

interface Emits {
  (e: 'click'): void
}

const props = withDefaults(defineProps<Props>(), {
  icon: 'pi pi-plus',
  position: 'bottom-right',
  size: 'large'
})

const emit = defineEmits<Emits>()

const positionClass = computed(() => {
  const positions = {
    'bottom-right': 'fab-bottom-right',
    'bottom-left': 'fab-bottom-left',
    'top-right': 'fab-top-right',
    'top-left': 'fab-top-left'
  }
  return positions[props.position]
})

const sizeClass = computed(() => {
  const sizes = {
    'small': 'fab-small',
    'medium': 'fab-medium',
    'large': 'fab-large'
  }
  return sizes[props.size]
})

function handleClick() {
  emit('click')
}
</script>

<template>
  <div class="fab-container" :class="[positionClass, sizeClass]">
    <Button
      :icon="icon"
      :label="label"
      rounded
      :aria-label="label || 'Action button'"
      class="fab-button"
      @click="handleClick"
    />
  </div>
</template>

<style scoped>
.fab-container {
  position: fixed;
  z-index: 1000;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Positions */
.fab-bottom-right {
  bottom: 2rem;
  right: 2rem;
}

.fab-bottom-left {
  bottom: 2rem;
  left: 2rem;
}

.fab-top-right {
  top: 2rem;
  right: 2rem;
}

.fab-top-left {
  top: 2rem;
  left: 2rem;
}

/* Sizes */
.fab-small :deep(.fab-button) {
  width: 48px;
  height: 48px;
  font-size: 1.25rem;
}

.fab-medium :deep(.fab-button) {
  width: 56px;
  height: 56px;
  font-size: 1.5rem;
}

.fab-large :deep(.fab-button) {
  width: 64px;
  height: 64px;
  font-size: 1.75rem;
}

/* Button Styling */
:deep(.fab-button) {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  border: none !important;
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4) !important;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

:deep(.fab-button:hover) {
  transform: scale(1.1) translateY(-2px) !important;
  box-shadow: 0 12px 32px rgba(102, 126, 234, 0.5) !important;
}

:deep(.fab-button:active) {
  transform: scale(0.95) !important;
}

:deep(.fab-button .p-button-label) {
  font-weight: 600;
  font-size: 0.875rem;
  margin-left: 0.5rem;
}

/* With label */
.fab-container:has(.fab-button .p-button-label:not(:empty)) :deep(.fab-button) {
  width: auto;
  padding: 0 1.5rem;
  min-width: 56px;
  border-radius: 28px;
}

/* Animation on mount */
@keyframes fabIn {
  from {
    transform: scale(0) rotate(-180deg);
    opacity: 0;
  }
  to {
    transform: scale(1) rotate(0deg);
    opacity: 1;
  }
}

.fab-container {
  animation: fabIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Responsive */
@media (max-width: 768px) {
  .fab-bottom-right,
  .fab-bottom-left {
    bottom: 1.5rem;
  }

  .fab-bottom-right {
    right: 1.5rem;
  }

  .fab-bottom-left {
    left: 1.5rem;
  }

  .fab-small :deep(.fab-button) {
    width: 48px;
    height: 48px;
  }

  .fab-medium :deep(.fab-button) {
    width: 52px;
    height: 52px;
  }

  .fab-large :deep(.fab-button) {
    width: 56px;
    height: 56px;
    font-size: 1.5rem;
  }
}

@media (max-width: 480px) {
  .fab-container:has(.fab-button .p-button-label:not(:empty)) :deep(.fab-button .p-button-label) {
    display: none;
  }

  .fab-container:has(.fab-button .p-button-label:not(:empty)) :deep(.fab-button) {
    width: 56px;
    height: 56px;
    padding: 0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .fab-container:has(.fab-button .p-button-label:not(:empty)) :deep(.fab-button .p-button-icon) {
    margin: 0;
  }
}
</style>

