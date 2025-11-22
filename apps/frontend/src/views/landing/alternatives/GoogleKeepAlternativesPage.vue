<template>
  <LandingLayout>
    <section class="alternatives-hero">
      <div class="container">
        <div class="hero-content">
          <h1 class="hero-title">Лучшие альтернативы <span class="gradient-text-light">Google Keep</span> в 2024</h1>
          <p class="hero-subtitle">Когда простых заметок становится недостаточно — время для апгрейда</p>
          <div class="hero-rating">
            <div class="rating-stars"><i class="pi pi-star-fill" v-for="n in 5" :key="n"></i></div>
            <span>4.8/5 от бывших пользователей Google Keep</span>
          </div>
        </div>
      </div>
    </section>

    <section class="why-leave">
      <div class="container">
        <div class="section-header"><h2>Почему Google Keep устарел?</h2><p>Ограничения, которые мешают продуктивности</p></div>
        <div class="reasons-grid">
          <div class="reason-card fade-in-up" v-for="reason in leaveReasons" :key="reason.title">
            <div class="reason-icon"><i :class="reason.icon"></i></div>
            <h3>{{ reason.title }}</h3>
            <p>{{ reason.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="alternatives-list">
      <div class="container">
        <div class="section-header"><h2>Топ альтернатив Google Keep</h2><p>От простых заметок к полноценному управлению задачами</p></div>
        
        <div class="alternative-card recommended fade-in-up">
          <div class="badge-top">Рекомендация #1</div>
          <div class="alternative-header">
            <div class="product-logo">
              <svg width="60" height="60" viewBox="0 0 60 60" fill="none">
                <rect width="60" height="60" rx="15" fill="url(#taskflow-gradient)"/>
                <path d="M18 22h24M18 30h15M18 38h21" stroke="white" stroke-width="3" stroke-linecap="round"/>
                <defs><linearGradient id="taskflow-gradient" x1="0" y1="0" x2="60" y2="60"><stop offset="0%" stop-color="#667eea"/><stop offset="100%" stop-color="#764ba2"/></linearGradient></defs>
              </svg>
            </div>
            <div class="product-info">
              <h3>TaskFlow</h3>
              <p>Управление задачами с AI и голосом</p>
            </div>
            <div class="product-price"><span class="price">290₽</span><span class="period">/мес</span></div>
          </div>
          <div class="alternative-features">
            <span class="feature"><i class="pi pi-check"></i> AI-помощник</span>
            <span class="feature"><i class="pi pi-check"></i> Голосовое управление</span>
            <span class="feature"><i class="pi pi-check"></i> Telegram</span>
            <span class="feature"><i class="pi pi-check"></i> PDF/OCR</span>
          </div>
          <button class="btn-primary" @click="startTrial">Попробовать бесплатно</button>
        </div>

        <div class="alternative-card fade-in-up" v-for="alt in otherAlternatives" :key="alt.name">
          <div class="alternative-header">
            <div class="product-logo" :style="{ background: alt.color }">
              <i :class="alt.icon" style="color: white; font-size: 1.5rem;"></i>
            </div>
            <div class="product-info">
              <h3>{{ alt.name }}</h3>
              <p>{{ alt.description }}</p>
            </div>
            <div class="product-price"><span class="price">{{ alt.price }}</span><span class="period">{{ alt.period }}</span></div>
          </div>
          <div class="alternative-features">
            <span class="feature" v-for="feat in alt.features" :key="feat"><i class="pi pi-check"></i> {{ feat }}</span>
          </div>
          <div class="alternative-cons">
            <span class="con" v-for="con in alt.cons" :key="con"><i class="pi pi-times"></i> {{ con }}</span>
          </div>
        </div>
      </div>
    </section>

    <section class="comparison-table-section">
      <div class="container">
        <div class="section-header"><h2>Быстрое сравнение</h2><p>Все альтернативы в одной таблице</p></div>
        <div class="comparison-table fade-in-up">
          <table>
            <thead>
              <tr>
                <th>Функция</th>
                <th class="highlight-col">TaskFlow</th>
                <th>Notion</th>
                <th>Obsidian</th>
                <th>Apple Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="feature in comparisonData" :key="feature.name">
                <td><strong>{{ feature.name }}</strong></td>
                <td class="highlight-col">{{ feature.taskflow }}</td>
                <td>{{ feature.notion }}</td>
                <td>{{ feature.obsidian }}</td>
                <td>{{ feature.apple }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="user-reviews">
      <div class="container">
        <div class="section-header"><h2>Отзывы бывших пользователей Keep</h2><p>Рейтинг: 4.8/5 на основе 300+ отзывов</p></div>
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

const leaveReasons = [
  { icon: 'pi pi-clock', title: 'Нет обновлений', description: 'Google Keep практически не развивается — последние серьезные обновления были годы назад' },
  { icon: 'pi pi-search', title: 'Слабый поиск', description: 'Невозможно нормально искать по содержимому заметок и меткам' },
  { icon: 'pi pi-sitemap', title: 'Нет структуры', description: 'Только метки и цвета — нет папок, проектов, иерархии задач' },
  { icon: 'pi pi-times', title: 'Нет AI', description: 'Никакой автоматизации и умных функций — все вручную' }
]

const otherAlternatives = [
  { name: 'Notion', description: 'Универсальное рабочее пространство', price: '$4-8', period: '/мес', color: '#000000', icon: 'pi pi-book', features: ['База знаний', 'Шаблоны', 'Командная работа'], cons: ['Сложный', 'Медленный'] },
  { name: 'Obsidian', description: 'Локальные заметки с графом связей', price: '$4-8', period: '/мес', color: '#7C3AED', icon: 'pi pi-share-alt', features: ['Граф связей', 'Markdown', 'Плагины'], cons: ['Только для продвинутых', 'Нет мобильной синхронизации бесплатно'] },
  { name: 'Apple Notes', description: 'Встроенные заметки Apple', price: 'Бесплатно', period: '', color: '#FF9500', icon: 'pi pi-apple', features: ['Интеграция с Apple', 'Простота', 'Quick Notes'], cons: ['Только Apple', 'Нет AI', 'Базовые функции'] }
]

const comparisonData = [
  { name: 'Цена/мес', taskflow: '290₽', notion: '$4-8', obsidian: '$4-8', apple: 'Бесплатно' },
  { name: 'AI-функции', taskflow: '✅', notion: '✅', obsidian: '⚠️', apple: '❌' },
  { name: 'Голос', taskflow: '✅', notion: '❌', obsidian: '❌', apple: '⚠️' },
  { name: 'Telegram', taskflow: '✅', notion: '⚠️', obsidian: '❌', apple: '❌' },
  { name: 'OCR/PDF', taskflow: '✅', notion: '✅', obsidian: '⚠️', apple: '⚠️' },
  { name: 'Простота', taskflow: '✅', notion: '❌', obsidian: '❌', apple: '✅' }
]

const userReviews = [
  { name: 'Виктор Степанов', role: 'Аналитик', text: 'Google Keep был хорош для списка покупок, но для работы нужно больше. TaskFlow — это Keep на стероидах с AI.' },
  { name: 'Ирина Федорова', role: 'Маркетолог', text: 'Перепробовала Notion — слишком сложно. TaskFlow дает все нужное без лишней головной боли.' },
  { name: 'Денис Крылов', role: 'Фрилансер', text: 'Голосовое управление через Telegram — то, чего не хватало в Keep. Теперь создаю задачи на ходу.' }
]

const startTrial = () => router.push('/register')
</script>

<style scoped lang="scss">
@import '@/styles/landing.scss';

.fade-in-up { opacity: 0; transform: translateY(30px); transition: all 0.6s ease-out; &.visible { opacity: 1; transform: translateY(0); } }

.alternatives-hero { padding: 8rem 0 4rem; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); text-align: center; .hero-title { font-size: 3rem; margin-bottom: 1rem; color: var(--gray-900); @media (max-width: 768px) { font-size: 2rem; } } .hero-subtitle { font-size: 1.25rem; color: var(--gray-600); margin-bottom: 2rem; } .hero-rating { display: inline-flex; align-items: center; gap: 1rem; background: white; padding: 1rem 2rem; border-radius: 50px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); .rating-stars { color: #fbbf24; } } }

.why-leave { padding: 5rem 0; background: white; .reasons-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem; @media (max-width: 768px) { grid-template-columns: 1fr; } } .reason-card { text-align: center; padding: 2rem; border-radius: 16px; background: var(--gray-50); .reason-icon { width: 60px; height: 60px; background: var(--gradient-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; i { font-size: 1.5rem; color: white; } } h3 { margin-bottom: 0.5rem; color: var(--gray-900); } p { color: var(--gray-600); font-size: 0.9rem; } } }

.alternatives-list { padding: 5rem 0; background: var(--gray-50); .alternative-card { background: white; border-radius: 16px; padding: 2rem; margin-bottom: 1.5rem; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); &.recommended { border: 2px solid var(--primary); position: relative; .badge-top { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: var(--gradient-primary); color: white; padding: 0.5rem 1.5rem; border-radius: 20px; font-weight: 600; } } .alternative-header { display: flex; align-items: center; gap: 1.5rem; margin-bottom: 1.5rem; @media (max-width: 768px) { flex-direction: column; text-align: center; } .product-logo { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; } .product-info { flex: 1; h3 { margin: 0 0 0.25rem; color: var(--gray-900); } p { margin: 0; color: var(--gray-600); font-size: 0.9rem; } } .product-price { text-align: right; @media (max-width: 768px) { text-align: center; } .price { font-size: 1.5rem; font-weight: 800; color: var(--gray-900); } .period { color: var(--gray-500); } } } .alternative-features { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; .feature { display: inline-flex; align-items: center; gap: 0.25rem; background: #E8F5E9; color: #2E7D32; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.875rem; i { font-size: 0.75rem; } } } .alternative-cons { display: flex; flex-wrap: wrap; gap: 0.75rem; .con { display: inline-flex; align-items: center; gap: 0.25rem; background: #FFEBEE; color: #C62828; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.875rem; i { font-size: 0.75rem; } } } } }

.comparison-table-section { padding: 5rem 0; background: white; .comparison-table { overflow-x: auto; table { width: 100%; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--gray-200); } thead { background: var(--gray-900); color: white; .highlight-col { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); } } tbody { .highlight-col { background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%); font-weight: 600; } } } } }

.user-reviews { padding: 5rem 0; background: var(--gray-50); .reviews-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-top: 3rem; @media (max-width: 768px) { grid-template-columns: 1fr; } } .review-card { background: white; padding: 2rem; border-radius: 16px; .rating { color: #fbbf24; margin-bottom: 1rem; } .review-text { color: var(--gray-700); line-height: 1.6; margin-bottom: 1.5rem; } .review-author { display: flex; align-items: center; gap: 1rem; .avatar-placeholder { width: 48px; height: 48px; border-radius: 50%; background: var(--gradient-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.25rem; } h4 { margin: 0; color: var(--gray-900); } span { font-size: 0.875rem; color: var(--gray-500); } } } }

.final-cta { padding: 5rem 0; background: white; .cta-card { padding: 4rem; border-radius: 30px; text-align: center; color: white; h2 { font-size: 2.5rem; margin-bottom: 1rem; } p { font-size: 1.25rem; margin-bottom: 2rem; opacity: 0.9; } .cta-features { display: flex; justify-content: center; gap: 3rem; margin-bottom: 3rem; @media (max-width: 768px) { flex-direction: column; gap: 1rem; } .cta-feature { display: flex; align-items: center; gap: 0.5rem; i { font-size: 1.5rem; } } } .cta-actions { display: flex; gap: 1rem; justify-content: center; } } }

.btn-large { padding: 1rem 2rem; font-size: 1.1rem; }
.btn-white { background: white; color: var(--primary); padding: 12px 32px; border-radius: 50px; font-weight: 600; transition: all 0.3s ease; border: none; cursor: pointer; &:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); } }
.section-header { text-align: center; margin-bottom: 3rem; h2 { font-size: 2.5rem; margin-bottom: 1rem; color: var(--gray-900); } p { font-size: 1.25rem; color: var(--gray-600); } }
.container { max-width: 1280px; margin: 0 auto; padding: 0 1rem; }
</style>
