import { onMounted, onUnmounted } from 'vue'

/**
 * SSR-safe composable for scroll animations using IntersectionObserver.
 * Automatically observes elements with animation classes and adds 'visible' class when they enter viewport.
 */
export function useScrollAnimations() {
  let observer: IntersectionObserver | null = null

  const initAnimations = () => {
    // SSR-safe check
    if (typeof window === 'undefined' || typeof document === 'undefined') {
      return
    }

    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible')
          }
        })
      },
      { threshold: 0.1 }
    )

    // Observe all elements with animation classes
    const animatedElements = document.querySelectorAll(
      '.fade-in-up, .fade-in-left, .fade-in-right, .scale-in'
    )

    animatedElements.forEach((el) => {
      observer?.observe(el)
    })
  }

  const destroyAnimations = () => {
    if (observer) {
      observer.disconnect()
      observer = null
    }
  }

  onMounted(() => {
    initAnimations()
  })

  onUnmounted(() => {
    destroyAnimations()
  })

  return {
    initAnimations,
    destroyAnimations
  }
}
