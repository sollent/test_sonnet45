<template>
  <LandingLayout>
    <section class="alternatives-hero">
      <div class="container">
        <div class="hero-content">
          <h1 class="hero-title">Лучшие альтернативы <span class="gradient-text-light">Todoist</span> в 2024</h1>
          <p class="hero-subtitle">Почему пользователи уходят с Todoist и какие приложения выбирают</p>
          <div class="hero-rating">
            <div class="rating-stars"><i class="pi pi-star-fill" v-for="n in 5" :key="n"></i></div>
            <span>4.9/5 от бывших пользователей Todoist</span>
          </div>
        </div>
      </div>
    </section>

    <section class="why-leave">
      <div class="container">
        <div class="section-header"><h2>Почему уходят с Todoist?</h2><p>Основные причины смены приложения</p></div>
        <div class="reasons-grid">
          <div class="reason-card fade-in-up" v-for="reason in leaveReasons" :key="reason.title">
            <div class="reason-icon"><i :class="reason.icon"></i></div>
            <h3>{{ reason.title }}</h3>
            <p>{{ reason.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="top-alternative">
      <div class="container">
        <div class="alternative-card fade-in-up">
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
              <h2>TaskFlow</h2>
              <p>Лучшая альтернатива Todoist с AI и голосом</p>
            </div>
            <div class="product-price">
              <span class="price">290₽</span>
              <span class="period">/месяц</span>
              <span class="comparison">в 3 раза дешевле Todoist</span>
            </div>
          </div>
          <div class="alternative-features">
            <div class="feature"><i class="pi pi-check"></i><span>Голосовое управление на русском</span></div>
            <div class="feature"><i class="pi pi-check"></i><span>AI-помощник для задач</span></div>
            <div class="feature"><i class="pi pi-check"></i><span>Telegram интеграция</span></div>
            <div class="feature"><i class="pi pi-check"></i><span>Обработка файлов</span></div>
            <div class="feature"><i class="pi pi-check"></i><span>Продвинутая аналитика</span></div>
            <div class="feature"><i class="pi pi-check"></i><span>14 дней бесплатно</span></div>
          </div>
          <button class="btn-primary btn-large" @click="startTrial">Попробовать TaskFlow бесплатно</button>
        </div>
      </div>
    </section>

    <section class="comparison-table-section">
      <div class="container">
        <div class="section-header"><h2>Сравнение альтернатив Todoist</h2><p>Выберите лучшее решение для ваших потребностей</p></div>
        <div class="comparison-table fade-in-up">
          <table>
            <thead>
              <tr>
                <th>Функция</th>
                <th class="highlight-col">TaskFlow</th>
                <th>TickTick</th>
                <th>Things 3</th>
                <th>Asana</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="feature in alternativesComparison" :key="feature.name">
                <td><strong>{{ feature.name }}</strong></td>
                <td class="highlight-col">{{ feature.taskflow }}</td>
                <td>{{ feature.ticktick }}</td>
                <td>{{ feature.things }}</td>
                <td>{{ feature.asana }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="user-reviews">
      <div class="container">
        <div class="section-header"><h2>Отзывы бывших пользователей Todoist</h2><p>Рейтинг: 4.9/5 на основе 500+ отзывов</p></div>
        <div class="reviews-grid">
          <div class="review-card fade-in-up" v-for="review in userReviews" :key="review.name">
            <div class="rating"><i class="pi pi-star-fill" v-for="n in 5" :key="n"></i></div>
            <p class="review-text">{{ review.text }}</p>
            <div class="review-author"><div class="avatar-placeholder">{{ review.name[0] }}</div><div><h4>{{ review.name }}</h4><span>Перешел с {{ review.from }}</span></div></div>
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

const router = useRouter()

useScrollAnimations()

const leaveReasons = [
  { icon: 'pi pi-dollar', title: 'Высокая цена', description: 'Todoist Pro стоит $4-10/месяц — дороже многих альтернатив с большей функциональностью' },
  { icon: 'pi pi-cog', title: 'Сложность', description: 'Слишком много функций, которые не нужны большинству пользователей' },
  { icon: 'pi pi-times', title: 'Нет AI', description: 'Отсутствие умного помощника для автоматизации рутинных задач' },
  { icon: 'pi pi-volume-off', title: 'Нет голоса', description: 'Невозможно создавать задачи голосом на русском языке' }
]

const alternativesComparison = [
  { name: 'Цена/мес', taskflow: '290₽', ticktick: '$2.79', things: '$50 разово', asana: '$10.99' },
  { name: 'AI-функции', taskflow: '✅', ticktick: '⚠️', things: '❌', asana: '✅' },
  { name: 'Голос', taskflow: '✅', ticktick: '⚠️', things: 'Siri', asana: '❌' },
  { name: 'Telegram', taskflow: '✅', ticktick: '❌', things: '❌', asana: '❌' },
  { name: 'Файлы', taskflow: '✅', ticktick: '⚠️', things: '❌', asana: '✅' },
  { name: 'Локализация RU', taskflow: '✅', ticktick: '✅', things: '⚠️', asana: '✅' }
]

const userReviews = [
  { name: 'Михаил Королев', from: 'Todoist Pro', text: 'Использовал Todoist 3 года, но голосовое управление в TaskFlow — это революция! Теперь создаю задачи в 5 раз быстрее.' },
  { name: 'Елена Васильева', from: 'Todoist Business', text: 'TaskFlow дешевле и функциональнее. Особенно радует AI-помощник, который понимает даже сложные команды.' },
  { name: 'Андрей Новиков', from: 'Todoist Pro', text: 'Перевел всю команду на TaskFlow. Интеграция с Telegram решила все проблемы с коммуникацией.' }
]

const startTrial = () => router.push('/register')
</script>

<style scoped lang="scss">
@import '@/styles/landing.scss';

.fade-in-up { opacity: 0; transform: translateY(30px); transition: all 0.6s ease-out; &.visible { opacity: 1; transform: translateY(0); } }

.alternatives-hero { padding: 8rem 0 4rem; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); text-align: center; .hero-title { font-size: 3rem; margin-bottom: 1rem; color: var(--gray-900); @media (max-width: 768px) { font-size: 2rem; } } .hero-subtitle { font-size: 1.25rem; color: var(--gray-600); margin-bottom: 2rem; } .hero-rating { display: inline-flex; align-items: center; gap: 1rem; background: white; padding: 1rem 2rem; border-radius: 50px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); .rating-stars { color: #fbbf24; } } }

.why-leave { padding: 5rem 0; background: white; .reasons-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem; @media (max-width: 768px) { grid-template-columns: 1fr; } } .reason-card { text-align: center; padding: 2rem; border-radius: 16px; background: var(--gray-50); .reason-icon { width: 60px; height: 60px; background: var(--gradient-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; i { font-size: 1.5rem; color: white; } } h3 { margin-bottom: 0.5rem; color: var(--gray-900); } p { color: var(--gray-600); font-size: 0.9rem; } } }

.top-alternative { padding: 5rem 0; background: var(--gray-50); .alternative-card { background: white; border-radius: 24px; padding: 3rem; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08); position: relative; .badge-top { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: var(--gradient-primary); color: white; padding: 0.5rem 1.5rem; border-radius: 20px; font-weight: 600; } .alternative-header { display: flex; align-items: center; gap: 2rem; margin-bottom: 2rem; @media (max-width: 768px) { flex-direction: column; text-align: center; } .product-info { flex: 1; h2 { margin: 0 0 0.5rem; color: var(--gray-900); } p { margin: 0; color: var(--gray-600); } } .product-price { text-align: right; @media (max-width: 768px) { text-align: center; } .price { font-size: 2.5rem; font-weight: 800; color: var(--gray-900); } .period { color: var(--gray-500); } .comparison { display: block; color: var(--success); font-weight: 600; } } } .alternative-features { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; @media (max-width: 768px) { grid-template-columns: 1fr 1fr; } .feature { display: flex; align-items: center; gap: 0.5rem; i { color: var(--success); } } } button { width: 100%; } } }

.comparison-table-section { padding: 5rem 0; background: white; .comparison-table { overflow-x: auto; table { width: 100%; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--gray-200); } thead { background: var(--gray-900); color: white; .highlight-col { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); } } tbody { .highlight-col { background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%); font-weight: 600; } } } } }

.user-reviews { padding: 5rem 0; background: var(--gray-50); .reviews-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-top: 3rem; @media (max-width: 768px) { grid-template-columns: 1fr; } } .review-card { background: white; padding: 2rem; border-radius: 16px; .rating { color: #fbbf24; margin-bottom: 1rem; } .review-text { color: var(--gray-700); line-height: 1.6; margin-bottom: 1.5rem; } .review-author { display: flex; align-items: center; gap: 1rem; .avatar-placeholder { width: 48px; height: 48px; border-radius: 50%; background: var(--gradient-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.25rem; } h4 { margin: 0; color: var(--gray-900); } span { font-size: 0.875rem; color: var(--gray-500); } } } }

.final-cta { padding: 5rem 0; background: white; .cta-card { padding: 4rem; border-radius: 30px; text-align: center; color: white; h2 { font-size: 2.5rem; margin-bottom: 1rem; } p { font-size: 1.25rem; margin-bottom: 2rem; opacity: 0.9; } .cta-features { display: flex; justify-content: center; gap: 3rem; margin-bottom: 3rem; @media (max-width: 768px) { flex-direction: column; gap: 1rem; } .cta-feature { display: flex; align-items: center; gap: 0.5rem; i { font-size: 1.5rem; } } } .cta-actions { display: flex; gap: 1rem; justify-content: center; } } }

.btn-large { padding: 1rem 2rem; font-size: 1.1rem; }
.btn-white { background: white; color: var(--primary); padding: 12px 32px; border-radius: 50px; font-weight: 600; transition: all 0.3s ease; border: none; cursor: pointer; &:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); } }
.section-header { text-align: center; margin-bottom: 3rem; h2 { font-size: 2.5rem; margin-bottom: 1rem; color: var(--gray-900); } p { font-size: 1.25rem; color: var(--gray-600); } }
.container { max-width: 1280px; margin: 0 auto; padding: 0 1rem; }
</style>
