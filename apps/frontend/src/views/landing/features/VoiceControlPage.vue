<template>
  <LandingLayout>
    <!-- Hero Section -->
    <section class="feature-hero voice-control-hero">
      <div class="hero-bg">
        <div class="wave-pattern"></div>
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
              <i class="pi pi-microphone"></i>
              Голосовое управление
            </div>

            <h1>Управляйте задачами <span class="gradient-text-light">голосом</span></h1>
            <p class="lead">
              Просто скажите, что нужно сделать. Наш AI понимает русский язык,
              исправляет ошибки и создает задачи с правильными датами и приоритетами.
            </p>

            <div class="hero-actions">
              <button class="btn-primary btn-large" @click="tryNow">
                <i class="pi pi-play"></i>
                Попробовать сейчас
              </button>
              <button class="btn-glass btn-large" @click="showDemo = true">
                <i class="pi pi-video"></i>
                Смотреть демо
              </button>
            </div>
          </div>

          <div class="hero-visual fade-in-right">
            <div class="voice-animation">
              <div class="sound-waves">
                <div class="wave" v-for="i in 5" :key="i" :style="`animation-delay: ${i * 0.1}s`"></div>
              </div>
              <div class="mic-icon pulse-glow">
                <i class="pi pi-microphone"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works-section">
      <div class="container">
        <div class="section-header">
          <h2>Как это работает</h2>
          <p>Три простых шага для создания задач голосом</p>
        </div>

        <div class="steps-grid">
          <div class="step-card fade-in-up" v-for="step in voiceSteps" :key="step.title">
            <div class="step-icon" :style="`background: ${step.color}`">
              <i :class="step.icon"></i>
            </div>
            <h3>{{ step.title }}</h3>
            <p>{{ step.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Command Examples -->
    <section class="examples-section">
      <div class="container">
        <div class="section-header">
          <h2>Примеры голосовых команд</h2>
          <p>AI понимает любые формулировки и контекст</p>
        </div>

        <div class="examples-grid">
          <div class="example-card" v-for="example in commandExamples" :key="example.command">
            <div class="example-header">
              <div class="voice-icon">
                <i class="pi pi-volume-up"></i>
              </div>
              <span class="example-type">{{ example.type }}</span>
            </div>

            <div class="command-text">
              "{{ example.command }}"
            </div>

            <div class="result-preview">
              <div class="result-label">Результат:</div>
              <div class="result-card">
                <i :class="example.resultIcon"></i>
                <div>
                  <strong>{{ example.resultTitle }}</strong>
                  <span v-if="example.resultDetails">{{ example.resultDetails }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features List -->
    <section class="features-detailed">
      <div class="container">
        <div class="features-content">
          <div class="features-text">
            <h2>Умный парсинг команд</h2>
            <p class="lead">
              Наш AI использует передовые технологии для понимания ваших команд
            </p>

            <div class="features-list">
              <div class="feature-item" v-for="feature in voiceFeatures" :key="feature.title">
                <div class="feature-icon-small">
                  <i :class="feature.icon"></i>
                </div>
                <div>
                  <h4>{{ feature.title }}</h4>
                  <p>{{ feature.description }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="features-visual">
            <div class="code-preview glass-card">
              <div class="code-header">
                <span>AI обработка команды</span>
              </div>
              <pre><code>{{ codeExample }}</code></pre>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Technology Stack -->
    <section class="tech-section">
      <div class="container">
        <div class="section-header">
          <h2>Технологии</h2>
          <p>Используем лучшие решения для распознавания речи</p>
        </div>

        <div class="tech-grid">
          <div class="tech-card" v-for="tech in technologies" :key="tech.name">
            <div class="tech-logo">
              <img :src="tech.logo" :alt="tech.name" />
            </div>
            <h3>{{ tech.name }}</h3>
            <p>{{ tech.description }}</p>
            <div class="tech-badge">{{ tech.badge }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Language Support -->
    <section class="languages-section">
      <div class="container">
        <div class="section-header">
          <h2>Поддерживаемые языки</h2>
          <p>Говорите на родном языке</p>
        </div>

        <div class="languages-grid">
          <div class="language-card" v-for="lang in languages" :key="lang.code">
            <div class="flag-icon">{{ lang.flag }}</div>
            <h3>{{ lang.name }}</h3>
            <span class="accuracy">{{ lang.accuracy }}% точность</span>
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
    <Dialog v-model:visible="showDemo" modal header="Демо голосового управления"
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Dialog from 'primevue/dialog'
import LandingLayout from '@/components/landing/LandingLayout.vue'

const router = useRouter()
const showDemo = ref(false)

// Setup scroll animations
onMounted(() => {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible')
      }
    })
  }, { threshold: 0.1 })

  document.querySelectorAll('.fade-in-up, .fade-in-right, .scale-in').forEach(el => {
    observer.observe(el)
  })
})

// Voice steps
const voiceSteps = [
  {
    icon: 'pi pi-microphone',
    title: 'Нажмите и говорите',
    description: 'Нажмите кнопку микрофона и произнесите команду на русском языке',
    color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'
  },
  {
    icon: 'pi pi-sparkles',
    title: 'AI обрабатывает',
    description: 'Whisper транскрибирует речь, а Qwen 2.5 понимает смысл команды',
    color: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'
  },
  {
    icon: 'pi pi-check-circle',
    title: 'Задача создана',
    description: 'Автоматически создается задача с правильными параметрами',
    color: 'linear-gradient(135deg, #13ce66 0%, #00d2d3 100%)'
  }
]

// Command examples
const commandExamples = [
  {
    type: 'Простая задача',
    command: 'Купить молоко завтра',
    resultIcon: 'pi pi-calendar',
    resultTitle: 'Купить молоко',
    resultDetails: 'Завтра, без времени'
  },
  {
    type: 'С приоритетом',
    command: 'Срочно позвонить клиенту сегодня в 15:00',
    resultIcon: 'pi pi-exclamation-triangle',
    resultTitle: 'Позвонить клиенту',
    resultDetails: 'Сегодня, 15:00, Высокий приоритет'
  },
  {
    type: 'С подзадачами',
    command: 'Создай задачу подготовить презентацию с подзадачами: собрать данные, сделать слайды, отрепетировать',
    resultIcon: 'pi pi-list',
    resultTitle: 'Подготовить презентацию',
    resultDetails: '+ 3 подзадачи'
  },
  {
    type: 'Повторяющаяся',
    command: 'Тренировка каждый понедельник и среду в 19:00',
    resultIcon: 'pi pi-refresh',
    resultTitle: 'Тренировка',
    resultDetails: 'Пн, Ср в 19:00, повторяется'
  },
  {
    type: 'Множественные',
    command: 'Создай три задачи: купить хлеб, заправить машину и оплатить счета',
    resultIcon: 'pi pi-clone',
    resultTitle: '3 задачи созданы',
    resultDetails: 'Купить хлеб, Заправить машину, Оплатить счета'
  },
  {
    type: 'Управление',
    command: 'Заверши все задачи на сегодня',
    resultIcon: 'pi pi-check-square',
    resultTitle: 'Задачи завершены',
    resultDetails: 'Все сегодняшние задачи отмечены выполненными'
  }
]

// Voice features
const voiceFeatures = [
  {
    icon: 'pi pi-brain',
    title: 'Понимание контекста',
    description: 'AI понимает относительные даты, время и приоритеты из контекста'
  },
  {
    icon: 'pi pi-wrench',
    title: 'Исправление ошибок',
    description: 'Автоматически исправляет опечатки и грамматические ошибки'
  },
  {
    icon: 'pi pi-language',
    title: 'Естественный язык',
    description: 'Говорите как обычно, без специальных команд'
  },
  {
    icon: 'pi pi-bolt',
    title: 'Быстрая обработка',
    description: 'Команды обрабатываются за 2-3 секунды'
  },
  {
    icon: 'pi pi-shield',
    title: 'Приватность',
    description: 'Обработка происходит локально, данные защищены'
  },
  {
    icon: 'pi pi-sync',
    title: 'Обучение',
    description: 'AI учится на ваших привычках и становится умнее'
  }
]

// Technologies
const technologies = [
  {
    name: 'OpenAI Whisper',
    description: 'Передовая модель для распознавания речи с поддержкой 100+ языков',
    badge: 'Speech-to-Text',
    logo: '/logos/whisper.svg'
  },
  {
    name: 'Qwen 2.5 14B',
    description: 'Мощная языковая модель для понимания команд и контекста',
    badge: 'LLM',
    logo: '/logos/qwen.svg'
  },
  {
    name: 'Centrifugo',
    description: 'Real-time обновления через WebSocket для мгновенной обратной связи',
    badge: 'WebSocket',
    logo: '/logos/centrifugo.svg'
  }
]

// Languages
const languages = [
  { code: 'ru', name: 'Русский', flag: '🇷🇺', accuracy: 98 },
  { code: 'en', name: 'English', flag: '🇬🇧', accuracy: 99 },
  { code: 'uk', name: 'Українська', flag: '🇺🇦', accuracy: 97 },
  { code: 'de', name: 'Deutsch', flag: '🇩🇪', accuracy: 96 },
  { code: 'fr', name: 'Français', flag: '🇫🇷', accuracy: 96 },
  { code: 'es', name: 'Español', flag: '🇪🇸', accuracy: 97 }
]

// Code example
const codeExample = `// Входная команда (голос)
"Создай срочную задачу позвонить клиенту завтра в 14:00"

// AI парсинг
{
  "action": "create_task",
  "parameters": {
    "title": "Позвонить клиенту",
    "due_date": "tomorrow",
    "start_time": "14:00",
    "priority": "urgent"
  },
  "confidence": 0.95
}

// Результат
✅ Задача создана с напоминанием за 15 минут`

// Methods
const tryNow = () => {
  router.push('/register')
}

const startFree = () => {
  router.push('/register')
}
</script>

<style scoped lang="scss">
@import '@/styles/landing.scss';

// Hero section
.feature-hero {
  position: relative;
  padding: 8rem 0 4rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  overflow: hidden;

  .hero-bg {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    opacity: 0.1;

    .wave-pattern {
      position: absolute;
      width: 200%;
      height: 100%;
      background: url('/patterns/wave.svg') repeat-x;
      animation: wave-motion 20s linear infinite;
    }

    .floating-orbs {
      .orb {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        animation: float 10s ease-in-out infinite;

        &.orb-1 {
          width: 200px;
          height: 200px;
          top: 10%;
          left: 10%;
        }

        &.orb-2 {
          width: 150px;
          height: 150px;
          top: 50%;
          right: 10%;
          animation-delay: 3s;
        }

        &.orb-3 {
          width: 100px;
          height: 100px;
          bottom: 10%;
          left: 50%;
          animation-delay: 6s;
        }
      }
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

      @media (max-width: 768px) {
        font-size: 2.5rem;
      }
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

      @media (max-width: 768px) {
        flex-direction: column;
      }
    }

    .btn-large {
      padding: 1rem 2rem;
      font-size: 1.1rem;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .voice-animation {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;

      .sound-waves {
        position: absolute;
        display: flex;
        gap: 0.5rem;

        .wave {
          width: 4px;
          height: 60px;
          background: rgba(255, 255, 255, 0.3);
          border-radius: 2px;
          animation: sound-wave 1s ease-in-out infinite;
        }
      }

      .mic-icon {
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
    }
  }
}

@keyframes wave-motion {
  0% { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

@keyframes sound-wave {
  0%, 100% { transform: scaleY(0.5); }
  50% { transform: scaleY(1); }
}

// How it works
.how-it-works-section {
  padding: 5rem 0;
  background: white;

  .steps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 3rem;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  .step-card {
    text-align: center;

    .step-icon {
      width: 80px;
      height: 80px;
      margin: 0 auto 1.5rem;
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 2rem;
      i.pi { color: white !important; }
    }

    h3 {
      margin-bottom: 1rem;
      color: var(--gray-900);
    }

    p {
      color: var(--gray-600);
      line-height: 1.6;
    }
  }
}

// Examples section
.examples-section {
  padding: 5rem 0;
  background: var(--gray-50);

  .examples-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
  }

  .example-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }

    .example-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1rem;

      .voice-icon {
        width: 40px;
        height: 40px;
        background: var(--gradient-primary);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
      }

      .example-type {
        font-size: 0.875rem;
        color: var(--gray-500);
        font-weight: 600;
      }
    }

    .command-text {
      font-size: 1.1rem;
      color: var(--gray-900);
      font-style: italic;
      margin-bottom: 1.5rem;
      padding: 1rem;
      background: var(--gray-50);
      border-radius: 8px;
      border-left: 3px solid var(--primary);
    }

    .result-preview {
      .result-label {
        font-size: 0.875rem;
        color: var(--gray-500);
        margin-bottom: 0.5rem;
      }

      .result-card {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: var(--success) + '10';
        border-radius: 8px;
        border: 1px solid var(--success) + '30';

        i {
          color: var(--success);
          font-size: 1.5rem;
        }

        strong {
          display: block;
          color: var(--gray-900);
          margin-bottom: 0.25rem;
        }

        span {
          font-size: 0.875rem;
          color: var(--gray-600);
        }
      }
    }
  }
}

// Features detailed
.features-detailed {
  padding: 5rem 0;
  background: white;

  .features-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  .features-list {
    margin-top: 2rem;

    .feature-item {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;

      .feature-icon-small {
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

      h4 {
        margin-bottom: 0.5rem;
        color: var(--gray-900);
      }

      p {
        color: var(--gray-600);
        line-height: 1.5;
      }
    }
  }

  .code-preview {
    padding: 2rem;

    .code-header {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 1rem;
      color: var(--primary);
      font-weight: 600;
    }

    pre {
      background: var(--gray-900);
      color: #f8f8f2;
      padding: 1.5rem;
      border-radius: 12px;
      overflow-x: auto;
      font-size: 0.9rem;
      line-height: 1.6;
    }
  }
}

// Tech section
.tech-section {
  padding: 5rem 0;
  background: var(--gray-50);

  .tech-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 3rem;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  .tech-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }

    .tech-logo {
      width: 80px;
      height: 80px;
      margin: 0 auto 1rem;

      img {
        width: 100%;
        height: 100%;
        object-fit: contain;
      }
    }

    h3 {
      margin-bottom: 0.5rem;
      color: var(--gray-900);
    }

    p {
      color: var(--gray-600);
      margin-bottom: 1rem;
      line-height: 1.5;
    }

    .tech-badge {
      display: inline-block;
      background: var(--gradient-primary);
      color: white;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.875rem;
    }
  }
}

// Languages section
.languages-section {
  padding: 5rem 0;
  background: white;

  .languages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
  }

  .language-card {
    text-align: center;
    padding: 1.5rem;
    border-radius: 16px;
    background: var(--gray-50);
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-5px);
      background: white;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .flag-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
    }

    h3 {
      margin-bottom: 0.5rem;
      color: var(--gray-900);
    }

    .accuracy {
      color: var(--success);
      font-weight: 600;
      font-size: 0.875rem;
    }
  }
}

// CTA section
.feature-cta {
  padding: 5rem 0;

  .cta-card {
    padding: 4rem;
    border-radius: 30px;
    text-align: center;
    color: white;

    h2 {
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }

    p {
      font-size: 1.25rem;
      margin-bottom: 2rem;
      opacity: 0.9;
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

.demo-content {
  video {
    width: 100%;
    border-radius: 12px;
  }
}

.section-header {
  text-align: center;
  margin-bottom: 3rem;

  h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: var(--gray-900);
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
</style>