<template>
  <LandingLayout>
    <!-- Hero Section -->
    <section class="feature-hero ai-hero">
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
              <i class="pi pi-sparkles"></i>
              AI Ассистент
            </div>

            <h1>AI который <span class="gradient-text-light">понимает тебя</span></h1>
            <p class="lead">
              Преобразование речи в задачи без форм и кнопок. Просто говорите естественным языком —
              AI поймет контекст, даты и приоритеты.
            </p>

            <div class="hero-actions">
              <button class="btn-primary btn-large" @click="tryAI">
                <i class="pi pi-play"></i>
                Протестировать AI
              </button>
              <button class="btn-glass btn-large" @click="showDemo = true">
                <i class="pi pi-video"></i>
                Смотреть демо
              </button>
            </div>
          </div>

          <div class="hero-visual fade-in-right">
            <div class="ai-animation">
              <div class="brain-icon pulse-glow">
                <i class="pi pi-sparkles"></i>
              </div>
              <div class="neural-lines">
                <div class="line" v-for="i in 6" :key="i" :style="`animation-delay: ${i * 0.2}s`"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- AI Capabilities Section -->
    <section class="capabilities-section">
      <div class="container">
        <div class="section-header">
          <h2>Возможности AI</h2>
          <p>Передовые технологии обработки естественного языка</p>
        </div>

        <div class="capabilities-grid">
          <div class="capability-card fade-in-up" v-for="capability in capabilities" :key="capability.title">
            <div class="capability-icon" :style="`background: ${capability.color}`">
              <i :class="capability.icon"></i>
            </div>
            <h3>{{ capability.title }}</h3>
            <p>{{ capability.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Time Processing Section -->
    <section class="time-processing-section">
      <div class="container">
        <div class="section-header">
          <h2>Умная обработка времени</h2>
          <p>AI понимает относительные и абсолютные даты</p>
        </div>

        <div class="time-examples">
          <div class="time-card" v-for="example in timeExamples" :key="example.input">
            <div class="time-input">
              <i class="pi pi-comment"></i>
              <span>"{{ example.input }}"</span>
            </div>
            <div class="time-arrow">
              <i class="pi pi-arrow-right"></i>
            </div>
            <div class="time-output">
              <i class="pi pi-calendar"></i>
              <span>{{ example.output }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Examples Table Section -->
    <section class="examples-section">
      <div class="container">
        <div class="section-header">
          <h2>Примеры преобразований</h2>
          <p>Голосовое сообщение - Структурированная задача</p>
        </div>

        <div class="examples-table">
          <div class="table-header">
            <div class="col">Голосовая команда</div>
            <div class="col">Результат</div>
          </div>
          <div class="table-row" v-for="example in transformExamples" :key="example.voice">
            <div class="col voice-col">
              <i class="pi pi-microphone"></i>
              "{{ example.voice }}"
            </div>
            <div class="col result-col">
              <div class="result-item">
                <strong>{{ example.title }}</strong>
                <span v-if="example.date">{{ example.date }}</span>
                <span v-if="example.priority">{{ example.priority }}</span>
                <span v-if="example.tags">{{ example.tags }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Benefits Section -->
    <section class="benefits-section">
      <div class="container">
        <div class="benefits-content">
          <div class="benefits-visual">
            <div class="speed-card glass-card">
              <div class="speed-value">+500%</div>
              <div class="speed-label">Скорость фиксации идей</div>
              <div class="speed-comparison">
                <div class="comparison-item">
                  <span>Традиционно</span>
                  <div class="bar" style="width: 20%"></div>
                  <span>30 сек</span>
                </div>
                <div class="comparison-item">
                  <span>С TaskFlow AI</span>
                  <div class="bar active" style="width: 100%"></div>
                  <span>5 сек</span>
                </div>
              </div>
            </div>
          </div>

          <div class="benefits-text">
            <h2>Почему это быстрее</h2>
            <p class="lead">AI экономит время на каждом этапе</p>

            <div class="benefits-list">
              <div class="benefit-item" v-for="benefit in benefits" :key="benefit.title">
                <div class="benefit-icon">
                  <i :class="benefit.icon"></i>
                </div>
                <div>
                  <h4>{{ benefit.title }}</h4>
                  <p>{{ benefit.description }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Security Section -->
    <section class="security-section">
      <div class="container">
        <div class="section-header">
          <h2>Безопасность данных</h2>
          <p>Ваши данные защищены на всех уровнях</p>
        </div>

        <div class="security-grid">
          <div class="security-card" v-for="item in securityFeatures" :key="item.title">
            <div class="security-icon">
              <i :class="item.icon"></i>
            </div>
            <h3>{{ item.title }}</h3>
            <p>{{ item.description }}</p>
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
    <Dialog v-model:visible="showDemo" modal header="Демо AI Ассистента"
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
useSeoMeta(seoConfigs.aiAssistant)

const capabilities = [
  {
    icon: 'pi pi-language',
    title: 'NLP понимание',
    description: 'Глубокое понимание естественного языка на русском, украинском и английском',
    color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
  },
  {
    icon: 'pi pi-flag',
    title: 'Определение приоритетов',
    description: 'AI распознает срочность по ключевым словам и контексту',
    color: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'
  },
  {
    icon: 'pi pi-tags',
    title: 'Автоматические теги',
    description: 'Категоризация задач на основе содержания и истории',
    color: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'
  },
  {
    icon: 'pi pi-calendar',
    title: 'Умные даты',
    description: 'Преобразование "завтра", "через неделю" в конкретные даты',
    color: 'linear-gradient(135deg, #13ce66 0%, #00d2d3 100%)'
  },
  {
    icon: 'pi pi-sitemap',
    title: 'Создание подзадач',
    description: 'Автоматическое разбиение сложных задач на подзадачи',
    color: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'
  },
  {
    icon: 'pi pi-history',
    title: 'Обучение на привычках',
    description: 'AI учится на ваших паттернах и становится умнее',
    color: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'
  }
]

const timeExamples = [
  { input: 'завтра', output: '23 ноября 2025' },
  { input: 'через неделю', output: '29 ноября 2025' },
  { input: 'в следующую пятницу', output: '28 ноября 2025' },
  { input: 'в конце месяца', output: '30 ноября 2025' },
  { input: 'через 3 дня в 15:00', output: '25 ноября, 15:00' }
]

const transformExamples = [
  {
    voice: 'Срочно позвонить клиенту Иванову завтра в 10 утра',
    title: 'Позвонить клиенту Иванову',
    date: 'Завтра, 10:00',
    priority: 'Высокий',
    tags: 'Звонки, Клиенты'
  },
  {
    voice: 'Подготовить презентацию для встречи в пятницу',
    title: 'Подготовить презентацию',
    date: 'Пятница',
    priority: 'Средний',
    tags: 'Работа, Презентации'
  },
  {
    voice: 'Купить молоко и хлеб по дороге домой',
    title: 'Купить продукты',
    date: 'Сегодня',
    priority: 'Низкий',
    tags: 'Покупки'
  }
]

const benefits = [
  { icon: 'pi pi-bolt', title: 'Нет форм', description: 'Не нужно заполнять поля — просто говорите' },
  { icon: 'pi pi-check-circle', title: 'Нет ошибок', description: 'AI исправляет опечатки и грамматику' },
  { icon: 'pi pi-clock', title: 'Мгновенно', description: 'Задача создается за 2-3 секунды' },
  { icon: 'pi pi-mobile', title: 'Где угодно', description: 'Работает на телефоне, в машине, на прогулке' }
]

const securityFeatures = [
  { icon: 'pi pi-lock', title: 'Шифрование', description: 'Все данные шифруются AES-256 при передаче и хранении' },
  { icon: 'pi pi-server', title: 'Защищенная среда', description: 'AI работает в изолированном окружении без доступа к внешним сервисам' },
  { icon: 'pi pi-trash', title: 'Автоудаление', description: 'Голосовые записи удаляются сразу после обработки' },
  { icon: 'pi pi-shield', title: 'GDPR', description: 'Полное соответствие требованиям защиты персональных данных' }
]

const tryAI = () => {
  router.push('/register')
}
</script>

<style scoped lang="scss">
@import '@/styles/landing.scss';

.feature-hero {
  position: relative;
  padding: 8rem 0 4rem;
  background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
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

  .ai-animation {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 300px;

    .brain-icon {
      width: 120px;
      height: 120px;
      background: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      color: var(--primary);
      z-index: 2;
    }

    .neural-lines {
      position: absolute;
      .line {
        position: absolute;
        width: 100px;
        height: 2px;
        background: rgba(255, 255, 255, 0.3);
        animation: pulse-line 2s ease-in-out infinite;

        &:nth-child(1) { transform: rotate(0deg) translateX(80px); }
        &:nth-child(2) { transform: rotate(60deg) translateX(80px); }
        &:nth-child(3) { transform: rotate(120deg) translateX(80px); }
        &:nth-child(4) { transform: rotate(180deg) translateX(80px); }
        &:nth-child(5) { transform: rotate(240deg) translateX(80px); }
        &:nth-child(6) { transform: rotate(300deg) translateX(80px); }
      }
    }
  }
}

@keyframes pulse-line {
  0%, 100% { opacity: 0.3; }
  50% { opacity: 1; }
}

.capabilities-section {
  padding: 5rem 0;
  background: white;

  .capabilities-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 3rem;

    @media (max-width: 1024px) { grid-template-columns: repeat(2, 1fr); }
    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .capability-card {
    text-align: center;
    padding: 2rem;
    border-radius: 16px;
    background: var(--gray-50);
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
      background: white;
    }

    .capability-icon {
      width: 60px;
      height: 60px;
      margin: 0 auto 1.5rem;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
    }

    h3 { margin-bottom: 1rem; color: var(--gray-900); }
    p { color: var(--gray-600); line-height: 1.6; }
  }
}

.time-processing-section {
  padding: 5rem 0;
  background: var(--gray-50);

  .time-examples {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-width: 800px;
    margin: 3rem auto 0;
  }

  .time-card {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    background: white;
    border-radius: 12px;
    transition: all 0.3s ease;

    &:hover {
      transform: translateX(10px);
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    .time-input, .time-output {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      i { color: var(--primary); }
    }

    .time-input span { color: var(--gray-700); font-style: italic; }
    .time-output span { color: var(--gray-900); font-weight: 600; }
    .time-arrow { color: var(--gray-400); }
  }
}

.examples-section {
  padding: 5rem 0;
  background: white;

  .examples-table {
    max-width: 1000px;
    margin: 3rem auto 0;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);

    .table-header {
      display: grid;
      grid-template-columns: 1fr 1fr;
      background: var(--gradient-primary);
      color: white;
      font-weight: 600;
      .col { padding: 1rem 1.5rem; }
    }

    .table-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      border-bottom: 1px solid var(--gray-200);

      &:last-child { border-bottom: none; }
      &:hover { background: var(--gray-50); }

      .col { padding: 1.5rem; }

      .voice-col {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--gray-700);
        font-style: italic;
        i { color: var(--primary); }
      }

      .result-col .result-item {
        strong { display: block; color: var(--gray-900); margin-bottom: 0.5rem; }
        span { display: inline-block; font-size: 0.875rem; color: var(--gray-600); margin-right: 1rem; }
      }
    }
  }
}

.benefits-section {
  padding: 5rem 0;
  background: var(--gray-50);

  .benefits-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .speed-card {
    padding: 2rem;

    .speed-value { font-size: 4rem; font-weight: 700; color: var(--primary); text-align: center; }
    .speed-label { text-align: center; color: var(--gray-600); margin-bottom: 2rem; }

    .speed-comparison .comparison-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;

      span:first-child { width: 100px; font-size: 0.875rem; color: var(--gray-600); }

      .bar {
        height: 8px;
        background: var(--gray-300);
        border-radius: 4px;
        flex: 1;
        &.active { background: var(--gradient-primary); }
      }

      span:last-child { width: 60px; text-align: right; font-weight: 600; color: var(--gray-900); }
    }
  }

  .benefits-list {
    margin-top: 2rem;

    .benefit-item {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;

      .benefit-icon {
        width: 48px;
        height: 48px;
        background: var(--gradient-primary);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        flex-shrink: 0;
      }

      h4 { margin-bottom: 0.5rem; color: var(--gray-900); }
      p { color: var(--gray-600); line-height: 1.5; }
    }
  }
}

.security-section {
  padding: 5rem 0;
  background: white;

  .security-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
    margin-top: 3rem;

    @media (max-width: 1024px) { grid-template-columns: repeat(2, 1fr); }
    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .security-card {
    text-align: center;
    padding: 2rem;
    border-radius: 16px;
    background: var(--gray-50);
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-5px);
      background: white;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }

    .security-icon {
      width: 60px;
      height: 60px;
      margin: 0 auto 1.5rem;
      background: #dcfce7;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #16a34a;
      font-size: 1.5rem;
    }

    h3 { margin-bottom: 0.5rem; color: var(--gray-900); }
    p { color: var(--gray-600); line-height: 1.5; font-size: 0.9rem; }
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
