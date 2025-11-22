<template>
  <LandingLayout>
    <!-- Hero Section -->
    <section class="compare-hero">
      <div class="container">
        <div class="hero-content">
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
                <img src="/logos/todoist.svg" alt="Todoist" />
              </div>
              <h2>Todoist</h2>
            </div>
          </div>

          <h1 class="comparison-title">
            TaskFlow vs Todoist: <span class="gradient-text-light">Детальное сравнение</span>
          </h1>

          <p class="comparison-subtitle">
            Узнайте, почему TaskFlow — лучший выбор для управления задачами с AI и голосовым управлением
          </p>
        </div>
      </div>
    </section>

    <!-- Quick Overview -->
    <section class="quick-overview">
      <div class="container">
        <div class="overview-cards">
          <div class="overview-card taskflow">
            <h3>TaskFlow выигрывает в:</h3>
            <ul>
              <li><i class="pi pi-check"></i> Голосовое управление на русском</li>
              <li><i class="pi pi-check"></i> AI-помощник для парсинга команд</li>
              <li><i class="pi pi-check"></i> Интеграция с Telegram</li>
              <li><i class="pi pi-check"></i> Умная обработка файлов</li>
              <li><i class="pi pi-check"></i> Продвинутая аналитика</li>
              <li><i class="pi pi-check"></i> Более доступная цена</li>
            </ul>
          </div>

          <div class="overview-card todoist">
            <h3>Todoist лучше в:</h3>
            <ul>
              <li><i class="pi pi-check"></i> Больше интеграций (100+)</li>
              <li><i class="pi pi-check"></i> Карма-система мотивации</li>
              <li><i class="pi pi-check"></i> Шаблоны проектов</li>
              <li><i class="pi pi-check"></i> Более долгая история (с 2007)</li>
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

        <div class="comparison-table">
          <table>
            <thead>
              <tr>
                <th>Функция</th>
                <th class="taskflow-col">
                  <div class="product-header">
                    <span>TaskFlow</span>
                    <span class="badge">Наш выбор</span>
                  </div>
                </th>
                <th class="todoist-col">Todoist</th>
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
                    <span v-else>{{ feature.taskflow }}</span>
                  </div>
                </td>
                <td class="todoist-col">
                  <div class="feature-value">
                    <i v-if="feature.todoist === true" class="pi pi-check-circle check-icon"></i>
                    <i v-else-if="feature.todoist === false" class="pi pi-times-circle cross-icon"></i>
                    <span v-else>{{ feature.todoist }}</span>
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
          <p>Больше возможностей за меньшие деньги</p>
        </div>

        <div class="pricing-cards">
          <div class="pricing-card taskflow">
            <div class="product-label">TaskFlow</div>
            <div class="price">
              <span class="amount">299₽</span>
              <span class="period">/месяц</span>
            </div>
            <ul class="price-features">
              <li><i class="pi pi-check"></i> Неограниченные задачи</li>
              <li><i class="pi pi-check"></i> Голосовое управление</li>
              <li><i class="pi pi-check"></i> AI-помощник</li>
              <li><i class="pi pi-check"></i> Telegram интеграция</li>
              <li><i class="pi pi-check"></i> Аналитика и отчеты</li>
              <li><i class="pi pi-check"></i> API доступ</li>
            </ul>
            <button class="btn-primary w-full">Попробовать бесплатно</button>
          </div>

          <div class="pricing-card todoist">
            <div class="product-label">Todoist Pro</div>
            <div class="price">
              <span class="amount">399₽</span>
              <span class="period">/месяц</span>
            </div>
            <ul class="price-features">
              <li><i class="pi pi-check"></i> 300 проектов</li>
              <li><i class="pi pi-check"></i> 25 человек на проект</li>
              <li><i class="pi pi-times"></i> Нет голосового управления</li>
              <li><i class="pi pi-times"></i> Нет AI-помощника</li>
              <li><i class="pi pi-check"></i> Напоминания</li>
              <li><i class="pi pi-check"></i> Шаблоны</li>
            </ul>
            <button class="btn-secondary w-full">Сайт Todoist</button>
          </div>
        </div>
      </div>
    </section>

    <!-- User Reviews -->
    <section class="user-reviews">
      <div class="container">
        <div class="section-header">
          <h2>Что говорят пользователи</h2>
          <p>Реальные отзывы тех, кто перешел с Todoist на TaskFlow</p>
        </div>

        <div class="reviews-grid">
          <div class="review-card" v-for="review in userReviews" :key="review.name">
            <div class="rating">
              <i class="pi pi-star-fill" v-for="n in 5" :key="n"></i>
            </div>
            <p class="review-text">{{ review.text }}</p>
            <div class="review-author">
              <img :src="review.avatar" :alt="review.name" />
              <div>
                <h4>{{ review.name }}</h4>
                <span>Перешел с {{ review.from }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Migration Section -->
    <section class="migration-section">
      <div class="container">
        <div class="migration-card">
          <div class="migration-content">
            <h2>Легкий переход с Todoist</h2>
            <p>Импортируйте все ваши задачи и проекты за 2 минуты</p>

            <div class="migration-steps">
              <div class="step" v-for="(step, index) in migrationSteps" :key="index">
                <div class="step-number">{{ index + 1 }}</div>
                <p>{{ step }}</p>
              </div>
            </div>

            <button class="btn-primary btn-large">
              <i class="pi pi-download"></i>
              Начать миграцию
            </button>
          </div>

          <div class="migration-visual">
            <img src="https://via.placeholder.com/400x300/6366f1/ffffff?text=Migration" alt="Migration" />
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
              <button class="btn-white btn-large" @click="startTrial">
                <i class="pi pi-play"></i>
                Начать бесплатно
              </button>
              <button class="btn-cta-outline btn-large" @click="watchComparison">
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
  </LandingLayout>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import LandingLayout from '@/components/landing/LandingLayout.vue'

const router = useRouter()

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

// Comparison features
const comparisonFeatures = [
  {
    name: 'Голосовое управление',
    description: 'Создание задач голосом',
    taskflow: '✅ На русском',
    todoist: false
  },
  {
    name: 'AI-помощник',
    description: 'Умный парсинг команд',
    taskflow: '✅ Qwen 2.5',
    todoist: false
  },
  {
    name: 'Telegram интеграция',
    description: 'Управление через бота',
    taskflow: true,
    todoist: false
  },
  {
    name: 'Количество задач',
    description: 'Максимум в месяц',
    taskflow: 'Неограниченно',
    todoist: '300 проектов'
  },
  {
    name: 'Подзадачи',
    description: 'Вложенность уровней',
    taskflow: 'Неограниченно',
    todoist: '4 уровня'
  },
  {
    name: 'Напоминания',
    description: 'Уведомления о задачах',
    taskflow: true,
    todoist: true
  },
  {
    name: 'Календарь',
    description: 'Визуализация задач',
    taskflow: true,
    todoist: true
  },
  {
    name: 'Совместная работа',
    description: 'Командные проекты',
    taskflow: true,
    todoist: true
  },
  {
    name: 'Файлы и вложения',
    description: 'Загрузка документов',
    taskflow: '✅ + AI обработка',
    todoist: '✅ Базовая'
  },
  {
    name: 'Аналитика',
    description: 'Статистика продуктивности',
    taskflow: '✅ Продвинутая',
    todoist: '✅ Базовая'
  },
  {
    name: 'API',
    description: 'Интеграция с сервисами',
    taskflow: true,
    todoist: true
  },
  {
    name: 'Мобильные приложения',
    description: 'iOS и Android',
    taskflow: 'В разработке',
    todoist: true
  },
  {
    name: 'Офлайн режим',
    description: 'Работа без интернета',
    taskflow: true,
    todoist: true
  },
  {
    name: 'Экспорт данных',
    description: 'Выгрузка задач',
    taskflow: 'CSV, JSON, PDF',
    todoist: 'CSV'
  },
  {
    name: 'Поддержка',
    description: 'Техническая помощь',
    taskflow: '24/7 чат + email',
    todoist: 'Email'
  }
]

// User reviews
const userReviews = [
  {
    name: 'Михаил Королев',
    from: 'Todoist',
    avatar: '/avatars/mikhail.jpg',
    text: 'Использовал Todoist 3 года, но голосовое управление в TaskFlow — это революция! Теперь создаю задачи в 5 раз быстрее.'
  },
  {
    name: 'Елена Васильева',
    from: 'Todoist Pro',
    avatar: '/avatars/elena.jpg',
    text: 'TaskFlow дешевле и функциональнее. Особенно радует AI-помощник, который понимает даже сложные команды.'
  },
  {
    name: 'Андрей Новиков',
    from: 'Todoist Business',
    avatar: '/avatars/andrey.jpg',
    text: 'Перевел всю команду на TaskFlow. Интеграция с Telegram решила все проблемы с коммуникацией.'
  }
]

// Migration steps
const migrationSteps = [
  'Экспортируйте данные из Todoist в CSV',
  'Загрузите файл в TaskFlow',
  'Автоматический импорт всех задач и проектов',
  'Готово! Все ваши данные на месте'
]

// Methods
const startTrial = () => {
  router.push('/register')
}

const watchComparison = () => {
  // Open comparison video modal
  console.log('Watch comparison video')
}
</script>

<style scoped lang="scss">
@import '@/styles/landing.scss';

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

        img {
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

    &.todoist {
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

    &.todoist {
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
      margin-bottom: 2rem;

      .amount {
        font-size: 3rem;
        font-weight: 800;
        color: var(--gray-900);
      }

      .period {
        color: var(--gray-500);
      }
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

// User reviews
.user-reviews {
  padding: 5rem 0;
  background: var(--gray-50);

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
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);

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

      img {
        width: 48px;
        height: 48px;
        border-radius: 50%;
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

// Migration section
.migration-section {
  padding: 5rem 0;
  background: white;

  .migration-card {
    background: var(--gradient-primary);
    border-radius: 30px;
    padding: 3rem;
    color: white;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
      text-align: center;
    }

    h2 {
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }

    p {
      font-size: 1.25rem;
      margin-bottom: 2rem;
      opacity: 0.9;
    }

    .migration-steps {
      display: grid;
      gap: 1rem;
      margin-bottom: 2rem;

      .step {
        display: flex;
        align-items: center;
        gap: 1rem;

        .step-number {
          width: 32px;
          height: 32px;
          background: rgba(255, 255, 255, 0.2);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-weight: bold;
        }

        p {
          margin: 0;
          font-size: 1rem;
        }
      }
    }

    .migration-visual {
      img {
        width: 100%;
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