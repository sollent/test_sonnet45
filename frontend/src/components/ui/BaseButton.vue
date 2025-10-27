<script setup lang="ts">
interface Props {
  label?: string
  loading?: boolean
  disabled?: boolean
  variant?: 'primary' | 'secondary' | 'outline' | 'text'
  size?: 'small' | 'medium' | 'large'
  fullWidth?: boolean
  type?: 'button' | 'submit' | 'reset'
}

withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'medium',
  fullWidth: false,
  type: 'button',
  loading: false,
  disabled: false
})

defineEmits<{
  click: [event: MouseEvent]
}>()
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'base-button',
      `base-button--${variant}`,
      `base-button--${size}`,
      {
        'base-button--full-width': fullWidth,
        'base-button--loading': loading
      }
    ]"
    @click="$emit('click', $event)"
  >
    <span v-if="loading" class="base-button__spinner">
      <i class="pi pi-spin pi-spinner"></i>
    </span>
    <span class="base-button__content">
      <slot>{{ label }}</slot>
    </span>
  </button>
</template>

<style scoped>
.base-button {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-weight: 600;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  transition: all var(--transition-base);
  font-family: inherit;
  text-decoration: none;
  outline: none;
}

.base-button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

/* Variants */
.base-button--primary {
  background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-700) 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
}

.base-button--primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(14, 165, 233, 0.4);
  background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
}

.base-button--primary:active:not(:disabled) {
  transform: translateY(0);
}

.base-button--secondary {
  background: linear-gradient(135deg, var(--secondary-600) 0%, var(--secondary-700) 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(168, 85, 247, 0.3);
}

.base-button--secondary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(168, 85, 247, 0.4);
}

.base-button--outline {
  background: transparent;
  color: var(--primary-600);
  border: 2px solid var(--primary-600);
}

.base-button--outline:hover:not(:disabled) {
  background: var(--primary-50);
  transform: translateY(-2px);
}

.base-button--text {
  background: transparent;
  color: var(--primary-600);
  box-shadow: none;
}

.base-button--text:hover:not(:disabled) {
  background: var(--primary-50);
}

/* Sizes */
.base-button--small {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.base-button--medium {
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
}

.base-button--large {
  padding: 1rem 2rem;
  font-size: 1.125rem;
}

/* Full width */
.base-button--full-width {
  width: 100%;
}

/* Loading state */
.base-button--loading {
  pointer-events: none;
}

.base-button__spinner {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
}

.base-button--loading .base-button__content {
  opacity: 0;
}

/* Animations */
@media (prefers-reduced-motion: no-preference) {
  .base-button {
    transition: all var(--transition-base);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .base-button--small {
    padding: 0.4rem 0.875rem;
    font-size: 0.8125rem;
  }

  .base-button--medium {
    padding: 0.625rem 1.25rem;
    font-size: 0.9375rem;
  }

  .base-button--large {
    padding: 0.875rem 1.75rem;
    font-size: 1.0625rem;
  }
}
</style>

