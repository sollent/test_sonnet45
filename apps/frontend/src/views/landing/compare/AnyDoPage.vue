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
                  <rect width="60" height="60" rx="15" fill="#FF6B6B"/>
                  <circle cx="30" cy="30" r="12" fill="white"/>
                  <path d="M26 30l3 3 5-5" stroke="#FF6B6B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <h2>Any.do</h2>
            </div>
          </div>
          <h1 class="comparison-title">TaskFlow vs Any.do: <span class="gradient-text">Детальное сравнение</span></h1>
          <p class="comparison-subtitle">Красота против функциональности — узнайте, что выбрать для ваших задач</p>
        </div>
      </div>
    </section>

    <section class="quick-overview">
      <div class="container">
        <div class="overview-cards">
          <div class="overview-card taskflow fade-in-up">
            <h3>TaskFlow выигрывает в:</h3>
            <ul>
              <li><i class="pi pi-check"></i> Полное голосовое управление</li>
              <li><i class="pi pi-check"></i> AI-помощник с естественным языком</li>
              <li><i class="pi pi-check"></i> Telegram интеграция</li>
              <li><i class="pi pi-check"></i> Обработка файлов (PDF, DOC)</li>
              <li><i class="pi pi-check"></i> Более доступная цена</li>
            </ul>
          </div>
          <div class="overview-card anydo fade-in-up">
            <h3>Any.do лучше в:</h3>
            <ul>
              <li><i class="pi pi-check"></i> Очень красивый дизайн</li>
              <li><i class="pi pi-check"></i> Продвинутый календарь</li>
              <li><i class="pi pi-check"></i> Интеграция с WhatsApp</li>
              <li><i class="pi pi-check"></i> Планировщик дня</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <section class="comparison-table-section">
      <div class="container">
        <div class="section-header">
          <h2>Полное сравнение функций</h2>
          <p>Функциональность vs красота дизайна</p>
        </div>
        <div class="comparison-table fade-in-up">
          <table>
            <thead>
              <tr>
                <th>Критерий</th>
                <th class="taskflow-col"><div class="product-header"><span>TaskFlow</span><span class="badge">Наш выбор</span></div></th>
                <th class="anydo-col">Any.do</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="feature in comparisonFeatures" :key="feature.name" class="feature-row">
                <td class="feature-name"><strong>{{ feature.name }}</strong><span class="feature-description">{{ feature.description }}</span></td>
                <td class="taskflow-col"><div class="feature-value"><i v-if="feature.taskflow === true" class="pi pi-check-circle check-icon"></i><i v-else-if="feature.taskflow === false" class="pi pi-times-circle cross-icon"></i><span v-else :class="{ 'highlight': feature.taskflowWins }">{{ feature.taskflow }}</span></div></td>
                <td class="anydo-col"><div class="feature-value"><i v-if="feature.anydo === true" class="pi pi-check-circle check-icon"></i><i v-else-if="feature.anydo === false" class="pi pi-times-circle cross-icon"></i><span v-else :class="{ 'highlight': feature.anydoWins }">{{ feature.anydo }}</span></div></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="pricing-comparison">
      <div class="container">
        <div class="section-header"><h2>Сравнение цен</h2><p>TaskFlow дешевле с большим количеством функций</p></div>
        <div class="pricing-cards">
          <div class="pricing-card taskflow fade-in-up">
            <div class="product-label">TaskFlow Pro</div>
            <div class="price"><span class="amount">290₽</span><span class="period">/месяц</span></div>
            <ul class="price-features">
              <li><i class="pi pi-check"></i> Голосовое управление</li>
              <li><i class="pi pi-check"></i> AI-помощник</li>
              <li><i class="pi pi-check"></i> Telegram интеграция</li>
              <li><i class="pi pi-check"></i> Обработка файлов</li>
              <li><i class="pi pi-check"></i> Продвинутая аналитика</li>
            </ul>
            <button class="btn-primary w-full" @click="startTrial">Попробовать бесплатно</button>
          </div>
          <div class="pricing-card anydo fade-in-up">
            <div class="product-label">Any.do Premium</div>
            <div class="price"><span class="amount">$2.99-5.99</span><span class="period">/месяц</span></div>
            <ul class="price-features">
              <li><i class="pi pi-check"></i> Красивый дизайн</li>
              <li><i class="pi pi-check"></i> Продвинутый календарь</li>
              <li><i class="pi pi-check"></i> WhatsApp интеграция</li>
              <li><i class="pi pi-times"></i> Нет голосового управления</li>
              <li><i class="pi pi-times"></i> Нет Telegram</li>
            </ul>
            <button class="btn-secondary w-full">Сайт Any.do</button>
          </div>
        </div>
      </div>
    </section>

    <section class="verdict-section">
      <div class="container">
        <div class="verdict-card fade-in-up">
          <div class="verdict-icon"><i class="pi pi-star-fill"></i></div>
          <h2>Наш вердикт</h2>
          <div class="verdict-content">
            <div class="verdict-item">
              <h3>Выберите TaskFlow, если:</h3>
              <ul><li>Вам важна функциональность</li><li>Хотите голосовое управление</li><li>Используете Telegram</li><li>Нужна AI-обработка файлов</li></ul>
            </div>
            <div class="verdict-item">
              <h3>Выберите Any.do, если:</h3>
              <ul><li>Дизайн — приоритет №1</li><li>Важен продвинутый календарь</li><li>Используете WhatsApp</li><li>Нужен планировщик дня</li></ul>
            </div>
          </div>
          <p class="verdict-summary"><strong>Any.do — для любителей красивого интерфейса, TaskFlow — для максимальной функциональности</strong></p>
        </div>
      </div>
    </section>

    <section class="user-reviews">
      <div class="container">
        <div class="section-header"><h2>Что говорят пользователи</h2><p>Реальные отзывы тех, кто перешел с Any.do</p></div>
        <div class="reviews-grid">
          <div class="review-card fade-in-up" v-for="review in userReviews" :key="review.name">
            <div class="rating"><i class="pi pi-star-fill" v-for="n in 5" :key="n"></i></div>
            <p class="review-text">{{ review.text }}</p>
            <div class="review-author"><div class="avatar-placeholder">{{ review.name[0] }}</div><div><h4>{{ review.name }}</h4><span>{{ review.role }}</span></div></div>
          </div>
        </div>
      </div>
    </section>

    <section class="final-cta">
      <div class="container">
        <div class="cta-card animated-gradient">
          <h2>Попробуйте TaskFlow бесплатно</h2>
          <p>Функциональность важнее визуала — убедитесь сами</p>
          <div class="cta-features">
            <div class="cta-feature"><i class="pi pi-calendar"></i><span>14 дней бесплатно</span></div>
            <div class="cta-feature"><i class="pi pi-credit-card"></i><span>Без карты</span></div>
            <div class="cta-feature"><i class="pi pi-bolt"></i><span>Все функции</span></div>
          </div>
          <div class="cta-actions"><button class="btn-white btn-large" @click="startTrial">Начать бесплатный период</button></div>
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

onMounted(() => {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => { if (entry.isIntersecting) entry.target.classList.add('visible') })
  }, { threshold: 0.1 })
  document.querySelectorAll('.fade-in-up').forEach((el) => observer.observe(el))
})

const comparisonFeatures = [
  { name: 'Цена', description: 'Стоимость подписки', taskflow: '290₽/мес', anydo: '$2.99-5.99/мес', taskflowWins: true },
  { name: 'Голосовое управление', description: 'Создание задач голосом', taskflow: true, anydo: false },
  { name: 'AI-возможности', description: 'Умный парсинг команд', taskflow: '✅ Полноценный AI', anydo: '⚠️ Базовый', taskflowWins: true },
  { name: 'Telegram интеграция', description: 'Управление через бота', taskflow: true, anydo: false },
  { name: 'Обработка файлов', description: 'PDF, DOC, изображения', taskflow: '✅ PDF, DOC, изображения', anydo: '⚠️ Только вложения', taskflowWins: true },
  { name: 'Дизайн', description: 'Визуальное оформление', taskflow: '✅ Современный', anydo: '✅ Очень красивый', anydoWins: true },
  { name: 'Календарь', description: 'Интеграция с календарем', taskflow: '⚠️ Базовая', anydo: '✅ Продвинутая', anydoWins: true },
  { name: 'WhatsApp интеграция', description: 'Управление через WhatsApp', taskflow: false, anydo: true },
  { name: 'Планировщик дня', description: 'Утренний обзор задач', taskflow: false, anydo: true },
  { name: 'Аналитика', description: 'Статистика продуктивности', taskflow: '✅ Продвинутая', anydo: '⚠️ Базовая', taskflowWins: true }
]

const userReviews = [
  { name: 'Ольга Смирнова', role: 'Дизайнер', text: 'Any.do очень красивый, но TaskFlow делает больше. Голосовое управление и Telegram — это то, чего мне не хватало.' },
  { name: 'Максим Козлов', role: 'Менеджер проектов', text: 'Перешел с Any.do из-за отсутствия нормальной обработки файлов. В TaskFlow загружаю PDF и AI сам создает задачи!' },
  { name: 'Екатерина Волкова', role: 'Предприниматель', text: 'Красивый интерфейс — это приятно, но функциональность важнее. TaskFlow экономит мне час в день.' }
]

const startTrial = () => router.push('/register')
</script>

<style scoped lang="scss">
@import '@/styles/landing.scss';

.fade-in-up { opacity: 0; transform: translateY(30px); transition: all 0.6s ease-out; &.visible { opacity: 1; transform: translateY(0); } }

.compare-hero { padding: 8rem 0 4rem; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); .hero-content { text-align: center; } .comparison-header { display: flex; align-items: center; justify-content: center; gap: 3rem; margin-bottom: 3rem; @media (max-width: 768px) { flex-direction: column; gap: 2rem; } .product-card { text-align: center; .product-logo { width: 80px; height: 80px; margin: 0 auto 1rem; } h2 { font-size: 1.5rem; margin-bottom: 0.5rem; } &.our-product .badge.winner { background: var(--gradient-primary); color: white; padding: 0.25rem 1rem; border-radius: 20px; font-size: 0.875rem; } } .vs-divider { width: 60px; height: 60px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); font-weight: bold; color: var(--gray-700); } } .comparison-title { font-size: 3rem; margin-bottom: 1rem; color: var(--gray-900); @media (max-width: 768px) { font-size: 2rem; } } .comparison-subtitle { font-size: 1.25rem; color: var(--gray-600); } }

.quick-overview { padding: 5rem 0; background: white; .overview-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; @media (max-width: 768px) { grid-template-columns: 1fr; } } .overview-card { padding: 2rem; border-radius: 16px; &.taskflow { background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%); border: 2px solid var(--primary); } &.anydo { background: #f8f8f8; border: 2px solid #e0e0e0; } h3 { margin-bottom: 1.5rem; color: var(--gray-900); } ul { list-style: none; padding: 0; li { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; color: var(--gray-700); i { color: var(--success); } } } } }

.comparison-table-section { padding: 5rem 0; background: var(--gray-50); .comparison-table { overflow-x: auto; table { width: 100%; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); thead { background: var(--gray-900); color: white; th { padding: 1.5rem 1rem; text-align: left; &.taskflow-col { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); } .product-header { display: flex; align-items: center; gap: 0.5rem; .badge { background: rgba(255, 255, 255, 0.2); padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem; } } } } tbody .feature-row { transition: all 0.3s ease; &:hover { background: var(--gray-50); } td { padding: 1.25rem 1rem; border-bottom: 1px solid var(--gray-200); } .feature-name { strong { display: block; color: var(--gray-900); margin-bottom: 0.25rem; } .feature-description { font-size: 0.875rem; color: var(--gray-500); } } .feature-value { .check-icon { color: var(--success); font-size: 1.5rem; } .cross-icon { color: var(--danger); font-size: 1.5rem; } .highlight { color: var(--success); font-weight: 600; } } } } } }

.pricing-comparison { padding: 5rem 0; background: white; .pricing-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 3rem; @media (max-width: 768px) { grid-template-columns: 1fr; } } .pricing-card { padding: 2rem; border-radius: 16px; text-align: center; &.taskflow { background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%); border: 2px solid var(--primary); } &.anydo { background: #f8f8f8; border: 2px solid #e0e0e0; } .product-label { font-size: 1.25rem; font-weight: bold; margin-bottom: 1rem; color: var(--gray-900); } .price { margin-bottom: 2rem; .amount { font-size: 3rem; font-weight: 800; color: var(--gray-900); } .period { color: var(--gray-500); } } .price-features { list-style: none; padding: 0; margin-bottom: 2rem; li { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; text-align: left; i { &.pi-check { color: var(--success); } &.pi-times { color: var(--danger); } } } } } }

.verdict-section { padding: 5rem 0; background: var(--gray-50); .verdict-card { background: white; border-radius: 24px; padding: 3rem; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08); text-align: center; .verdict-icon { width: 80px; height: 80px; background: var(--gradient-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem; i { font-size: 2.5rem; color: white; } } h2 { font-size: 2rem; margin-bottom: 2rem; color: var(--gray-900); } .verdict-content { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; text-align: left; margin-bottom: 2rem; @media (max-width: 768px) { grid-template-columns: 1fr; } .verdict-item { h3 { font-size: 1.25rem; margin-bottom: 1rem; color: var(--gray-900); } ul { list-style: none; padding: 0; li { padding: 0.5rem 0; color: var(--gray-600); position: relative; padding-left: 1.5rem; &::before { content: '•'; position: absolute; left: 0; color: var(--primary); } } } } } .verdict-summary { font-size: 1.125rem; color: var(--gray-700); padding-top: 2rem; border-top: 1px solid var(--gray-200); } } }

.user-reviews { padding: 5rem 0; background: white; .reviews-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-top: 3rem; @media (max-width: 768px) { grid-template-columns: 1fr; } } .review-card { background: var(--gray-50); padding: 2rem; border-radius: 16px; .rating { color: #fbbf24; margin-bottom: 1rem; } .review-text { color: var(--gray-700); line-height: 1.6; margin-bottom: 1.5rem; } .review-author { display: flex; align-items: center; gap: 1rem; .avatar-placeholder { width: 48px; height: 48px; border-radius: 50%; background: var(--gradient-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.25rem; } h4 { margin: 0; color: var(--gray-900); } span { font-size: 0.875rem; color: var(--gray-500); } } } }

.final-cta { padding: 5rem 0; background: var(--gray-50); .cta-card { padding: 4rem; border-radius: 30px; text-align: center; color: white; h2 { font-size: 2.5rem; margin-bottom: 1rem; } p { font-size: 1.25rem; margin-bottom: 2rem; opacity: 0.9; } .cta-features { display: flex; justify-content: center; gap: 3rem; margin-bottom: 3rem; @media (max-width: 768px) { flex-direction: column; gap: 1rem; } .cta-feature { display: flex; align-items: center; gap: 0.5rem; i { font-size: 1.5rem; } } } .cta-actions { display: flex; gap: 1rem; justify-content: center; } } }

.btn-large { padding: 1rem 2rem; font-size: 1.1rem; }
.btn-white { background: white; color: var(--primary); padding: 12px 32px; border-radius: 50px; font-weight: 600; transition: all 0.3s ease; border: none; cursor: pointer; &:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); } }
.w-full { width: 100%; }
.section-header { text-align: center; margin-bottom: 3rem; h2 { font-size: 2.5rem; margin-bottom: 1rem; color: var(--gray-900); } p { font-size: 1.25rem; color: var(--gray-600); } }
.container { max-width: 1280px; margin: 0 auto; padding: 0 1rem; }
</style>
