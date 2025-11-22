<template>
  <LandingLayout>
    <!-- Hero Section -->
    <section class="compare-hero">
      <div class="container">
        <div class="hero-content" ref="heroRef">
          <div class="comparison-header">
            <div class="product-card our-product">
              <div class="product-logo">
                <svg width="60" height="60" viewBox="0 0 60 60" fill="none">
                  <rect width="60" height="60" rx="15" fill="url(#taskflow-gradient)"/>
                  <path d="M18 22h24M18 30h15M18 38h21" stroke="white" stroke-width="3" stroke-linecap="round"/>
                  <defs>
                    <linearGradient id="taskflow-gradient" x1="0" y1="0" x2="60" y2="60">
                      <stop offset="0%" stop-color="#667eea"/>
                      <stop offset="100%" stop-color="#764ba2"/>
                    </linearGradient>
                  </defs>
                </svg>
              </div>
              <h2>TaskFlow</h2>
              <div class="badge winner">Рекомендуем</div>
            </div>

            <div class="vs-divider">
              <span>VS</span>
            </div>

            <div class="product-card competitor">
              <div class="product-logo">
                <svg width="60" height="60" viewBox="0 0 60 60" fill="none">
                  <rect width="60" height="60" rx="15" fill="#4772FA"/>
                  <path d="M15 30l10 10 20-20" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <h2>TickTick</h2>
            </div>
          </div>

          <h1 class="comparison-title">
            TaskFlow vs TickTick: <span class="gradient-text">Детальное сравнение</span>
          </h1>

          <p class="comparison-subtitle">
            Сравните два популярных менеджера задач и выберите лучший для ваших потребностей
          </p>
        </div>
      </div>
    </section>

    <!-- Quick Overview -->
    <section class="quick-overview">
      <div class="container">
        <div class="overview-cards">
          <div class="overview-card taskflow fade-in-up">
            <h3>TaskFlow выигрывает в:</h3>
            <ul>
              <li><i class="pi pi-check"></i> Полное голосовое управление через Telegram</li>
              <li><i class="pi pi-check"></i> Цена в 2 раза дешевле</li>
              <li><i class="pi pi-check"></i> AI на естественном языке</li>
              <li><i class="pi pi-check"></i> Обработка PDF, DOC, изображений</li>
              <li><i class="pi pi-check"></i> Освоение за 5 минут</li>
            </ul>
          </div>

          <div class="overview-card ticktick fade-in-up">
            <h3>TickTick лучше в:</h3>
            <ul>
              <li><i class="pi pi-check"></i> Продвинутая календарная интеграция</li>
              <li><i class="pi pi-check"></i> Трекинг привычек</li>
              <li><i class="pi pi-check"></i> Таймер Pomodoro</li>
              <li><i class="pi pi-check"></i> Eisenhower матрица</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- Detailed Comparison Table -->
    <section class="comparison-table-section">
      <div class="container">
        <div class="section-header">
          <h2>Полное сравнение функций</h2>
          <p>Все возможности в одной таблице</p>
        </div>

        <div class="comparison-table fade-in-up">
          <table>
            <thead>
              <tr>
                <th>Критерий</th>
                <th class="taskflow-col">
                  <div class="product-header">
                    <span>TaskFlow</span>
                    <span class="badge">Наш выбор</span>
                  </div>
                </th>
                <th class="ticktick-col">TickTick</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="feature in comparisonFeatures" :key="feature.name" class="feature-row">
                <td class="feature-name">
                  <strong>{{ feature.name }}</strong>
                  <span class="feature-description">{{ feature.description }}</span>
                </td>
                <td class="taskflow-col">
                  <div class="feature-value">
                    <i v-if="feature.taskflow === true" class="pi pi-check-circle check-icon"></i>
                    <i v-else-if="feature.taskflow === false" class="pi pi-times-circle cross-icon"></i>
                    <span v-else :class="{ 'highlight': feature.taskflowWins }">{{ feature.taskflow }}</span>
                  </div>
                </td>
                <td class="ticktick-col">
                  <div class="feature-value">
                    <i v-if="feature.ticktick === true" class="pi pi-check-circle check-icon"></i>
                    <i v-else-if="feature.ticktick === false" class="pi pi-times-circle cross-icon"></i>
                    <span v-else :class="{ 'highlight': feature.ticktickWins }">{{ feature.ticktick }}</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Pricing Comparison -->
    <section class="pricing-comparison">
      <div class="container">
        <div class="section-header">
          <h2>Сравнение цен</h2>
          <p>TaskFlow — в 2 раза дешевле при большей функциональности</p>
        </div>

        <div class="pricing-cards">
          <div class="pricing-card taskflow fade-in-up">
            <div class="product-label">TaskFlow Pro</div>
            <div class="price">
              <span class="amount">3 480₽</span>
              <span class="period">/год</span>
            </div>
            <div class="price-monthly">290₽/месяц</div>
            <ul class="price-features">
              <li><i class="pi pi-check"></i> Голосовое управление</li>
              <li><i class="pi pi-check"></i> AI-помощник</li>
              <li><i class="pi pi-check"></i> Telegram интеграция</li>
              <li><i class="pi pi-check"></i> Обработка файлов</li>
              <li><i class="pi pi-check"></i> Неограниченные задачи</li>
              <li><i class="pi pi-check"></i> Продвинутая аналитика</li>
            </ul>
            <button class="btn-primary w-full" @click="startTrial">Попробовать бесплатно</button>
          </div>

          <div class="pricing-card ticktick fade-in-up">
            <div class="product-label">TickTick Premium</div>
            <div class="price">
              <span class="amount">~7 200₽</span>
              <span class="period">/год</span>
            </div>
            <div class="price-monthly">$27.99/год</div>
            <ul class="price-features">
              <li><i class="pi pi-check"></i> Календарь</li>
              <li><i class="pi pi-check"></i> Трекинг привычек</li>
              <li><i class="pi pi-check"></i> Pomodoro таймер</li>
              <li><i class="pi pi-times"></i> Нет голосового управления</li>
              <li><i class="pi pi-times"></i> Нет Telegram</li>
              <li><i class="pi pi-times"></i> Нет обработки файлов</li>
            </ul>
            <button class="btn-secondary w-full">Сайт TickTick</button>
          </div>
        </div>
      </div>
    </section>

    <!-- Verdict Section -->
    <section class="verdict-section">
      <div class="container">
        <div class="verdict-card fade-in-up">
          <div class="verdict-icon">
            <i class="pi pi-star-fill"></i>
          </div>
          <h2>Наш вердикт</h2>
          <div class="verdict-content">
            <div class="verdict-item">
              <h3>Выберите TaskFlow, если:</h3>
              <ul>
                <li>Вам важна простота и скорость освоения</li>
                <li>Хотите голосовое управление на русском</li>
                <li>Используете Telegram для коммуникаций</li>
                <li>Нужна обработка файлов с AI</li>
                <li>Цена имеет значение</li>
              </ul>
            </div>
            <div class="verdict-item">
              <h3>Выберите TickTick, если:</h3>
              <ul>
                <li>Вам нужен трекинг привычек</li>
                <li>Важна детальная работа с календарем</li>
                <li>Используете Pomodoro технику</li>
                <li>Нужна Eisenhower матрица</li>
              </ul>
            </div>
          </div>
          <p class="verdict-summary">
            <strong>TaskFlow — для простоты и AI-функций, TickTick — для детальной продуктивности</strong>
          </p>
        </div>
      </div>
    </section>

    <!-- User Reviews -->
    <section class="user-reviews">
      <div class="container">
        <div class="section-header">
          <h2>Что говорят пользователи</h2>
          <p>Реальные отзывы тех, кто сравнивал оба приложения</p>
        </div>

        <div class="reviews-grid">
          <div class="review-card fade-in-up" v-for="review in userReviews" :key="review.name">
            <div class="rating">
              <i class="pi pi-star-fill" v-for="n in 5" :key="n"></i>
            </div>
            <p class="review-text">{{ review.text }}</p>
            <div class="review-author">
              <div class="avatar-placeholder">{{ review.name[0] }}</div>
              <div>
                <h4>{{ review.name }}</h4>
                <span>{{ review.role }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Final CTA -->
    <section class="final-cta">
      <div class="container">
        <div class="cta-card animated-gradient">
          <h2>Попробуйте TaskFlow бесплатно</h2>
          <p>14 дней полного доступа без ограничений</p>

          <div class="cta-features">
            <div class="cta-feature">
              <i class="pi pi-calendar"></i>
              <span>14 дней бесплатно</span>
            </div>
            <div class="cta-feature">
              <i class="pi pi-credit-card"></i>
              <span>Без карты</span>
            </div>
            <div class="cta-feature">
              <i class="pi pi-bolt"></i>
              <span>Старт за 5 минут</span>
            </div>
          </div>

          <div class="cta-actions">
            <button class="btn-white btn-large" @click="startTrial">
              Начать бесплатный период
            </button>
          </div>
        </div>
      </div>
    </section>
  </LandingLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import LandingLayout from '@/components/landing/LandingLayout.vue'

const router = useRouter()
const heroRef = ref<HTMLElement | null>(null)

// Intersection Observer for animations
onMounted(() => {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible')
        }
      })
    },
    { threshold: 0.1 }
  )

  document.querySelectorAll('.fade-in-up').forEach((el) => {
    observer.observe(el)
  })
})

// Comparison features
const comparisonFeatures = [
  {
    name: 'Цена (год)',
    description: 'Стоимость годовой подписки',
    taskflow: '3 480₽',
    ticktick: '~7 200₽ ($27.99)',
    taskflowWins: true
  },
  {
    name: 'Голосовое управление',
    description: 'Создание задач голосом',
    taskflow: '✅ Полное через Telegram',
    ticktick: '⚠️ Только диктовка',
    taskflowWins: true
  },
  {
    name: 'AI-возможности',
    description: 'Умный парсинг команд',
    taskflow: '✅ Естественный язык',
    ticktick: '✅ AI-планирование'
  },
  {
    name: 'Календарная интеграция',
    description: 'Работа с календарем',
    taskflow: '⚠️ Базовая',
    ticktick: '✅ Продвинутая',
    ticktickWins: true
  },
  {
    name: 'Трекинг привычек',
    description: 'Формирование привычек',
    taskflow: false,
    ticktick: true
  },
  {
    name: 'Telegram интеграция',
    description: 'Управление через бота',
    taskflow: true,
    ticktick: false
  },
  {
    name: 'Обработка файлов',
    description: 'PDF, DOC, изображения',
    taskflow: '✅ PDF, DOC, изображения',
    ticktick: '⚠️ Только вложения',
    taskflowWins: true
  },
  {
    name: 'Скорость освоения',
    description: 'Время до продуктивности',
    taskflow: '✅ 5 минут',
    ticktick: '⚠️ 2–3 дня',
    taskflowWins: true
  },
  {
    name: 'Pomodoro таймер',
    description: 'Встроенный таймер',
    taskflow: false,
    ticktick: true
  },
  {
    name: 'Eisenhower матрица',
    description: 'Приоритизация задач',
    taskflow: false,
    ticktick: true
  }
]

// User reviews
const userReviews = [
  {
    name: 'Дмитрий Соколов',
    role: 'Маркетолог',
    text: 'Перешел с TickTick на TaskFlow из-за голосового управления. Теперь создаю задачи на ходу через Telegram — это невероятно удобно!'
  },
  {
    name: 'Анна Петрова',
    role: 'Фрилансер',
    text: 'TickTick был слишком сложным для меня. TaskFlow освоила за 5 минут, и он делает все что нужно, плюс AI-обработка файлов.'
  },
  {
    name: 'Сергей Иванов',
    role: 'Предприниматель',
    text: 'Цена в 2 раза ниже, а функциональность выше. TaskFlow — это то, чем должен был стать TickTick.'
  }
]

// Methods
const startTrial = () => {
  router.push('/register')
}
</script>

<style scoped lang="scss">
@import '@/styles/landing.scss';

.fade-in-up {
  opacity: 0;
  transform: translateY(30px);
  transition: all 0.6s ease-out;

  &.visible {
    opacity: 1;
    transform: translateY(0);
  }
}

// Compare hero
.compare-hero {
  padding: 8rem 0 4rem;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);

  .hero-content {
    text-align: center;
  }

  .comparison-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 3rem;
    margin-bottom: 3rem;

    @media (max-width: 768px) {
      flex-direction: column;
      gap: 2rem;
    }

    .product-card {
      text-align: center;

      .product-logo {
        width: 80px;
        height: 80px;
        margin: 0 auto 1rem;

        img, svg {
          width: 100%;
          height: 100%;
          object-fit: contain;
        }
      }

      h2 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
      }

      &.our-product {
        .badge.winner {
          background: var(--gradient-primary);
          color: white;
          padding: 0.25rem 1rem;
          border-radius: 20px;
          font-size: 0.875rem;
        }
      }
    }

    .vs-divider {
      width: 60px;
      height: 60px;
      background: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      font-weight: bold;
      color: var(--gray-700);
    }
  }

  .comparison-title {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: var(--gray-900);

    @media (max-width: 768px) {
      font-size: 2rem;
    }
  }

  .comparison-subtitle {
    font-size: 1.25rem;
    color: var(--gray-600);
  }
}

// Quick overview
.quick-overview {
  padding: 5rem 0;
  background: white;

  .overview-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  .overview-card {
    padding: 2rem;
    border-radius: 16px;

    &.taskflow {
      background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
      border: 2px solid var(--primary);
    }

    &.ticktick {
      background: #f8f8f8;
      border: 2px solid #e0e0e0;
    }

    h3 {
      margin-bottom: 1.5rem;
      color: var(--gray-900);
    }

    ul {
      list-style: none;
      padding: 0;

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
  }
}

// Comparison table
.comparison-table-section {
  padding: 5rem 0;
  background: var(--gray-50);

  .comparison-table {
    overflow-x: auto;

    table {
      width: 100%;
      background: white;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);

      thead {
        background: var(--gray-900);
        color: white;

        th {
          padding: 1.5rem 1rem;
          text-align: left;

          &.taskflow-col {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          }

          .product-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;

            .badge {
              background: rgba(255, 255, 255, 0.2);
              padding: 0.25rem 0.5rem;
              border-radius: 12px;
              font-size: 0.75rem;
            }
          }
        }
      }

      tbody {
        .feature-row {
          transition: all 0.3s ease;

          &:hover {
            background: var(--gray-50);
          }

          td {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid var(--gray-200);
          }

          .feature-name {
            strong {
              display: block;
              color: var(--gray-900);
              margin-bottom: 0.25rem;
            }

            .feature-description {
              font-size: 0.875rem;
              color: var(--gray-500);
            }
          }

          .feature-value {
            .check-icon {
              color: var(--success);
              font-size: 1.5rem;
            }

            .cross-icon {
              color: var(--danger);
              font-size: 1.5rem;
            }

            .highlight {
              color: var(--success);
              font-weight: 600;
            }
          }
        }
      }
    }
  }
}

// Pricing comparison
.pricing-comparison {
  padding: 5rem 0;
  background: white;

  .pricing-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-top: 3rem;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  .pricing-card {
    padding: 2rem;
    border-radius: 16px;
    text-align: center;

    &.taskflow {
      background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
      border: 2px solid var(--primary);
    }

    &.ticktick {
      background: #f8f8f8;
      border: 2px solid #e0e0e0;
    }

    .product-label {
      font-size: 1.25rem;
      font-weight: bold;
      margin-bottom: 1rem;
      color: var(--gray-900);
    }

    .price {
      margin-bottom: 0.5rem;

      .amount {
        font-size: 3rem;
        font-weight: 800;
        color: var(--gray-900);
      }

      .period {
        color: var(--gray-500);
      }
    }

    .price-monthly {
      color: var(--gray-500);
      margin-bottom: 2rem;
    }

    .price-features {
      list-style: none;
      padding: 0;
      margin-bottom: 2rem;

      li {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
        text-align: left;

        i {
          &.pi-check {
            color: var(--success);
          }

          &.pi-times {
            color: var(--danger);
          }
        }
      }
    }
  }
}

// Verdict section
.verdict-section {
  padding: 5rem 0;
  background: var(--gray-50);

  .verdict-card {
    background: white;
    border-radius: 24px;
    padding: 3rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
    text-align: center;

    .verdict-icon {
      width: 80px;
      height: 80px;
      background: var(--gradient-primary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 2rem;

      i {
        font-size: 2.5rem;
        color: white;
      }
    }

    h2 {
      font-size: 2rem;
      margin-bottom: 2rem;
      color: var(--gray-900);
    }

    .verdict-content {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      text-align: left;
      margin-bottom: 2rem;

      @media (max-width: 768px) {
        grid-template-columns: 1fr;
      }

      .verdict-item {
        h3 {
          font-size: 1.25rem;
          margin-bottom: 1rem;
          color: var(--gray-900);
        }

        ul {
          list-style: none;
          padding: 0;

          li {
            padding: 0.5rem 0;
            color: var(--gray-600);
            position: relative;
            padding-left: 1.5rem;

            &::before {
              content: '•';
              position: absolute;
              left: 0;
              color: var(--primary);
            }
          }
        }
      }
    }

    .verdict-summary {
      font-size: 1.125rem;
      color: var(--gray-700);
      padding-top: 2rem;
      border-top: 1px solid var(--gray-200);
    }
  }
}

// User reviews
.user-reviews {
  padding: 5rem 0;
  background: white;

  .reviews-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 3rem;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  .review-card {
    background: var(--gray-50);
    padding: 2rem;
    border-radius: 16px;

    .rating {
      color: #fbbf24;
      margin-bottom: 1rem;
    }

    .review-text {
      color: var(--gray-700);
      line-height: 1.6;
      margin-bottom: 1.5rem;
    }

    .review-author {
      display: flex;
      align-items: center;
      gap: 1rem;

      .avatar-placeholder {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: var(--gradient-primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.25rem;
      }

      h4 {
        margin: 0;
        color: var(--gray-900);
      }

      span {
        font-size: 0.875rem;
        color: var(--gray-500);
      }
    }
  }
}

// Final CTA
.final-cta {
  padding: 5rem 0;
  background: var(--gray-50);

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

    .cta-features {
      display: flex;
      justify-content: center;
      gap: 3rem;
      margin-bottom: 3rem;

      @media (max-width: 768px) {
        flex-direction: column;
        gap: 1rem;
      }

      .cta-feature {
        display: flex;
        align-items: center;
        gap: 0.5rem;

        i {
          font-size: 1.5rem;
        }
      }
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

.btn-large {
  padding: 1rem 2rem;
  font-size: 1.1rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
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

.w-full {
  width: 100%;
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
