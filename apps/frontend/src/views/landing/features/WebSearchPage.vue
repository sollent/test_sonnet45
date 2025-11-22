<template>
  <LandingLayout>
    <!-- Hero Section -->
    <section class="feature-hero search-hero">
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
              <i class="pi pi-globe"></i>
              Веб-поиск
            </div>

            <h1>Поиск актуальной информации <span class="gradient-text">в один клик</span></h1>
            <p class="lead">
              AI ищет информацию в интернете и создает структурированные заметки
              с источниками. Больше не нужно открывать десятки вкладок.
            </p>

            <div class="hero-actions">
              <button class="btn-primary btn-large" @click="trySearch">
                <i class="pi pi-search"></i>
                Попробовать поиск
              </button>
              <button class="btn-glass btn-large" @click="showDemo = true">
                <i class="pi pi-video"></i>
                Смотреть демо
              </button>
            </div>
          </div>

          <div class="hero-visual fade-in-right">
            <div class="search-animation">
              <div class="search-box">
                <i class="pi pi-search"></i>
                <span>лучшие CRM для малого бизнеса</span>
              </div>
              <div class="result-preview">
                <div class="result-item">HubSpot - бесплатно до 1000 контактов</div>
                <div class="result-item">Pipedrive - от $12/мес</div>
                <div class="result-item">Zoho CRM - от $14/мес</div>
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
          <h2>Проблемы ручного поиска</h2>
          <p>Почему обычный поиск неэффективен</p>
        </div>

        <div class="problems-grid">
          <div class="problem-card fade-in-up" v-for="problem in problems" :key="problem.title">
            <div class="problem-icon"><i :class="problem.icon"></i></div>
            <h3>{{ problem.title }}</h3>
            <p>{{ problem.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works-section">
      <div class="container">
        <div class="section-header">
          <h2>Как это работает</h2>
          <p>Три простых шага до готовой информации</p>
        </div>

        <div class="steps-grid">
          <div class="step-card" v-for="(step, index) in steps" :key="step.title">
            <div class="step-number">{{ index + 1 }}</div>
            <h3>{{ step.title }}</h3>
            <p>{{ step.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Use Cases Section -->
    <section class="use-cases-section">
      <div class="container">
        <div class="section-header">
          <h2>Примеры использования</h2>
          <p>Что можно искать с помощью TaskFlow</p>
        </div>

        <div class="cases-grid">
          <div class="case-card" v-for="useCase in useCases" :key="useCase.title">
            <div class="case-icon"><i :class="useCase.icon"></i></div>
            <h3>{{ useCase.title }}</h3>
            <p>{{ useCase.description }}</p>
            <div class="case-example">{{ useCase.example }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Example Section -->
    <section class="example-section">
      <div class="container">
        <div class="section-header">
          <h2>Пример результата</h2>
          <p>Запрос: "лучшие CRM для малого бизнеса"</p>
        </div>

        <div class="example-result glass-card">
          <div class="example-header">
            <i class="pi pi-file-edit"></i>
            <span>Заметка создана автоматически</span>
          </div>
          <div class="example-content">
            <h4>Лучшие CRM для малого бизнеса 2025</h4>
            <ul>
              <li><strong>HubSpot CRM</strong> — бесплатно до 1000 контактов, идеален для старта</li>
              <li><strong>Pipedrive</strong> — от $12/мес, лучший для продаж</li>
              <li><strong>Zoho CRM</strong> — от $14/мес, много интеграций</li>
            </ul>
            <div class="sources">
              <span>Источники: G2, Capterra, ProductHunt</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="feature-cta">
      <div class="container">
        <div class="cta-card animated-gradient">
          <h2>Попробовать поиск</h2>
          <p>Получите структурированную информацию за секунды</p>
          <div class="cta-actions">
            <button class="btn-white btn-large" @click="trySearch">
              <i class="pi pi-search"></i>
              Начать бесплатно
            </button>
            <router-link to="/features" class="btn-glass btn-large">
              Все возможности
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- Demo Modal -->
    <Dialog v-model:visible="showDemo" modal header="Демо веб-поиска"
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

const problems = [
  { icon: 'pi pi-clone', title: 'Временные затраты', description: 'Открываете 10+ вкладок, читаете статьи, сравниваете — часы работы' },
  { icon: 'pi pi-calendar-times', title: 'Устаревшая информация', description: 'Статьи 2-3 летней давности с неактуальными ценами и функциями' },
  { icon: 'pi pi-sitemap', title: 'Разрозненность', description: 'Информация разбросана по разным источникам, сложно систематизировать' }
]

const steps = [
  { title: 'Запрос', description: 'Напишите или продиктуйте, что нужно найти' },
  { title: 'AI поиск', description: 'TaskFlow ищет актуальную информацию в интернете' },
  { title: 'Готовая заметка', description: 'Получите структурированный результат с источниками' }
]

const useCases = [
  { icon: 'pi pi-dollar', title: 'Цены и тарифы', description: 'Сравнение цен на продукты и услуги', example: '"тарифы облачных хранилищ 2025"' },
  { icon: 'pi pi-phone', title: 'Контакты', description: 'Поиск контактов компаний и специалистов', example: '"контакты техподдержки Apple"' },
  { icon: 'pi pi-chart-bar', title: 'Статистика', description: 'Актуальные данные и исследования', example: '"статистика e-commerce в России"' },
  { icon: 'pi pi-book', title: 'Курсы', description: 'Обзор образовательных программ', example: '"лучшие курсы по Python"' },
  { icon: 'pi pi-calendar', title: 'События', description: 'Конференции, митапы, вебинары', example: '"IT-конференции Москва 2025"' },
  { icon: 'pi pi-wrench', title: 'Инструменты', description: 'Обзор софта и сервисов', example: '"инструменты для дизайнеров"' }
]

onMounted(() => {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) entry.target.classList.add('visible')
    })
  }, { threshold: 0.1 })
  document.querySelectorAll('.fade-in-up, .fade-in-right').forEach((el) => observer.observe(el))
})

const trySearch = () => { router.push('/register') }
</script>

<style scoped lang="scss">
@import '@/styles/landing.scss';

.feature-hero {
  position: relative;
  padding: 8rem 0 4rem;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
    @media (max-width: 768px) { grid-template-columns: 1fr; text-align: center; }
  }

  .feature-badge { display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(10px); padding: 0.5rem 1rem; border-radius: 50px; color: white; margin-bottom: 1rem; }
  h1 { color: white; font-size: 3.5rem; margin-bottom: 1rem; @media (max-width: 768px) { font-size: 2.5rem; } }
  .lead { color: rgba(255, 255, 255, 0.9); font-size: 1.25rem; line-height: 1.6; margin-bottom: 2rem; }
  .hero-actions { display: flex; gap: 1rem; @media (max-width: 768px) { flex-direction: column; } }
  .btn-large { padding: 1rem 2rem; font-size: 1.1rem; display: inline-flex; align-items: center; gap: 0.5rem; }

  .search-animation {
    max-width: 320px;
    margin: 0 auto;

    .search-box {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      background: white;
      padding: 1rem 1.5rem;
      border-radius: 12px;
      margin-bottom: 1rem;
      i { color: var(--gray-400); }
      span { color: var(--gray-700); font-size: 0.9rem; }
    }

    .result-preview {
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      padding: 1rem;

      .result-item {
        padding: 0.5rem 0;
        color: white;
        font-size: 0.875rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        &:last-child { border-bottom: none; }
      }
    }
  }
}

.problems-section {
  padding: 5rem 0;
  background: var(--gray-50);

  .problems-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 3rem;
    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .problem-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    &:hover { transform: translateY(-5px); box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); }
    .problem-icon { width: 60px; height: 60px; margin: 0 auto 1.5rem; background: #fee2e2; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1.5rem; }
    h3 { margin-bottom: 1rem; color: var(--gray-900); }
    p { color: var(--gray-600); line-height: 1.6; }
  }
}

.how-it-works-section {
  padding: 5rem 0;
  background: white;

  .steps-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 3rem;
    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .step-card {
    text-align: center;
    padding: 2rem;
    position: relative;

    .step-number {
      width: 50px;
      height: 50px;
      background: var(--gradient-primary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
      font-weight: 700;
      margin: 0 auto 1.5rem;
    }

    h3 { margin-bottom: 1rem; color: var(--gray-900); }
    p { color: var(--gray-600); line-height: 1.6; }
  }
}

.use-cases-section {
  padding: 5rem 0;
  background: var(--gray-50);

  .cases-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 3rem;
    @media (max-width: 1024px) { grid-template-columns: repeat(2, 1fr); }
    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .case-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    transition: all 0.3s ease;
    &:hover { transform: translateY(-5px); box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); }
    .case-icon { width: 50px; height: 50px; background: var(--gradient-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.25rem; margin-bottom: 1rem; }
    h3 { margin-bottom: 0.5rem; color: var(--gray-900); }
    p { color: var(--gray-600); line-height: 1.5; margin-bottom: 1rem; font-size: 0.9rem; }
    .case-example { padding: 0.75rem; background: var(--gray-100); border-radius: 8px; font-size: 0.8rem; color: var(--gray-700); font-style: italic; }
  }
}

.example-section {
  padding: 5rem 0;
  background: white;

  .example-result {
    max-width: 600px;
    margin: 3rem auto 0;
    padding: 2rem;

    .example-header {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 1.5rem;
      color: var(--primary);
      font-weight: 600;
    }

    .example-content {
      h4 { margin-bottom: 1rem; color: var(--gray-900); }
      ul { list-style: none; padding: 0; margin: 0 0 1.5rem;
        li { padding: 0.75rem 0; border-bottom: 1px solid var(--gray-200); color: var(--gray-700); &:last-child { border-bottom: none; } strong { color: var(--gray-900); } }
      }
      .sources { font-size: 0.875rem; color: var(--gray-500); }
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
    .cta-actions { display: flex; gap: 1rem; justify-content: center; @media (max-width: 768px) { flex-direction: column; } }
  }
}

.btn-white { background: white; color: var(--primary); padding: 12px 32px; border-radius: 50px; font-weight: 600; transition: all 0.3s ease; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; &:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); } }
.demo-content video { width: 100%; border-radius: 12px; }
.section-header { text-align: center; margin-bottom: 3rem; h2 { font-size: 2.5rem; margin-bottom: 1rem; color: var(--gray-900); } p { font-size: 1.25rem; color: var(--gray-600); } }
.container { max-width: 1280px; margin: 0 auto; padding: 0 1rem; }
.fade-in-up { opacity: 0; transform: translateY(30px); transition: all 0.6s ease-out; &.visible { opacity: 1; transform: translateY(0); } }
.fade-in-right { opacity: 0; transform: translateX(30px); transition: all 0.6s ease-out; &.visible { opacity: 1; transform: translateX(0); } }
@keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
</style>
