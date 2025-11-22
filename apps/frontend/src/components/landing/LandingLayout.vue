<template>
  <div class="landing-layout">
    <!-- Navigation -->
    <nav class="landing-nav" :class="{ 'scrolled': isScrolled }">
      <div class="container">
        <div class="nav-wrapper">
          <!-- Logo -->
          <router-link to="/" class="logo">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
              <rect width="40" height="40" rx="10" fill="url(#logo-gradient)"/>
              <path d="M12 15h16M12 20h10M12 25h14" stroke="white" stroke-width="2" stroke-linecap="round"/>
              <defs>
                <linearGradient id="logo-gradient" x1="0" y1="0" x2="40" y2="40">
                  <stop offset="0%" stop-color="#667eea"/>
                  <stop offset="100%" stop-color="#764ba2"/>
                </linearGradient>
              </defs>
            </svg>
            <span class="logo-text">TaskFlow</span>
          </router-link>

          <!-- Desktop Navigation -->
          <div class="nav-menu hide-mobile">
            <div class="nav-item dropdown">
              <span class="nav-link">Возможности <i class="pi pi-chevron-down"></i></span>
              <div class="dropdown-menu">
                <router-link to="/features/voice-control" class="dropdown-item">
                  <i class="pi pi-microphone"></i> Голосовое управление
                </router-link>
                <router-link to="/features/telegram" class="dropdown-item">
                  <i class="pi pi-send"></i> Telegram интеграция
                </router-link>
                <router-link to="/features/ai-assistant" class="dropdown-item">
                  <i class="pi pi-sparkles"></i> AI-помощник
                </router-link>
                <router-link to="/features/file-processing" class="dropdown-item">
                  <i class="pi pi-file"></i> Обработка файлов
                </router-link>
                <router-link to="/features/smart-reminders" class="dropdown-item">
                  <i class="pi pi-bell"></i> Умные напоминания
                </router-link>
                <router-link to="/features/web-interface" class="dropdown-item">
                  <i class="pi pi-desktop"></i> Веб-интерфейс
                </router-link>
                <router-link to="/features/web-search" class="dropdown-item">
                  <i class="pi pi-search"></i> Поиск в интернете
                </router-link>
              </div>
            </div>

            <div class="nav-item dropdown">
              <span class="nav-link">Сравнение <i class="pi pi-chevron-down"></i></span>
              <div class="dropdown-menu">
                <router-link to="/compare/todoist" class="dropdown-item">vs Todoist</router-link>
                <router-link to="/compare/ticktick" class="dropdown-item">vs TickTick</router-link>
                <router-link to="/compare/anydo" class="dropdown-item">vs Any.do</router-link>
                <router-link to="/compare/google-keep" class="dropdown-item">vs Google Keep</router-link>
                <router-link to="/compare/things3" class="dropdown-item">vs Things 3</router-link>
              </div>
            </div>

            <router-link to="/#pricing" class="nav-link">Тарифы</router-link>
            <router-link to="/about" class="nav-link">О нас</router-link>
          </div>

          <!-- CTA Buttons -->
          <div class="nav-actions">
            <router-link to="/login" class="btn-secondary hide-mobile">Войти</router-link>
            <router-link to="/register" class="btn-primary">Начать бесплатно</router-link>

            <!-- Mobile menu toggle -->
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="mobile-menu-toggle hide-desktop">
              <i class="pi" :class="mobileMenuOpen ? 'pi-times' : 'pi-bars'"></i>
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Mobile Menu -->
    <div class="mobile-menu" :class="{ 'open': mobileMenuOpen }">
      <div class="mobile-menu-content">
        <div class="mobile-menu-section">
          <h4>Возможности</h4>
          <router-link to="/features/voice-control" @click="mobileMenuOpen = false">
            <i class="pi pi-microphone"></i> Голосовое управление
          </router-link>
          <router-link to="/features/telegram" @click="mobileMenuOpen = false">
            <i class="pi pi-send"></i> Telegram интеграция
          </router-link>
          <router-link to="/features/ai-assistant" @click="mobileMenuOpen = false">
            <i class="pi pi-sparkles"></i> AI-помощник
          </router-link>
          <router-link to="/features/file-processing" @click="mobileMenuOpen = false">
            <i class="pi pi-file"></i> Обработка файлов
          </router-link>
          <router-link to="/features/smart-reminders" @click="mobileMenuOpen = false">
            <i class="pi pi-bell"></i> Умные напоминания
          </router-link>
          <router-link to="/features/web-interface" @click="mobileMenuOpen = false">
            <i class="pi pi-desktop"></i> Веб-интерфейс
          </router-link>
          <router-link to="/features/web-search" @click="mobileMenuOpen = false">
            <i class="pi pi-search"></i> Поиск в интернете
          </router-link>
        </div>

        <div class="mobile-menu-section">
          <h4>Сравнение</h4>
          <router-link to="/compare/todoist" @click="mobileMenuOpen = false">vs Todoist</router-link>
          <router-link to="/compare/ticktick" @click="mobileMenuOpen = false">vs TickTick</router-link>
          <router-link to="/compare/anydo" @click="mobileMenuOpen = false">vs Any.do</router-link>
          <router-link to="/compare/google-keep" @click="mobileMenuOpen = false">vs Google Keep</router-link>
          <router-link to="/compare/things3" @click="mobileMenuOpen = false">vs Things 3</router-link>
        </div>

        <div class="mobile-menu-section">
          <router-link to="/#pricing" @click="mobileMenuOpen = false">Тарифы</router-link>
          <router-link to="/about" @click="mobileMenuOpen = false">О нас</router-link>
        </div>

        <div class="mobile-menu-actions">
          <router-link to="/login" class="btn-secondary" @click="mobileMenuOpen = false">Войти</router-link>
          <router-link to="/register" class="btn-primary" @click="mobileMenuOpen = false">
            Начать бесплатно
          </router-link>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <main class="landing-content">
      <slot></slot>
    </main>

    <!-- Footer -->
    <footer class="landing-footer">
      <div class="footer-wave">
        <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
          <path d="M0,64 C320,150 700,0 1440,64 L1440,120 L0,120 Z" fill="var(--gray-900)"/>
        </svg>
      </div>

      <div class="footer-content">
        <div class="container">
          <div class="footer-grid">
            <!-- Company info -->
            <div class="footer-column">
              <div class="footer-logo">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
                  <rect width="40" height="40" rx="10" fill="url(#footer-gradient)"/>
                  <path d="M12 15h16M12 20h10M12 25h14" stroke="white" stroke-width="2" stroke-linecap="round"/>
                  <defs>
                    <linearGradient id="footer-gradient" x1="0" y1="0" x2="40" y2="40">
                      <stop offset="0%" stop-color="#667eea"/>
                      <stop offset="100%" stop-color="#764ba2"/>
                    </linearGradient>
                  </defs>
                </svg>
                <span>TaskFlow</span>
              </div>
              <p class="footer-description">
                Умное управление задачами с AI и голосовым управлением.
                Организуйте свою жизнь проще и эффективнее.
              </p>
              <div class="social-links">
                <a href="#" aria-label="Twitter"><i class="pi pi-twitter"></i></a>
                <a href="#" aria-label="Facebook"><i class="pi pi-facebook"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="pi pi-linkedin"></i></a>
                <a href="#" aria-label="GitHub"><i class="pi pi-github"></i></a>
              </div>
            </div>

            <!-- Features -->
            <div class="footer-column">
              <h4>Возможности</h4>
              <ul>
                <li><router-link to="/features/voice-control">Голосовое управление</router-link></li>
                <li><router-link to="/features/telegram">Telegram интеграция</router-link></li>
                <li><router-link to="/features/ai-assistant">AI-помощник</router-link></li>
                <li><router-link to="/features/file-processing">Обработка файлов</router-link></li>
                <li><router-link to="/features/smart-reminders">Умные напоминания</router-link></li>
                <li><router-link to="/features/web-interface">Веб-интерфейс</router-link></li>
                <li><router-link to="/features/web-search">Поиск в интернете</router-link></li>
              </ul>
            </div>

            <!-- Compare -->
            <div class="footer-column">
              <h4>Сравнение</h4>
              <ul>
                <li><router-link to="/compare/todoist">TaskFlow vs Todoist</router-link></li>
                <li><router-link to="/compare/ticktick">TaskFlow vs TickTick</router-link></li>
                <li><router-link to="/compare/anydo">TaskFlow vs Any.do</router-link></li>
                <li><router-link to="/compare/google-keep">TaskFlow vs Google Keep</router-link></li>
                <li><router-link to="/compare/things3">TaskFlow vs Things 3</router-link></li>
              </ul>
              <h4 class="mt-4">Альтернативы</h4>
              <ul>
                <li><router-link to="/alternatives/todoist">Альтернативы Todoist</router-link></li>
                <li><router-link to="/alternatives/google-keep">Альтернативы Google Keep</router-link></li>
              </ul>
            </div>

            <!-- Resources -->
            <div class="footer-column">
              <h4>Ресурсы</h4>
              <ul>
                <li><router-link to="/about">О нас</router-link></li>
                <li><router-link to="/#pricing">Тарифы</router-link></li>
                <li><a href="#">Блог</a></li>
                <li><a href="#">API документация</a></li>
                <li><a href="#">Поддержка</a></li>
                <li><a href="#">Политика конфиденциальности</a></li>
                <li><a href="#">Условия использования</a></li>
              </ul>
            </div>
          </div>

          <div class="footer-bottom">
            <p>&copy; 2025 TaskFlow. Все права защищены.</p>
            <p>Сделано с <span class="heart">❤️</span> для повышения продуктивности</p>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

// Scroll state
const isScrolled = ref(false)
const mobileMenuOpen = ref(false)

// Handle scroll
const handleScroll = () => {
  isScrolled.value = window.scrollY > 50
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<style scoped lang="scss">
@import '@/styles/landing.scss';

.landing-layout {
  min-height: 100vh;
  background: var(--gray-50);
}

// Navigation styles
.landing-nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;

  &.scrolled {
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
  }

  .container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1rem;
  }

  .nav-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 0;
  }

  .logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;

    .logo-text {
      font-size: 1.5rem;
      font-weight: 800;
      background: var(--gradient-primary);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
  }

  .nav-menu {
    display: flex;
    gap: 2rem;
    align-items: center;
  }

  .nav-item {
    position: relative;

    &.dropdown {
      .nav-link {
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.25rem;

        i {
          font-size: 0.75rem;
          transition: transform 0.3s ease;
        }
      }

      &:hover {
        .nav-link i {
          transform: rotate(180deg);
        }

        .dropdown-menu {
          opacity: 1;
          visibility: visible;
          transform: translateY(0);
        }
      }
    }
  }

  .nav-link {
    color: var(--gray-700);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;

    &:hover {
      color: var(--primary);
    }
  }

  .dropdown-menu {
    position: absolute;
    top: 100%;
    left: -20px;
    margin-top: 1rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    padding: 1rem;
    min-width: 250px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;

    .dropdown-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem 1rem;
      color: var(--gray-700);
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.3s ease;

      i {
        color: var(--primary);
      }

      &:hover {
        background: var(--gray-50);
        color: var(--primary);
        transform: translateX(5px);
      }
    }
  }

  .nav-actions {
    display: flex;
    gap: 1rem;
    align-items: center;

    .btn-primary,
    .btn-secondary {
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
      color: white !important;
      padding: 0.75rem 1.5rem;
      border-radius: 50px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);
      }
    }

    .btn-secondary {
      color: var(--primary) !important;
      padding: 0.75rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s ease;

      &:hover {
        color: var(--primary-dark) !important;
      }
    }
  }

  .mobile-menu-toggle {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--gray-700);
    cursor: pointer;
  }
}

// Mobile menu
.mobile-menu {
  position: fixed;
  top: 70px;
  left: 0;
  right: 0;
  bottom: 0;
  background: white;
  z-index: 999;
  transform: translateX(100%);
  transition: transform 0.3s ease;

  &.open {
    transform: translateX(0);
  }

  .mobile-menu-content {
    padding: 2rem;
    overflow-y: auto;
    height: 100%;
  }

  .mobile-menu-section {
    margin-bottom: 2rem;

    h4 {
      color: var(--gray-500);
      font-size: 0.875rem;
      text-transform: uppercase;
      margin-bottom: 1rem;
    }

    a {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem 0;
      color: var(--gray-700);
      text-decoration: none;
      transition: color 0.3s ease;

      i {
        color: var(--primary);
      }

      &:hover {
        color: var(--primary);
      }
    }
  }

  .mobile-menu-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding-top: 2rem;
    border-top: 1px solid var(--gray-200);

    .btn-primary,
    .btn-secondary {
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      border-radius: 50px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
      color: white !important;
      box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
    }

    .btn-secondary {
      background: transparent;
      color: var(--primary) !important;
      border: 2px solid var(--primary);
    }
  }
}

// Main content
.landing-content {
  padding-top: 70px; // Account for fixed nav
  min-height: 100vh;
}

// Footer
.landing-footer {
  position: relative;
  margin-top: 8rem;

  .footer-wave {
    position: relative;
    height: 120px;
    overflow: hidden;

    svg {
      position: absolute;
      bottom: 0;
      width: 100%;
      height: 100%;
    }
  }

  .footer-content {
    background: var(--gray-900);
    color: var(--gray-400);
    padding: 4rem 0 2rem;
  }

  .container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1rem;
  }

  .footer-grid {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 4rem;
    margin-bottom: 3rem;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
      gap: 2rem;
    }
  }

  .footer-column {
    h4 {
      color: white;
      font-size: 1rem;
      margin-bottom: 1rem;
      font-weight: 600;
    }

    ul {
      list-style: none;
      padding: 0;

      li {
        margin-bottom: 0.75rem;

        a {
          color: var(--gray-400);
          text-decoration: none;
          transition: color 0.3s ease;

          &:hover {
            color: white;
          }
        }
      }
    }
  }

  .footer-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;

    span {
      font-size: 1.5rem;
      font-weight: 800;
      color: white;
    }
  }

  .footer-description {
    margin-bottom: 1.5rem;
    line-height: 1.6;
  }

  .social-links {
    display: flex;
    gap: 1rem;

    a {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      color: var(--gray-400);
      transition: all 0.3s ease;

      &:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-3px);
      }
    }
  }

  .footer-bottom {
    text-align: center;
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);

    .heart {
      color: var(--danger);
      animation: pulse 1s ease infinite;
    }
  }
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.2); }
}

.mt-4 {
  margin-top: 1rem;
}
</style>