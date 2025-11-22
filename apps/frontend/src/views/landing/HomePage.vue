<template>
  <LandingLayout>
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="blob-shape blob-1"></div>
      <div class="blob-shape blob-2"></div>

      <div class="container">
        <div class="hero-content">
          <div class="hero-text fade-in-up">
            <div class="hero-badge">
              <span class="badge-icon">🚀</span>
              <span>Новая эра продуктивности с AI</span>
            </div>

            <h1 class="hero-title">
              Управляйте задачами <br>
              <span class="gradient-text">голосом и AI</span>
            </h1>

            <p class="hero-subtitle">
              TaskFlow — умный помощник, который понимает вас с полуслова.
              Просто скажите, что нужно сделать, и мы организуем всё остальное.
            </p>

            <div class="hero-actions">
              <button class="btn-primary btn-large" @click="startDemo">
                <i class="pi pi-play"></i>
                Попробовать бесплатно
              </button>
              <button class="btn-glass btn-large" @click="watchDemo">
                <i class="pi pi-video"></i>
                Смотреть демо
              </button>
            </div>

            <div class="hero-stats">
              <div class="stat-item">
                <span class="stat-number">10K+</span>
                <span class="stat-label">Активных пользователей</span>
              </div>
              <div class="stat-item">
                <span class="stat-number">1M+</span>
                <span class="stat-label">Выполненных задач</span>
              </div>
              <div class="stat-item">
                <span class="stat-number">4.9⭐</span>
                <span class="stat-label">Рейтинг</span>
              </div>
            </div>
          </div>

          <div class="hero-visual fade-in-right">
            <div class="app-preview">
              <!-- Main app window -->
              <div class="app-window glass-morphism">
                <div class="app-header">
                  <div class="app-dots">
                    <span class="dot red"></span>
                    <span class="dot yellow"></span>
                    <span class="dot green"></span>
                  </div>
                  <span class="app-title">TaskFlow</span>
                </div>
                <div class="app-content">
                  <div class="task-list">
                    <div class="task-item completed">
                      <i class="pi pi-check-circle"></i>
                      <span>Подготовить презентацию</span>
                    </div>
                    <div class="task-item active">
                      <i class="pi pi-circle"></i>
                      <span>Созвониться с командой</span>
                      <span class="task-time">15:00</span>
                    </div>
                    <div class="task-item">
                      <i class="pi pi-circle"></i>
                      <span>Отправить отчёт</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Voice command bubble -->
              <div class="voice-bubble floating-slow">
                <div class="voice-wave">
                  <span></span><span></span><span></span><span></span><span></span>
                </div>
                <span class="voice-text">"Создай задачу на завтра..."</span>
              </div>

              <!-- AI suggestion card -->
              <div class="ai-card floating">
                <i class="pi pi-sparkles"></i>
                <span>AI предлагает: добавить напоминание</span>
              </div>

              <!-- Notification badge -->
              <div class="notification-badge pulse">
                <i class="pi pi-bell"></i>
                <span class="badge-count">3</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
      <div class="container">
        <div class="section-header">
          <span class="section-badge">Возможности</span>
          <h2>Всё, что нужно для продуктивности</h2>
          <p>Мощные инструменты, которые работают вместе</p>
        </div>

        <div class="features-grid">
          <div class="feature-card fade-in-up" v-for="feature in features" :key="feature.title">
            <div class="feature-icon" :style="`background: ${feature.gradient}`">
              <i :class="feature.icon"></i>
            </div>
            <h3>{{ feature.title }}</h3>
            <p>{{ feature.description }}</p>
            <router-link :to="feature.link" class="feature-link">
              Узнать больше <i class="pi pi-arrow-right"></i>
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
      <div class="container">
        <div class="section-header">
          <span class="section-badge">Как это работает</span>
          <h2>Три простых шага к продуктивности</h2>
        </div>

        <div class="steps-container">
          <div class="step-card fade-in-left" v-for="(step, index) in steps" :key="index">
            <div class="step-number">{{ index + 1 }}</div>
            <h3>{{ step.title }}</h3>
            <p>{{ step.description }}</p>
            <div class="step-visual">
              <img :src="step.image" :alt="step.title" />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Integration Section -->
    <section class="integrations-section">
      <div class="container">
        <div class="section-header">
          <span class="section-badge">Интеграции</span>
          <h2>Работает с вашими любимыми сервисами</h2>
          <p>Синхронизация со всеми популярными платформами</p>
        </div>

        <div class="integrations-grid">
          <div class="integration-card" v-for="integration in integrations" :key="integration.name">
            <img :src="integration.logo" :alt="integration.name" />
            <span>{{ integration.name }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing-section" id="pricing">
      <div class="container">
        <div class="section-header">
          <span class="section-badge">Тарифы</span>
          <h2>Выберите подходящий план</h2>
          <p>Прозрачные цены без скрытых платежей</p>
        </div>

        <div class="pricing-toggle">
          <span :class="{ active: !isYearly }">Месяц</span>
          <label class="switch">
            <input type="checkbox" v-model="isYearly" />
            <span class="slider"></span>
          </label>
          <span :class="{ active: isYearly }">Год <span class="discount">-20%</span></span>
        </div>

        <div class="pricing-grid">
          <div class="pricing-card" v-for="plan in pricingPlans" :key="plan.name"
               :class="{ popular: plan.popular }">
            <h3>{{ plan.name }}</h3>
            <div class="price">
              <span class="currency">₽</span>
              <span class="amount">{{ isYearly ? plan.yearlyPrice : plan.monthlyPrice }}</span>
              <span class="period">/{{ isYearly ? 'год' : 'месяц' }}</span>
            </div>
            <p class="description">{{ plan.description }}</p>

            <ul class="features-list">
              <li v-for="feature in plan.features" :key="feature">
                <i class="pi pi-check"></i>
                {{ feature }}
              </li>
            </ul>

            <button class="btn-primary w-full" :class="{ 'btn-gradient': plan.popular }">
              {{ plan.cta }}
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
      <div class="container">
        <div class="section-header">
          <span class="section-badge">Отзывы</span>
          <h2>Что говорят наши пользователи</h2>
          <p>Присоединяйтесь к тысячам довольных клиентов</p>
        </div>

        <div class="testimonials-slider">
          <div class="testimonial-card" v-for="testimonial in testimonials" :key="testimonial.name">
            <div class="quote-icon">"</div>
            <p class="testimonial-text">{{ testimonial.text }}</p>
            <div class="testimonial-author">
              <img :src="testimonial.avatar" :alt="testimonial.name" />
              <div>
                <h4>{{ testimonial.name }}</h4>
                <span>{{ testimonial.position }}</span>
              </div>
            </div>
            <div class="rating">
              <i class="pi pi-star-fill" v-for="n in 5" :key="n"></i>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
      <div class="container">
        <div class="section-header">
          <span class="section-badge">FAQ</span>
          <h2>Часто задаваемые вопросы</h2>
        </div>

        <div class="faq-container">
          <div class="faq-item" v-for="(faq, index) in faqs" :key="index">
            <button class="faq-question" @click="toggleFaq(index)">
              <span>{{ faq.question }}</span>
              <i class="pi" :class="activeFaq === index ? 'pi-minus' : 'pi-plus'"></i>
            </button>
            <transition name="faq">
              <div v-if="activeFaq === index" class="faq-answer">
                <p>{{ faq.answer }}</p>
              </div>
            </transition>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
      <div class="container">
        <div class="cta-card">
          <div class="cta-background"></div>
          <div class="cta-content">
            <h2>Готовы повысить продуктивность?</h2>
            <p>Начните использовать TaskFlow бесплатно прямо сейчас</p>
            <div class="cta-actions">
              <button class="btn-white btn-large" @click="startDemo">
                <i class="pi pi-play"></i>
                Начать бесплатно
              </button>
              <button class="btn-cta-outline btn-large">
                <i class="pi pi-envelope"></i>
                Связаться с нами
              </button>
            </div>
          </div>
          <div class="cta-decoration">
            <div class="deco-circle deco-1"></div>
            <div class="deco-circle deco-2"></div>
            <div class="deco-circle deco-3"></div>
          </div>
        </div>
      </div>
    </section>

    <!-- Demo Modal -->
    <Dialog v-model:visible="showDemoModal" modal header="Демо TaskFlow"
            :style="{ width: '90vw', maxWidth: '1200px' }">
      <div class="demo-container">
        <video controls autoplay>
          <source src="https://www.w3schools.com/html/mov_bbb.mp4" type="video/mp4">
          Ваш браузер не поддерживает видео.
        </video>
      </div>
    </Dialog>
  </LandingLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Dialog from 'primevue/dialog'
import LandingLayout from '@/components/landing/LandingLayout.vue'

const router = useRouter()

// Data
const isYearly = ref(false)
const activeFaq = ref<number | null>(null)
const showDemoModal = ref(false)

// Features data
const features = [
  {
    icon: 'pi pi-microphone',
    title: 'Голосовое управление',
    description: 'Создавайте задачи голосом на русском языке. AI понимает контекст и исправляет ошибки.',
    link: '/features/voice-control',
    gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
  },
  {
    icon: 'pi pi-send',
    title: 'Telegram интеграция',
    description: 'Управляйте задачами прямо в Telegram. Получайте уведомления и работайте в команде.',
    link: '/features/telegram',
    gradient: 'linear-gradient(135deg, #00b4d8 0%, #0077b6 100%)'
  },
  {
    icon: 'pi pi-sparkles',
    title: 'AI-помощник',
    description: 'Умный парсинг команд, автоматизация рутины и предсказания на основе ваших привычек.',
    link: '/features/ai-assistant',
    gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'
  },
  {
    icon: 'pi pi-file',
    title: 'Обработка файлов',
    description: 'Загружайте документы, изображения и аудио. Автоматическая категоризация и поиск.',
    link: '/features/file-processing',
    gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'
  },
  {
    icon: 'pi pi-bell',
    title: 'Умные напоминания',
    description: 'Контекстные уведомления, повторяющиеся задачи и геолокационные триггеры.',
    link: '/features/smart-reminders',
    gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)'
  },
  {
    icon: 'pi pi-desktop',
    title: 'Веб-интерфейс',
    description: 'Красивый и функциональный интерфейс с календарем, графиками и drag & drop.',
    link: '/features/web-interface',
    gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
  }
]

// How it works steps
const steps = [
  {
    title: 'Говорите или пишите',
    description: 'Просто скажите или напишите, что нужно сделать. AI поймет вас с полуслова.',
    image: '/images/step-1.svg'
  },
  {
    title: 'AI организует',
    description: 'Умный помощник создаст задачи, установит приоритеты и напоминания.',
    image: '/images/step-2.svg'
  },
  {
    title: 'Выполняйте эффективно',
    description: 'Следите за прогрессом, получайте уведомления и достигайте целей.',
    image: '/images/step-3.svg'
  }
]

// Integrations
const integrations = [
  { name: 'Telegram', logo: '/logos/telegram.svg' },
  { name: 'Google Calendar', logo: '/logos/google-calendar.svg' },
  { name: 'Slack', logo: '/logos/slack.svg' },
  { name: 'Notion', logo: '/logos/notion.svg' },
  { name: 'Trello', logo: '/logos/trello.svg' },
  { name: 'GitHub', logo: '/logos/github.svg' },
  { name: 'Jira', logo: '/logos/jira.svg' },
  { name: 'Discord', logo: '/logos/discord.svg' }
]

// Pricing plans
const pricingPlans = [
  {
    name: 'Старт',
    monthlyPrice: 0,
    yearlyPrice: 0,
    description: 'Для личного использования',
    features: [
      'До 50 задач в месяц',
      'Голосовые команды (5 мин/день)',
      'Базовые напоминания',
      'Веб-интерфейс',
      'Экспорт данных'
    ],
    cta: 'Начать бесплатно',
    popular: false
  },
  {
    name: 'Про',
    monthlyPrice: 299,
    yearlyPrice: 2390,
    description: 'Для продвинутых пользователей',
    features: [
      'Неограниченные задачи',
      'Голосовые команды без лимитов',
      'Telegram интеграция',
      'AI-помощник',
      'Приоритетная поддержка',
      'Аналитика и отчеты'
    ],
    cta: 'Попробовать 14 дней',
    popular: true
  },
  {
    name: 'Команда',
    monthlyPrice: 599,
    yearlyPrice: 4790,
    description: 'Для команд и бизнеса',
    features: [
      'Всё из тарифа Про',
      'До 10 пользователей',
      'Совместные проекты',
      'Админ-панель',
      'API доступ',
      'Выделенный менеджер'
    ],
    cta: 'Связаться с нами',
    popular: false
  }
]

// Testimonials
const testimonials = [
  {
    name: 'Александр Петров',
    position: 'Предприниматель',
    avatar: '/avatars/alex.jpg',
    text: 'TaskFlow полностью изменил мой подход к планированию. Голосовое управление экономит уйму времени!'
  },
  {
    name: 'Мария Иванова',
    position: 'Продуктовый менеджер',
    avatar: '/avatars/maria.jpg',
    text: 'Наконец-то нашла идеальный инструмент для управления проектами. AI-помощник реально умный!'
  },
  {
    name: 'Дмитрий Сидоров',
    position: 'Разработчик',
    avatar: '/avatars/dmitry.jpg',
    text: 'Интеграция с Telegram - это гениально! Теперь вся команда работает синхронно.'
  }
]

// FAQs
const faqs = [
  {
    question: 'Как работает голосовое управление?',
    answer: 'Просто нажмите кнопку микрофона и скажите, что нужно сделать. Наш AI использует передовые технологии распознавания речи и понимает русский язык, включая сложные команды с контекстом.'
  },
  {
    question: 'Можно ли использовать TaskFlow бесплатно?',
    answer: 'Да! Базовый тариф "Старт" полностью бесплатный и включает до 50 задач в месяц, голосовые команды и веб-интерфейс. Этого достаточно для личного использования.'
  },
  {
    question: 'Как происходит интеграция с Telegram?',
    answer: 'Просто добавьте нашего бота @TaskFlowBot в Telegram и следуйте инструкциям. Вы сможете создавать задачи, получать напоминания и работать с командой прямо в мессенджере.'
  },
  {
    question: 'Безопасны ли мои данные?',
    answer: 'Абсолютно! Мы используем сквозное шифрование, все данные хранятся на защищенных серверах в России. Мы соответствуем всем требованиям 152-ФЗ о персональных данных.'
  },
  {
    question: 'Можно ли экспортировать данные?',
    answer: 'Конечно! Вы можете экспортировать все свои задачи и данные в форматах CSV, JSON или PDF в любой момент. Ваши данные принадлежат только вам.'
  }
]

// Methods
const toggleFaq = (index: number) => {
  activeFaq.value = activeFaq.value === index ? null : index
}

const startDemo = () => {
  router.push('/register')
}

const watchDemo = () => {
  showDemoModal.value = true
}

// Scroll animations
onMounted(() => {
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible')
      }
    })
  }, observerOptions)

  // Observe all animated elements
  document.querySelectorAll('.fade-in-up, .fade-in-left, .fade-in-right, .scale-in').forEach(el => {
    observer.observe(el)
  })
})
</script>

<style scoped lang="scss">
@import '@/styles/landing.scss';

// Hero Section
.hero-section {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  overflow: hidden;
  padding: 6rem 0 4rem;

  .container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1rem;
  }

  .hero-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
      text-align: center;
    }
  }

  .hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: white;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;

    .badge-icon {
      font-size: 1.2rem;
    }
  }

  .hero-title {
    font-size: 4rem;
    line-height: 1.1;
    margin-bottom: 1.5rem;
    color: var(--gray-900);

    @media (max-width: 768px) {
      font-size: 2.5rem;
    }
  }

  .hero-subtitle {
    font-size: 1.25rem;
    color: var(--gray-600);
    margin-bottom: 2rem;
    line-height: 1.6;
  }

  .hero-actions {
    display: flex;
    gap: 1rem;
    margin-bottom: 3rem;

    @media (max-width: 768px) {
      flex-direction: column;
    }

    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
      color: white !important;
      padding: 1rem 2rem;
      font-size: 1.1rem;
      border-radius: 50px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
      transition: all 0.3s ease;

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);
      }
    }

    .btn-glass {
      background: white !important;
      color: #334155 !important;
      padding: 1rem 2rem;
      font-size: 1.1rem;
      border-radius: 50px;
      border: 1px solid #e2e8f0;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.3s ease;

      &:hover {
        background: #f8fafc !important;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
      }
    }
  }

  .hero-stats {
    display: flex;
    gap: 3rem;

    @media (max-width: 768px) {
      justify-content: center;
      gap: 2rem;
    }

    .stat-item {
      display: flex;
      flex-direction: column;

      .stat-number {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--primary);
      }

      .stat-label {
        font-size: 0.875rem;
        color: var(--gray-500);
      }
    }
  }

  .hero-visual {
    position: relative;

    @media (max-width: 768px) {
      display: none;
    }

    .app-preview {
      position: relative;
      width: 400px;
      height: 450px;
      margin: 0 auto;
    }

    .app-window {
      background: white;
      border-radius: 16px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      overflow: hidden;
      width: 100%;

      .app-header {
        background: var(--gray-100);
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;

        .app-dots {
          display: flex;
          gap: 6px;

          .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;

            &.red { background: #ff5f57; }
            &.yellow { background: #febc2e; }
            &.green { background: #28c840; }
          }
        }

        .app-title {
          font-weight: 600;
          color: var(--gray-600);
          font-size: 0.875rem;
        }
      }

      .app-content {
        padding: 1.5rem;

        .task-list {
          .task-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;

            i {
              color: var(--gray-400);
              font-size: 1.1rem;
            }

            span {
              color: var(--gray-700);
              font-size: 0.9rem;
            }

            .task-time {
              margin-left: auto;
              font-size: 0.75rem;
              color: var(--primary);
              background: rgba(99, 102, 241, 0.1);
              padding: 0.25rem 0.5rem;
              border-radius: 4px;
            }

            &.completed {
              i { color: var(--success); }
              span {
                text-decoration: line-through;
                color: var(--gray-400);
              }
            }

            &.active {
              background: rgba(99, 102, 241, 0.05);
              i { color: var(--primary); }
            }
          }
        }
      }
    }

    .voice-bubble {
      position: absolute;
      top: -20px;
      right: -40px;
      background: white;
      padding: 1rem 1.25rem;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      display: flex;
      align-items: center;
      gap: 0.75rem;

      .voice-wave {
        display: flex;
        align-items: center;
        gap: 3px;
        height: 20px;

        span {
          display: block;
          width: 3px;
          height: 100%;
          background: var(--gradient-primary);
          border-radius: 3px;
          animation: voice-wave 0.8s ease-in-out infinite;

          &:nth-child(2) { animation-delay: 0.1s; }
          &:nth-child(3) { animation-delay: 0.2s; }
          &:nth-child(4) { animation-delay: 0.3s; }
          &:nth-child(5) { animation-delay: 0.4s; }
        }
      }

      .voice-text {
        font-size: 0.8rem;
        color: var(--gray-600);
        font-style: italic;
      }
    }

    .ai-card {
      position: absolute;
      bottom: 60px;
      left: -60px;
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      color: white;
      padding: 0.75rem 1rem;
      border-radius: 12px;
      font-size: 0.75rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      box-shadow: 0 10px 30px rgba(240, 147, 251, 0.3);

      i { font-size: 1rem; }
    }

    .notification-badge {
      position: absolute;
      top: 80px;
      right: 20px;
      background: var(--gradient-primary);
      color: white;
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);

      .badge-count {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: white;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
      }
    }
  }

  .floating-slow {
    animation: float 4s ease-in-out infinite;
  }

  .pulse {
    animation: pulse-scale 2s ease-in-out infinite;
  }
}

@keyframes voice-wave {
  0%, 100% { transform: scaleY(0.3); }
  50% { transform: scaleY(1); }
}

@keyframes pulse-scale {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

@keyframes pulse-ring {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  100% {
    transform: scale(1.5);
    opacity: 0;
  }
}

// Features Section
.features-section {
  padding: 5rem 0;
  background: white;

  .features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-top: 3rem;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  .feature-card {
    background: white;
    padding: 2rem;
    border-radius: 20px;
    border: 1px solid var(--gray-200);
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      border-color: var(--primary);

      .feature-link {
        color: var(--primary);
        transform: translateX(5px);
      }
    }

    .feature-icon {
      width: 60px;
      height: 60px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
      margin-bottom: 1.5rem;
    }

    h3 {
      margin-bottom: 1rem;
      color: var(--gray-900);
    }

    p {
      color: var(--gray-600);
      line-height: 1.6;
      margin-bottom: 1.5rem;
    }

    .feature-link {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.3s ease;
    }
  }
}

// Common section styles
.section-header {
  text-align: center;
  margin-bottom: 3rem;

  .section-badge {
    display: inline-block;
    background: var(--gradient-primary);
    color: white;
    padding: 0.25rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
  }

  h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: var(--gray-900);

    @media (max-width: 768px) {
      font-size: 2rem;
    }
  }

  p {
    font-size: 1.25rem;
    color: var(--gray-600);
  }
}

.container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 1rem;
}

// How it works
.how-it-works {
  padding: 5rem 0;
  background: var(--gray-50);

  .steps-container {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 3rem;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  .step-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
    position: relative;

    .step-number {
      position: absolute;
      top: -20px;
      left: 50%;
      transform: translateX(-50%);
      width: 40px;
      height: 40px;
      background: var(--gradient-primary);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      font-size: 1.2rem;
    }

    h3 {
      margin: 1.5rem 0 1rem;
      color: var(--gray-900);
    }

    p {
      color: var(--gray-600);
      line-height: 1.6;
      margin-bottom: 2rem;
    }

    .step-visual {
      img {
        width: 100%;
        max-width: 200px;
      }
    }
  }
}

// Integrations
.integrations-section {
  padding: 5rem 0;
  background: white;

  .integrations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 2rem;
    margin-top: 3rem;

    @media (max-width: 768px) {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  .integration-card {
    background: var(--gray-50);
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    img {
      width: 48px;
      height: 48px;
      margin-bottom: 1rem;
    }

    span {
      display: block;
      color: var(--gray-700);
      font-weight: 500;
    }
  }
}

// Pricing
.pricing-section {
  padding: 5rem 0;
  background: var(--gray-50);

  .pricing-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin: 2rem 0 3rem;

    span {
      color: var(--gray-600);

      &.active {
        color: var(--gray-900);
        font-weight: 600;
      }

      .discount {
        background: var(--gradient-primary);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        margin-left: 0.5rem;
      }
    }

    .switch {
      position: relative;
      display: inline-block;
      width: 60px;
      height: 30px;

      input {
        opacity: 0;
        width: 0;
        height: 0;

        &:checked + .slider {
          background: var(--gradient-primary);

          &:before {
            transform: translateX(30px);
          }
        }
      }

      .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--gray-300);
        transition: 0.4s;
        border-radius: 30px;

        &:before {
          position: absolute;
          content: "";
          height: 22px;
          width: 22px;
          left: 4px;
          bottom: 4px;
          background: white;
          transition: 0.4s;
          border-radius: 50%;
        }
      }
    }
  }

  .pricing-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  .pricing-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    &.popular {
      border: 2px solid var(--primary);
      position: relative;
      transform: scale(1.05);

      &::before {
        content: 'Популярный';
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--gradient-primary);
        color: white;
        padding: 0.25rem 1rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
      }
    }

    h3 {
      font-size: 1.5rem;
      color: var(--gray-900);
      margin-bottom: 0.5rem;
    }

    .description {
      color: var(--gray-500);
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }

    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
      color: white !important;
      padding: 0.875rem 2rem;
      border-radius: 50px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      width: 100%;
      box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
      }
    }
  }

  .price {
    display: flex;
    align-items: baseline;
    justify-content: center;
    margin: 1.5rem 0;

    .currency {
      font-size: 1.5rem;
      color: var(--gray-600);
      margin-right: 0.25rem;
    }

    .amount {
      font-size: 3rem;
      font-weight: 800;
      color: var(--gray-900);
    }

    .period {
      font-size: 1rem;
      color: var(--gray-500);
      margin-left: 0.5rem;
    }
  }

  .features-list {
    list-style: none;
    padding: 0;
    margin: 2rem 0;

    li {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 1rem;
      color: var(--gray-700);

      i {
        color: var(--success);
      }
    }
  }

  .btn-gradient {
    background: var(--gradient-primary);
    color: white;
  }

  .w-full {
    width: 100%;
  }
}

// Testimonials
.testimonials-section {
  padding: 5rem 0;
  background: white;

  .testimonials-slider {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 3rem;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  .testimonial-author {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1.5rem;

    img {
      width: 50px;
      height: 50px;
      border-radius: 50%;
    }

    h4 {
      margin: 0;
      color: var(--gray-900);
    }

    span {
      color: var(--gray-500);
      font-size: 0.875rem;
    }
  }

  .rating {
    margin-top: 1rem;
    color: #fbbf24;
  }
}

// FAQ
.faq-section {
  padding: 5rem 0;
  background: var(--gray-50);

  .faq-container {
    max-width: 800px;
    margin: 0 auto;
  }

  .faq-item {
    background: white;
    border-radius: 12px;
    margin-bottom: 1rem;
    overflow: hidden;

    .faq-question {
      width: 100%;
      padding: 1.5rem;
      background: none;
      border: none;
      text-align: left;
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--gray-900);
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
      transition: all 0.3s ease;

      &:hover {
        background: var(--gray-50);
      }

      i {
        color: var(--primary);
        transition: transform 0.3s ease;
      }
    }

    .faq-answer {
      padding: 0 1.5rem 1.5rem;
      color: var(--gray-600);
      line-height: 1.6;
    }
  }
}

.faq-enter-active,
.faq-leave-active {
  transition: all 0.3s ease;
}

.faq-enter-from,
.faq-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

// CTA Section
.cta-section {
  padding: 5rem 0;

  .cta-card {
    border-radius: 30px;
    padding: 4rem;
    color: white;
    position: relative;
    overflow: hidden;
    background: var(--gradient-primary);

    @media (max-width: 768px) {
      padding: 2rem;
    }

    .cta-background {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
      background-size: 200% 200%;
      animation: gradient-shift 15s ease infinite;
    }

    .cta-content {
      position: relative;
      z-index: 2;
      text-align: center;

      h2 {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: white;

        @media (max-width: 768px) {
          font-size: 2rem;
        }
      }

      p {
        font-size: 1.25rem;
        margin-bottom: 2rem;
        opacity: 0.9;
        color: white;
      }

      .cta-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;

        @media (max-width: 768px) {
          flex-direction: column;
        }
      }
    }

    .cta-decoration {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      pointer-events: none;

      .deco-circle {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);

        &.deco-1 {
          width: 300px;
          height: 300px;
          top: -100px;
          right: -100px;
        }

        &.deco-2 {
          width: 200px;
          height: 200px;
          bottom: -50px;
          left: -50px;
        }

        &.deco-3 {
          width: 150px;
          height: 150px;
          top: 50%;
          left: 30%;
        }
      }
    }
  }

  .btn-cta-outline {
    background: transparent;
    color: white;
    padding: 1rem 2rem;
    border-radius: 50px;
    border: 2px solid rgba(255, 255, 255, 0.5);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.1rem;

    &:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: white;
      transform: translateY(-2px);
    }
  }
}

@keyframes gradient-shift {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.btn-white {
  background: white;
  color: var(--primary);
  padding: 12px 32px;
  border-radius: 50px;
  font-weight: 600;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  }
}

.demo-container {
  video {
    width: 100%;
    border-radius: 12px;
  }
}
</style>