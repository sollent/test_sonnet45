<template>
  <LandingLayout>
    <!-- Hero Section -->
    <section class="feature-hero telegram-hero">
      <div class="hero-bg">
        <div class="floating-orbs">
          <div class="orb orb-1"></div>
          <div class="orb orb-2"></div>
          <div class="orb orb-3"></div>
        </div>
      </div>

      <div class="container">
        <div class="hero-content">
          <div class="hero-text fade-in-up">
            <div class="feature-badge">
              <i class="pi pi-send"></i>
              Telegram интеграция
            </div>

            <h1>Telegram <span class="gradient-text-light">без хаоса</span></h1>
            <p class="lead">
              Структурированные задачи из любых сообщений. Превратите бесконечный поток чатов
              в организованный список дел с дедлайнами и приоритетами.
            </p>

            <div class="hero-actions">
              <button class="btn-primary btn-large" @click="installBot">
                <i class="pi pi-download"></i>
                Установить и не терять задачи
              </button>
              <button class="btn-glass btn-large" @click="showDemo = true">
                <i class="pi pi-video"></i>
                Смотреть демо
              </button>
            </div>
          </div>

          <div class="hero-visual fade-in-right">
            <div class="telegram-animation">
              <div class="chat-bubbles">
                <div class="bubble incoming">Подготовь отчет к пятнице</div>
                <div class="bubble incoming">И не забудь про презентацию!</div>
                <div class="bubble task-created">
                  <i class="pi pi-check-circle"></i>
                  2 задачи созданы
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Problems Section -->
    <section class="problems-section">
      <div class="container">
        <div class="section-header">
          <h2>Знакомые проблемы?</h2>
          <p>Telegram — отличный мессенджер, но ужасный планировщик</p>
        </div>

        <div class="problems-grid">
          <div class="problem-card fade-in-up" v-for="problem in problems" :key="problem.title">
            <div class="problem-icon">
              <i :class="problem.icon"></i>
            </div>
            <h3>{{ problem.title }}</h3>
            <p>{{ problem.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section">
      <div class="container">
        <div class="section-header">
          <h2>Истории пользователей</h2>
          <p>Как TaskFlow помог навести порядок в Telegram</p>
        </div>

        <div class="testimonials-grid">
          <div class="testimonial-card" v-for="testimonial in testimonials" :key="testimonial.name">
            <div class="testimonial-header">
              <div class="avatar">{{ testimonial.avatar }}</div>
              <div>
                <h4>{{ testimonial.name }}</h4>
                <span>{{ testimonial.role }}</span>
              </div>
            </div>
            <p class="testimonial-text">{{ testimonial.text }}</p>
            <div class="testimonial-stat">
              <i class="pi pi-chart-line"></i>
              {{ testimonial.stat }}
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Message Types Section -->
    <section class="message-types-section">
      <div class="container">
        <div class="section-header">
          <h2>Поддерживаемые типы сообщений</h2>
          <p>Создавайте задачи из любого контента</p>
        </div>

        <div class="types-grid">
          <div class="type-card" v-for="type in messageTypes" :key="type.title">
            <div class="type-icon" :style="`background: ${type.color}`">
              <i :class="type.icon"></i>
            </div>
            <h3>{{ type.title }}</h3>
            <p>{{ type.description }}</p>
            <div class="type-example">
              <code>{{ type.example }}</code>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Benefits Section -->
    <section class="benefits-section">
      <div class="container">
        <div class="benefits-content">
          <div class="benefits-text">
            <h2>Результаты использования</h2>
            <p class="lead">Реальные метрики от наших пользователей</p>

            <div class="benefits-list">
              <div class="benefit-item" v-for="benefit in benefits" :key="benefit.title">
                <div class="benefit-value">{{ benefit.value }}</div>
                <div>
                  <h4>{{ benefit.title }}</h4>
                  <p>{{ benefit.description }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="benefits-visual">
            <div class="stats-card glass-card">
              <div class="stat-row">
                <span>Экономия времени</span>
                <strong>до 2 часов/день</strong>
              </div>
              <div class="stat-row">
                <span>Пропущенные дедлайны</span>
                <strong>0%</strong>
              </div>
              <div class="stat-row">
                <span>Обработанных сообщений</span>
                <strong>100%</strong>
              </div>
            </div>
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
              <button class="btn-white btn-large">
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
    <Dialog v-model:visible="showDemo" modal header="Демо Telegram интеграции"
            :style="{ width: '90vw', maxWidth: '800px' }">
      <div class="demo-content">
        <video controls autoplay>
          <source src="https://www.w3schools.com/html/mov_bbb.mp4" type="video/mp4">
        </video>
      </div>
    </Dialog>
  </LandingLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Dialog from 'primevue/dialog'
import LandingLayout from '@/components/landing/LandingLayout.vue'
import { useScrollAnimations } from '@/composables/useScrollAnimations'
import { useSeoMeta, seoConfigs } from '@/composables/useSeoMeta'

const router = useRouter()
const showDemo = ref(false)

useScrollAnimations()
useSeoMeta(seoConfigs.telegram)

const problems = [
  {
    icon: 'pi pi-search',
    title: 'Сообщения теряются',
    description: 'Важные задачи тонут в потоке чатов. Вы тратите часы на поиск того, что обещали сделать.'
  },
  {
    icon: 'pi pi-file-pdf',
    title: 'Забытые файлы',
    description: 'PDF с ТЗ, картинки с правками — все теряется в истории сообщений.'
  },
  {
    icon: 'pi pi-volume-up',
    title: 'Голосовые без контекста',
    description: 'Переслушивать 5-минутное голосовое, чтобы вспомнить задачу — это ад.'
  },
  {
    icon: 'pi pi-clock',
    title: 'Нет структуры',
    description: 'Без дедлайнов, приоритетов и категорий невозможно понять, что делать первым.'
  }
]

const testimonials = [
  {
    avatar: 'Д',
    name: 'Дмитрий',
    role: 'Project Manager, 50+ чатов',
    text: 'Раньше каждое утро начиналось с перебирания чатов. Теперь все задачи автоматически попадают в TaskFlow.',
    stat: 'Экономит 3 часа в день'
  },
  {
    avatar: 'А',
    name: 'Анна',
    role: 'Дизайнер-фрилансер',
    text: 'Клиенты присылают правки в разных чатах. TaskFlow собирает все в одном месте с привязкой к проекту.',
    stat: '0 пропущенных дедлайнов'
  },
  {
    avatar: 'М',
    name: 'Михаил',
    role: 'Менеджер по продажам',
    text: 'Голосовые от клиентов превращаются в задачи с контактами и суммами. Больше никакого хаоса.',
    stat: '+40% к продажам'
  },
  {
    avatar: 'М',
    name: 'Мария',
    role: 'Блогер, 100k подписчиков',
    text: 'Идеи для контента приходят в любое время. Отправляю в бота — и они уже в плане публикаций.',
    stat: '2x контента в месяц'
  }
]

const messageTypes = [
  {
    icon: 'pi pi-comment',
    title: 'Текстовые сообщения',
    description: 'AI извлекает задачи, даты и приоритеты из обычного текста',
    example: '"Нужно сделать отчет до пятницы"',
    color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
  },
  {
    icon: 'pi pi-microphone',
    title: 'Голосовые сообщения',
    description: 'Whisper транскрибирует, AI понимает контекст и создает задачу',
    example: '"Позвони клиенту завтра..."',
    color: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'
  },
  {
    icon: 'pi pi-file',
    title: 'Файлы и PDF',
    description: 'Анализируем документы и создаем задачи на основе содержимого',
    example: 'contract.pdf - 5 задач',
    color: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'
  },
  {
    icon: 'pi pi-megaphone',
    title: 'Посты из каналов',
    description: 'Пересылайте посты — бот создаст задачу с источником',
    example: 'Пост - Задача + ссылка',
    color: 'linear-gradient(135deg, #13ce66 0%, #00d2d3 100%)'
  }
]

const benefits = [
  {
    value: '2ч',
    title: 'Экономия времени',
    description: 'Ежедневно экономьте до 2 часов на организации задач'
  },
  {
    value: '0%',
    title: 'Просроченных задач',
    description: 'Автоматические напоминания не дают забыть о дедлайнах'
  },
  {
    value: '100%',
    title: 'Обработка сообщений',
    description: 'Каждое сообщение превращается в структурированную задачу'
  }
]

const installBot = () => {
  window.open('https://t.me/taskflow_bot', '_blank')
}
</script>

<style scoped lang="scss">
@import '@/styles/landing.scss';

.feature-hero {
  position: relative;
  padding: 8rem 0 4rem;
  background: linear-gradient(135deg, #0088cc 0%, #229ed9 100%);
  overflow: hidden;

  .hero-bg {
    position: absolute;
    inset: 0;
    opacity: 0.1;

    .floating-orbs .orb {
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
      animation: float 10s ease-in-out infinite;

      &.orb-1 { width: 200px; height: 200px; top: 10%; left: 10%; }
      &.orb-2 { width: 150px; height: 150px; top: 50%; right: 10%; animation-delay: 3s; }
      &.orb-3 { width: 100px; height: 100px; bottom: 10%; left: 50%; animation-delay: 6s; }
    }
  }

  .hero-content {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
      text-align: center;
    }
  }

  .feature-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    color: white;
    margin-bottom: 1rem;
  }

  h1 {
    color: white;
    font-size: 3.5rem;
    margin-bottom: 1rem;

    @media (max-width: 768px) { font-size: 2.5rem; }
  }

  .lead {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.25rem;
    line-height: 1.6;
    margin-bottom: 2rem;
  }

  .hero-actions {
    display: flex;
    gap: 1rem;

    @media (max-width: 768px) { flex-direction: column; }
  }

  .btn-large {
    padding: 1rem 2rem;
    font-size: 1.1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
  }

  .telegram-animation .chat-bubbles {
    display: flex;
    flex-direction: column;
    gap: 1rem;

    .bubble {
      padding: 1rem 1.5rem;
      border-radius: 18px;
      max-width: 280px;
      animation: slideIn 0.5s ease-out forwards;
      opacity: 0;

      &.incoming {
        background: white;
        color: var(--gray-900);
        align-self: flex-start;
        &:nth-child(1) { animation-delay: 0.2s; }
        &:nth-child(2) { animation-delay: 0.6s; }
      }

      &.task-created {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        color: white;
        align-self: flex-end;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        animation-delay: 1s;
        i { color: #4ade80; }
      }
    }
  }
}

@keyframes slideIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.problems-section {
  padding: 5rem 0;
  background: var(--gray-50);

  .problems-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
    margin-top: 3rem;

    @media (max-width: 1024px) { grid-template-columns: repeat(2, 1fr); }
    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .problem-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }

    .problem-icon {
      width: 60px;
      height: 60px;
      margin: 0 auto 1.5rem;
      background: #fee2e2;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ef4444;
      font-size: 1.5rem;
    }

    h3 { margin-bottom: 1rem; color: var(--gray-900); }
    p { color: var(--gray-600); line-height: 1.6; }
  }
}

.testimonials-section {
  padding: 5rem 0;
  background: white;

  .testimonials-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
    margin-top: 3rem;

    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .testimonial-card {
    background: var(--gray-50);
    border-radius: 16px;
    padding: 2rem;
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }

    .testimonial-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;

      .avatar {
        width: 50px;
        height: 50px;
        background: var(--gradient-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1.25rem;
      }

      h4 { margin: 0; color: var(--gray-900); }
      span { font-size: 0.875rem; color: var(--gray-500); }
    }

    .testimonial-text {
      color: var(--gray-700);
      line-height: 1.6;
      margin-bottom: 1rem;
      font-style: italic;
    }

    .testimonial-stat {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: var(--primary);
      font-weight: 600;
    }
  }
}

.message-types-section {
  padding: 5rem 0;
  background: var(--gray-50);

  .types-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
    margin-top: 3rem;

    @media (max-width: 1024px) { grid-template-columns: repeat(2, 1fr); }
    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .type-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }

    .type-icon {
      width: 60px;
      height: 60px;
      margin: 0 auto 1.5rem;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
      i.pi { color: white !important; }
    }

    h3 { margin-bottom: 0.5rem; color: var(--gray-900); }
    p { color: var(--gray-600); line-height: 1.5; margin-bottom: 1rem; font-size: 0.9rem; }

    .type-example code {
      display: block;
      padding: 0.75rem;
      background: var(--gray-100);
      border-radius: 8px;
      font-size: 0.8rem;
      color: var(--gray-700);
    }
  }
}

.benefits-section {
  padding: 5rem 0;
  background: white;

  .benefits-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;

    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .benefits-list {
    margin-top: 2rem;

    .benefit-item {
      display: flex;
      gap: 1.5rem;
      margin-bottom: 2rem;
      align-items: flex-start;

      .benefit-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary);
        line-height: 1;
        min-width: 80px;
      }

      h4 { margin-bottom: 0.5rem; color: var(--gray-900); }
      p { color: var(--gray-600); line-height: 1.5; }
    }
  }

  .stats-card {
    padding: 2rem;

    .stat-row {
      display: flex;
      justify-content: space-between;
      padding: 1rem 0;
      border-bottom: 1px solid var(--gray-200);

      &:last-child { border-bottom: none; }
      span { color: var(--gray-600); }
      strong { color: var(--primary); font-size: 1.25rem; }
    }
  }
}

.feature-cta {
  padding: 5rem 0;

  .cta-card {
    padding: 4rem;
    border-radius: 30px;
    text-align: center;
    color: white;

    h2 { font-size: 2.5rem; margin-bottom: 1rem; }
    p { font-size: 1.25rem; margin-bottom: 2rem; opacity: 0.9; }

    .cta-actions {
      display: flex;
      gap: 1rem;
      justify-content: center;

      @media (max-width: 768px) { flex-direction: column; }
    }
  }
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
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  }
}

.demo-content video {
  width: 100%;
  border-radius: 12px;
}

.section-header {
  text-align: center;
  margin-bottom: 3rem;

  h2 { font-size: 2.5rem; margin-bottom: 1rem; color: var(--gray-900); }
  p { font-size: 1.25rem; color: var(--gray-600); }
}

.container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 1rem;
}

.fade-in-up {
  opacity: 0;
  transform: translateY(30px);
  transition: all 0.6s ease-out;

  &.visible { opacity: 1; transform: translateY(0); }
}

.fade-in-right {
  opacity: 0;
  transform: translateX(30px);
  transition: all 0.6s ease-out;

  &.visible { opacity: 1; transform: translateX(0); }
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-20px); }
}
</style>
