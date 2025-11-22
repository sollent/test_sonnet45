<template>
  <LandingLayout>
    <!-- Hero Section -->
    <section class="feature-hero interface-hero">
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
              <i class="pi pi-desktop"></i>
              Веб-интерфейс
            </div>

            <h1>Чистый веб-интерфейс <span class="gradient-text-light">без лишнего</span></h1>
            <p class="lead">
              Минималистичный дизайн, молниеносная загрузка и интуитивное управление.
              Только то, что нужно для продуктивной работы.
            </p>

            <div class="hero-actions">
              <button class="btn-primary btn-large" @click="tryInterface">
                <i class="pi pi-desktop"></i>
                Попробовать интерфейс
              </button>
              <button class="btn-glass btn-large" @click="showDemo = true">
                <i class="pi pi-video"></i>
                Смотреть демо
              </button>
            </div>
          </div>

          <div class="hero-visual fade-in-right">
            <div class="interface-preview">
              <div class="browser-frame">
                <div class="browser-dots">
                  <span></span><span></span><span></span>
                </div>
                <div class="browser-content">
                  <div class="task-item">
                    <i class="pi pi-check-circle"></i>
                    <span>Подготовить отчет</span>
                  </div>
                  <div class="task-item">
                    <i class="pi pi-circle"></i>
                    <span>Позвонить клиенту</span>
                  </div>
                  <div class="task-item">
                    <i class="pi pi-circle"></i>
                    <span>Обновить документацию</span>
                  </div>
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
          <h2>Проблемы сложных интерфейсов</h2>
          <p>Почему перегруженные планировщики снижают продуктивность</p>
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

    <!-- Design Principles Section -->
    <section class="principles-section">
      <div class="container">
        <div class="section-header">
          <h2>Принципы дизайна</h2>
          <p>Каждое решение направлено на вашу продуктивность</p>
        </div>

        <div class="principles-grid">
          <div class="principle-card" v-for="principle in principles" :key="principle.title">
            <div class="principle-icon" :style="`background: ${principle.color}`">
              <i :class="principle.icon"></i>
            </div>
            <h3>{{ principle.title }}</h3>
            <p>{{ principle.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
      <div class="container">
        <div class="section-header">
          <h2>Ключевые функции</h2>
          <p>Все для быстрой и комфортной работы</p>
        </div>

        <div class="features-grid">
          <div class="feature-card" v-for="feature in features" :key="feature.title">
            <div class="feature-icon"><i :class="feature.icon"></i></div>
            <h3>{{ feature.title }}</h3>
            <p>{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Comparison Section -->
    <section class="comparison-section">
      <div class="container">
        <div class="section-header">
          <h2>Сравнение скорости</h2>
          <p>TaskFlow vs сложные планировщики</p>
        </div>

        <div class="comparison-content">
          <div class="comparison-item">
            <div class="comparison-label">Сложные планировщики</div>
            <div class="comparison-bar slow">
              <div class="bar-fill" style="width: 100%"></div>
            </div>
            <div class="comparison-value">3-5 сек</div>
          </div>
          <div class="comparison-item">
            <div class="comparison-label">TaskFlow</div>
            <div class="comparison-bar fast">
              <div class="bar-fill" style="width: 20%"></div>
            </div>
            <div class="comparison-value">&lt; 1 сек</div>
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
    <Dialog v-model:visible="showDemo" modal header="Демо веб-интерфейса"
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
  { icon: 'pi pi-th-large', title: 'Много функций', description: 'Десятки кнопок и меню отвлекают от главного — выполнения задач' },
  { icon: 'pi pi-clock', title: 'Медленная загрузка', description: 'Тяжелые интерфейсы грузятся 3-5 секунд, убивая концентрацию' },
  { icon: 'pi pi-palette', title: 'Отвлекающий дизайн', description: 'Яркие цвета и анимации мешают фокусироваться на работе' }
]

const principles = [
  { icon: 'pi pi-bolt', title: 'Скорость', description: 'Загрузка менее 1 секунды, мгновенный отклик на действия', color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
  { icon: 'pi pi-minus', title: 'Минимализм', description: 'Только нужные функции, ничего лишнего', color: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' },
  { icon: 'pi pi-eye', title: 'Интуитивность', description: 'Понятный без инструкций, начните работать сразу', color: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
  { icon: 'pi pi-mobile', title: 'Адаптивность', description: 'Одинаково удобен на десктопе, планшете и телефоне', color: 'linear-gradient(135deg, #13ce66 0%, #00d2d3 100%)' }
]

const features = [
  { icon: 'pi pi-key', title: 'Горячие клавиши', description: 'Быстрые действия без мыши: N — новая задача, / — поиск' },
  { icon: 'pi pi-search', title: 'Умный поиск', description: 'Найдите любую задачу за секунды по названию или тегу' },
  { icon: 'pi pi-moon', title: 'Темная тема', description: 'Комфортная работа в любое время суток' },
  { icon: 'pi pi-arrows-alt', title: 'Drag & Drop', description: 'Перетаскивайте задачи между списками и датами' },
  { icon: 'pi pi-sync', title: 'Синхронизация', description: 'Мгновенное обновление на всех устройствах' },
  { icon: 'pi pi-wifi', title: 'Оффлайн режим', description: 'Работайте без интернета, синхронизация при подключении' }
]

onMounted(() => {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) entry.target.classList.add('visible')
    })
  }, { threshold: 0.1 })
  document.querySelectorAll('.fade-in-up, .fade-in-right').forEach((el) => observer.observe(el))
})

const tryInterface = () => { router.push('/register') }
</script>

<style scoped lang="scss">
@import '@/styles/landing.scss';

.feature-hero {
  position: relative;
  padding: 8rem 0 4rem;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
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

  .interface-preview .browser-frame {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 320px;
    margin: 0 auto;

    .browser-dots {
      display: flex;
      gap: 6px;
      margin-bottom: 1rem;
      span { width: 10px; height: 10px; border-radius: 50%; background: var(--gray-300); &:first-child { background: #ef4444; } &:nth-child(2) { background: #f59e0b; } &:last-child { background: #10b981; } }
    }

    .browser-content {
      .task-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        background: var(--gray-50);
        i { color: var(--primary); }
        span { color: var(--gray-700); font-size: 0.9rem; }
        &:first-child i { color: #10b981; }
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

.principles-section {
  padding: 5rem 0;
  background: white;

  .principles-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
    margin-top: 3rem;
    @media (max-width: 1024px) { grid-template-columns: repeat(2, 1fr); }
    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .principle-card {
    text-align: center;
    padding: 2rem;
    border-radius: 16px;
    background: var(--gray-50);
    transition: all 0.3s ease;
    &:hover { transform: translateY(-5px); box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); background: white; }
    .principle-icon { width: 60px; height: 60px; margin: 0 auto 1.5rem; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; }
    h3 { margin-bottom: 0.5rem; color: var(--gray-900); }
    p { color: var(--gray-600); line-height: 1.5; font-size: 0.9rem; }
  }
}

.features-section {
  padding: 5rem 0;
  background: var(--gray-50);

  .features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 3rem;
    @media (max-width: 1024px) { grid-template-columns: repeat(2, 1fr); }
    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .feature-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    transition: all 0.3s ease;
    &:hover { transform: translateY(-5px); box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); }
    .feature-icon { width: 50px; height: 50px; background: var(--gradient-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.25rem; margin-bottom: 1rem; }
    h3 { margin-bottom: 0.5rem; color: var(--gray-900); }
    p { color: var(--gray-600); line-height: 1.5; font-size: 0.9rem; }
  }
}

.comparison-section {
  padding: 5rem 0;
  background: white;

  .comparison-content {
    max-width: 600px;
    margin: 3rem auto 0;
  }

  .comparison-item {
    display: grid;
    grid-template-columns: 180px 1fr 80px;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;

    .comparison-label { font-weight: 600; color: var(--gray-700); }
    .comparison-bar {
      height: 12px;
      background: var(--gray-200);
      border-radius: 6px;
      overflow: hidden;

      .bar-fill { height: 100%; border-radius: 6px; }
      &.slow .bar-fill { background: #ef4444; }
      &.fast .bar-fill { background: var(--gradient-primary); }
    }
    .comparison-value { font-weight: 700; color: var(--gray-900); text-align: right; }
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
