<template>
  <LandingLayout>
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
            <div class="vs-divider"><span>VS</span></div>
            <div class="product-card competitor">
              <div class="product-logo">
                <svg width="60" height="60" viewBox="0 0 60 60" fill="none">
                  <rect width="60" height="60" rx="15" fill="#FFBB00"/>
                  <path d="M20 15h20c2.8 0 5 2.2 5 5v20c0 2.8-2.2 5-5 5H20c-2.8 0-5-2.2-5-5V20c0-2.8 2.2-5 5-5z" fill="white"/>
                  <rect x="22" y="22" width="16" height="3" rx="1.5" fill="#FFBB00"/>
                  <rect x="22" y="28" width="12" height="3" rx="1.5" fill="#FFBB00"/>
                  <rect x="22" y="34" width="14" height="3" rx="1.5" fill="#FFBB00"/>
                </svg>
              </div>
              <h2>Google Keep</h2>
            </div>
          </div>
          <h1 class="comparison-title">TaskFlow vs Google Keep: <span class="gradient-text-light">Заметки или управление задачами?</span></h1>
          <p class="comparison-subtitle">Google Keep для заметок, TaskFlow для управления жизнью</p>
        </div>
      </div>
    </section>

    <section class="key-difference">
      <div class="container">
        <div class="difference-card fade-in-up">
          <div class="difference-icon"><i class="pi pi-exclamation-triangle"></i></div>
          <div class="difference-content">
            <h3>Главное отличие</h3>
            <p>Google Keep — это приложение для <strong>заметок</strong>, а TaskFlow — полноценная система <strong>управления задачами</strong> с AI, голосовым управлением и аналитикой.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="quick-overview">
      <div class="container">
        <div class="overview-cards">
          <div class="overview-card taskflow fade-in-up">
            <h3>TaskFlow выигрывает в:</h3>
            <ul>
              <li><i class="pi pi-check"></i> Полноценное управление задачами</li>
              <li><i class="pi pi-check"></i> AI-помощник с умным анализом</li>
              <li><i class="pi pi-check"></i> Голосовые команды (не диктовка)</li>
              <li><i class="pi pi-check"></i> Умные напоминания с контекстом</li>
              <li><i class="pi pi-check"></i> Обработка PDF, Word</li>
              <li><i class="pi pi-check"></i> Продвинутая аналитика</li>
            </ul>
          </div>
          <div class="overview-card keep fade-in-up">
            <h3>Google Keep лучше в:</h3>
            <ul>
              <li><i class="pi pi-check"></i> Бесплатный навсегда</li>
              <li><i class="pi pi-check"></i> Простота использования</li>
              <li><i class="pi pi-check"></i> Интеграция с Google Workspace</li>
              <li><i class="pi pi-check"></i> Цветные заметки</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <section class="comparison-table-section">
      <div class="container">
        <div class="section-header"><h2>Полное сравнение функций</h2><p>Заметки vs управление задачами</p></div>
        <div class="comparison-table fade-in-up">
          <table>
            <thead>
              <tr>
                <th>Критерий</th>
                <th class="taskflow-col"><div class="product-header"><span>TaskFlow</span><span class="badge">Наш выбор</span></div></th>
                <th class="keep-col">Google Keep</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="feature in comparisonFeatures" :key="feature.name" class="feature-row">
                <td class="feature-name"><strong>{{ feature.name }}</strong><span class="feature-description">{{ feature.description }}</span></td>
                <td class="taskflow-col"><div class="feature-value"><i v-if="feature.taskflow === true" class="pi pi-check-circle check-icon"></i><i v-else-if="feature.taskflow === false" class="pi pi-times-circle cross-icon"></i><span v-else :class="{ 'highlight': feature.taskflowWins }">{{ feature.taskflow }}</span></div></td>
                <td class="keep-col"><div class="feature-value"><i v-if="feature.keep === true" class="pi pi-check-circle check-icon"></i><i v-else-if="feature.keep === false" class="pi pi-times-circle cross-icon"></i><span v-else :class="{ 'highlight': feature.keepWins }">{{ feature.keep }}</span></div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="pricing-comparison">
      <div class="container">
        <div class="section-header"><h2>Сравнение цен</h2><p>Бесплатное vs профессиональное</p></div>
        <div class="pricing-cards">
          <div class="pricing-card taskflow fade-in-up">
            <div class="product-label">TaskFlow Pro</div>
            <div class="price"><span class="amount">290₽</span><span class="period">/месяц</span></div>
            <ul class="price-features">
              <li><i class="pi pi-check"></i> Управление задачами</li>
              <li><i class="pi pi-check"></i> AI-анализ и помощник</li>
              <li><i class="pi pi-check"></i> Голосовые команды</li>
              <li><i class="pi pi-check"></i> Умные напоминания</li>
              <li><i class="pi pi-check"></i> PDF, Word обработка</li>
              <li><i class="pi pi-check"></i> Telegram интеграция</li>
            </ul>
            <button class="btn-primary w-full" @click="startTrial">Попробовать бесплатно</button>
          </div>
          <div class="pricing-card keep fade-in-up">
            <div class="product-label">Google Keep</div>
            <div class="price"><span class="amount">Бесплатно</span><span class="period">навсегда</span></div>
            <ul class="price-features">
              <li><i class="pi pi-check"></i> Заметки и списки</li>
              <li><i class="pi pi-check"></i> OCR для изображений</li>
              <li><i class="pi pi-check"></i> Простые напоминания</li>
              <li><i class="pi pi-times"></i> Нет управления задачами</li>
              <li><i class="pi pi-times"></i> Нет AI-анализа</li>
              <li><i class="pi pi-times"></i> Нет аналитики</li>
            </ul>
            <button class="btn-secondary w-full">Остаться на Keep</button>
          </div>
        </div>
      </div>
    </section>

    <section class="use-cases">
      <div class="container">
        <div class="section-header"><h2>Когда что выбрать?</h2><p>Разные инструменты для разных задач</p></div>
        <div class="use-cases-grid">
          <div class="use-case-card fade-in-up">
            <div class="use-case-icon keep-icon"><i class="pi pi-file"></i></div>
            <h3>Google Keep подходит для:</h3>
            <ul><li>Быстрые заметки на ходу</li><li>Списки покупок</li><li>Сохранение идей</li><li>Простые чек-листы</li><li>Студенты и начинающие</li></ul>
          </div>
          <div class="use-case-card fade-in-up">
            <div class="use-case-icon taskflow-icon"><i class="pi pi-chart-line"></i></div>
            <h3>TaskFlow подходит для:</h3>
            <ul><li>Управление проектами</li><li>Планирование дня/недели</li><li>Работа с документами</li><li>Анализ продуктивности</li><li>Профессионалы и команды</li></ul>
          </div>
        </div>
      </div>
    </section>

    <section class="user-reviews">
      <div class="container">
        <div class="section-header"><h2>Что говорят пользователи</h2><p>Реальные отзывы тех, кто перерос Google Keep</p></div>
        <div class="reviews-grid">
          <div class="review-card fade-in-up" v-for="review in userReviews" :key="review.name">
            <div class="rating"><i class="pi pi-star-fill" v-for="n in 5" :key="n"></i></div>
            <p class="review-text">{{ review.text }}</p>
            <div class="review-author"><div class="avatar-placeholder">{{ review.name[0] }}</div><div><h4>{{ review.name }}</h4><span>{{ review.role }}</span></div></div>
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
  </LandingLayout>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import LandingLayout from '@/components/landing/LandingLayout.vue'
import { useScrollAnimations } from '@/composables/useScrollAnimations'
import { useSeoMeta, seoConfigs } from '@/composables/useSeoMeta'

const router = useRouter()

useScrollAnimations()
useSeoMeta(seoConfigs.compareGoogleKeep)

const comparisonFeatures = [
  { name: 'Цена', description: 'Стоимость использования', taskflow: '290₽/мес', keep: 'Бесплатно', keepWins: true },
  { name: 'Назначение', description: 'Основная функция', taskflow: 'Задачи + планирование', keep: 'Только заметки', taskflowWins: true },
  { name: 'AI-возможности', description: 'Умный анализ', taskflow: '✅ Умный анализ', keep: 'Только OCR', taskflowWins: true },
  { name: 'Голосовые команды', description: 'Создание задач голосом', taskflow: '✅ Полноценное', keep: 'Только диктовка', taskflowWins: true },
  { name: 'Напоминания', description: 'Уведомления о задачах', taskflow: '✅ Умные с контекстом', keep: 'Простые', taskflowWins: true },
  { name: 'Обработка файлов', description: 'PDF, Word документы', taskflow: '✅ PDF, Word', keep: 'Только изображения', taskflowWins: true },
  { name: 'Подзадачи', description: 'Иерархия задач', taskflow: '✅ Неограниченно', keep: false },
  { name: 'Аналитика', description: 'Статистика продуктивности', taskflow: '✅ Продвинутая', keep: false },
  { name: 'Telegram интеграция', description: 'Управление через бота', taskflow: true, keep: false },
  { name: 'Google интеграция', description: 'Workspace, Drive', taskflow: '⚠️ Базовая', keep: '✅ Полная', keepWins: true }
]

const userReviews = [
  { name: 'Алексей Морозов', role: 'Менеджер', text: 'Google Keep отлично подходил для списка покупок, но для работы нужно что-то серьезнее. TaskFlow — это то, что нужно.' },
  { name: 'Мария Кузнецова', role: 'Фрилансер', text: 'Перешла с Keep, когда поняла, что трачу больше времени на организацию заметок, чем на работу. TaskFlow всё автоматизирует.' },
  { name: 'Павел Николаев', role: 'Предприниматель', text: 'Keep — для заметок, TaskFlow — для управления бизнесом. Это разные весовые категории.' }
]

const startTrial = () => router.push('/register')
</script>

<style scoped lang="scss">
@import '@/styles/landing.scss';

.fade-in-up { opacity: 0; transform: translateY(30px); transition: all 0.6s ease-out; &.visible { opacity: 1; transform: translateY(0); } }

.compare-hero { padding: 8rem 0 4rem; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); .hero-content { text-align: center; } .comparison-header { display: flex; align-items: center; justify-content: center; gap: 3rem; margin-bottom: 3rem; @media (max-width: 768px) { flex-direction: column; gap: 2rem; } .product-card { text-align: center; .product-logo { width: 80px; height: 80px; margin: 0 auto 1rem; } h2 { font-size: 1.5rem; margin-bottom: 0.5rem; } &.our-product .badge.winner { background: var(--gradient-primary); color: white; padding: 0.25rem 1rem; border-radius: 20px; font-size: 0.875rem; } } .vs-divider { width: 60px; height: 60px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); font-weight: bold; color: var(--gray-700); } } .comparison-title { font-size: 3rem; margin-bottom: 1rem; color: var(--gray-900); @media (max-width: 768px) { font-size: 2rem; } } .comparison-subtitle { font-size: 1.25rem; color: var(--gray-600); } }

.key-difference { padding: 3rem 0; background: white; .difference-card { background: #FFF3CD; border: 2px solid #FFBB00; border-radius: 16px; padding: 2rem; display: flex; align-items: center; gap: 1.5rem; @media (max-width: 768px) { flex-direction: column; text-align: center; } .difference-icon { width: 60px; height: 60px; background: #FFBB00; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; i { font-size: 1.5rem; color: white; } } .difference-content { h3 { margin: 0 0 0.5rem; color: var(--gray-900); } p { margin: 0; color: var(--gray-700); line-height: 1.6; } } } }

.quick-overview { padding: 5rem 0; background: white; .overview-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; @media (max-width: 768px) { grid-template-columns: 1fr; } } .overview-card { padding: 2rem; border-radius: 16px; &.taskflow { background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%); border: 2px solid var(--primary); } &.keep { background: #f8f8f8; border: 2px solid #e0e0e0; } h3 { margin-bottom: 1.5rem; color: var(--gray-900); } ul { list-style: none; padding: 0; li { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; color: var(--gray-700); i { color: var(--success); } } } } }

.comparison-table-section { padding: 5rem 0; background: var(--gray-50); .comparison-table { overflow-x: auto; table { width: 100%; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); thead { background: var(--gray-900); color: white; th { padding: 1.5rem 1rem; text-align: left; &.taskflow-col { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); } .product-header { display: flex; align-items: center; gap: 0.5rem; .badge { background: rgba(255, 255, 255, 0.2); padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem; } } } } tbody .feature-row { transition: all 0.3s ease; &:hover { background: var(--gray-50); } td { padding: 1.25rem 1rem; border-bottom: 1px solid var(--gray-200); } .feature-name { strong { display: block; color: var(--gray-900); margin-bottom: 0.25rem; } .feature-description { font-size: 0.875rem; color: var(--gray-500); } } .feature-value { .check-icon { color: var(--success); font-size: 1.5rem; } .cross-icon { color: var(--danger); font-size: 1.5rem; } .highlight { color: var(--success); font-weight: 600; } } } } } }

.pricing-comparison { padding: 5rem 0; background: white; .pricing-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 3rem; @media (max-width: 768px) { grid-template-columns: 1fr; } } .pricing-card { padding: 2rem; border-radius: 16px; text-align: center; &.taskflow { background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%); border: 2px solid var(--primary); } &.keep { background: #f8f8f8; border: 2px solid #e0e0e0; } .product-label { font-size: 1.25rem; font-weight: bold; margin-bottom: 1rem; color: var(--gray-900); } .price { margin-bottom: 2rem; .amount { font-size: 3rem; font-weight: 800; color: var(--gray-900); } .period { color: var(--gray-500); } } .price-features { list-style: none; padding: 0; margin-bottom: 2rem; li { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; text-align: left; i { &.pi-check { color: var(--success); } &.pi-times { color: var(--danger); } } } } } }

.use-cases { padding: 5rem 0; background: var(--gray-50); .use-cases-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; @media (max-width: 768px) { grid-template-columns: 1fr; } } .use-case-card { background: white; padding: 2rem; border-radius: 16px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); .use-case-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem; &.keep-icon { background: #FFF3CD; i { color: #FFBB00; font-size: 1.5rem; } } &.taskflow-icon { background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%); i { color: var(--primary); font-size: 1.5rem; } } } h3 { margin-bottom: 1rem; color: var(--gray-900); } ul { list-style: none; padding: 0; li { padding: 0.5rem 0; color: var(--gray-600); position: relative; padding-left: 1.5rem; &::before { content: '•'; position: absolute; left: 0; color: var(--primary); } } } } }

.user-reviews { padding: 5rem 0; background: white; .reviews-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-top: 3rem; @media (max-width: 768px) { grid-template-columns: 1fr; } } .review-card { background: var(--gray-50); padding: 2rem; border-radius: 16px; .rating { color: #fbbf24; margin-bottom: 1rem; } .review-text { color: var(--gray-700); line-height: 1.6; margin-bottom: 1.5rem; } .review-author { display: flex; align-items: center; gap: 1rem; .avatar-placeholder { width: 48px; height: 48px; border-radius: 50%; background: var(--gradient-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.25rem; } h4 { margin: 0; color: var(--gray-900); } span { font-size: 0.875rem; color: var(--gray-500); } } } }

.final-cta { padding: 5rem 0; background: var(--gray-50); .cta-card { padding: 4rem; border-radius: 30px; text-align: center; color: white; h2 { font-size: 2.5rem; margin-bottom: 1rem; } p { font-size: 1.25rem; margin-bottom: 2rem; opacity: 0.9; } .cta-features { display: flex; justify-content: center; gap: 3rem; margin-bottom: 3rem; @media (max-width: 768px) { flex-direction: column; gap: 1rem; } .cta-feature { display: flex; align-items: center; gap: 0.5rem; i { font-size: 1.5rem; } } } .cta-actions { display: flex; gap: 1rem; justify-content: center; } } }

.btn-large { padding: 1rem 2rem; font-size: 1.1rem; }
.btn-white { background: white; color: var(--primary); padding: 12px 32px; border-radius: 50px; font-weight: 600; transition: all 0.3s ease; border: none; cursor: pointer; &:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); } }
.w-full { width: 100%; }
.section-header { text-align: center; margin-bottom: 3rem; h2 { font-size: 2.5rem; margin-bottom: 1rem; color: var(--gray-900); } p { font-size: 1.25rem; color: var(--gray-600); } }
.container { max-width: 1280px; margin: 0 auto; padding: 0 1rem; }
</style>
