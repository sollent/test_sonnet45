<template>
  <LandingLayout>
    <!-- Hero Section -->
    <section class="feature-hero reminders-hero">
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
              <i class="pi pi-bell"></i>
              Умные напоминания
            </div>

            <h1>Напоминания, которые <span class="gradient-text-light">не забудешь</span></h1>
            <p class="lead">
              Контекстные напоминания с полной информацией о задаче, в нужное время,
              с быстрыми действиями прямо из уведомления.
            </p>

            <div class="hero-actions">
              <button class="btn-primary btn-large" @click="tryReminders">
                <i class="pi pi-bell"></i>
                Попробовать бесплатно
              </button>
              <button class="btn-glass btn-large" @click="showDemo = true">
                <i class="pi pi-video"></i>
                Смотреть демо
              </button>
            </div>
          </div>

          <div class="hero-visual fade-in-right">
            <div class="reminder-animation">
              <div class="notification-card">
                <div class="notification-header">
                  <i class="pi pi-bell"></i>
                  <span>TaskFlow</span>
                  <span class="time">сейчас</span>
                </div>
                <div class="notification-body">
                  <strong>Позвонить клиенту Иванову</strong>
                  <p>Высокий приоритет - Договор #2024-156</p>
                </div>
                <div class="notification-actions">
                  <button>Выполнено</button>
                  <button>+15 мин</button>
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
          <h2>Проблемы обычных напоминаний</h2>
          <p>Почему стандартные напоминания не работают</p>
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

    <!-- Solution Section -->
    <section class="solution-section">
      <div class="container">
        <div class="section-header">
          <h2>Наше решение</h2>
          <p>Умные напоминания с полным контекстом</p>
        </div>

        <div class="solution-grid">
          <div class="solution-card" v-for="solution in solutions" :key="solution.title">
            <div class="solution-icon" :style="`background: ${solution.color}`">
              <i :class="solution.icon"></i>
            </div>
            <h3>{{ solution.title }}</h3>
            <p>{{ solution.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Reminder Types Section -->
    <section class="types-section">
      <div class="container">
        <div class="section-header">
          <h2>Типы напоминаний</h2>
          <p>Разные напоминания для разных ситуаций</p>
        </div>

        <div class="types-grid">
          <div class="type-card" v-for="type in reminderTypes" :key="type.title">
            <div class="type-icon"><i :class="type.icon"></i></div>
            <h3>{{ type.title }}</h3>
            <p>{{ type.description }}</p>
            <div class="type-example">{{ type.example }}</div>
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
    <Dialog v-model:visible="showDemo" modal header="Демо умных напоминаний"
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

const router = useRouter()
const showDemo = ref(false)

useScrollAnimations()

const problems = [
  { icon: 'pi pi-question-circle', title: 'Без контекста', description: 'Получаете напоминание "Встреча" и не помните, о чем речь и где документы' },
  { icon: 'pi pi-clock', title: 'Неудобное время', description: 'Напоминание приходит в 9:00, хотя встреча в 10:00 — нет времени подготовиться' },
  { icon: 'pi pi-eye-slash', title: 'Привыкание', description: 'Через неделю вы перестаете обращать внимание на уведомления' }
]

const solutions = [
  { icon: 'pi pi-info-circle', title: 'Полный контекст', description: 'Вся информация о задаче: файлы, ссылки, комментарии — в одном уведомлении', color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
  { icon: 'pi pi-clock', title: 'Умное время', description: 'AI подбирает оптимальное время с учетом вашего календаря и привычек', color: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' },
  { icon: 'pi pi-bolt', title: 'Быстрые действия', description: 'Выполнить, отложить, делегировать — прямо из уведомления', color: 'linear-gradient(135deg, #13ce66 0%, #00d2d3 100%)' }
]

const reminderTypes = [
  { icon: 'pi pi-map-marker', title: 'Геолокационные', description: 'Напоминание при приближении к месту', example: 'Купить молоко - напоминание у магазина' },
  { icon: 'pi pi-refresh', title: 'Повторяющиеся', description: 'Регулярные напоминания по расписанию', example: 'Каждый понедельник в 9:00' },
  { icon: 'pi pi-users', title: 'Командные', description: 'Напоминания для всех участников задачи', example: 'Напомнить команде о дедлайне' },
  { icon: 'pi pi-exclamation-triangle', title: 'Приоритетные', description: 'Эскалация для важных задач', example: 'Повторять каждые 30 мин до выполнения' },
  { icon: 'pi pi-brain', title: 'Адаптивные', description: 'AI учится на ваших реакциях', example: 'Сдвиг времени на основе привычек' },
  { icon: 'pi pi-link', title: 'С интеграциями', description: 'Напоминания в Telegram, Slack, Email', example: 'Уведомление в удобном канале' }
]

const tryReminders = () => { router.push('/register') }
</script>

<style scoped lang="scss">
@import '@/styles/landing.scss';

.feature-hero {
  position: relative;
  padding: 8rem 0 4rem;
  background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
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

  .reminder-animation .notification-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 320px;
    margin: 0 auto;
    animation: slideIn 0.5s ease-out;

    .notification-header {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 1rem;
      color: var(--gray-500);
      font-size: 0.875rem;
      i { color: var(--primary); }
      .time { margin-left: auto; }
    }

    .notification-body {
      margin-bottom: 1rem;
      strong { display: block; color: var(--gray-900); margin-bottom: 0.5rem; }
      p { color: var(--gray-600); font-size: 0.875rem; margin: 0; }
    }

    .notification-actions {
      display: flex;
      gap: 0.5rem;
      button {
        flex: 1;
        padding: 0.5rem;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        background: white;
        color: var(--gray-700);
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s ease;
        &:first-child { background: var(--primary); border-color: var(--primary); color: white; }
        &:hover { transform: translateY(-2px); }
      }
    }
  }
}

@keyframes slideIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

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

.solution-section {
  padding: 5rem 0;
  background: white;

  .solution-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 3rem;
    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .solution-card {
    text-align: center;
    padding: 2rem;
    border-radius: 16px;
    background: var(--gray-50);
    transition: all 0.3s ease;
    &:hover { transform: translateY(-5px); box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); background: white; }
    .solution-icon { width: 60px; height: 60px; margin: 0 auto 1.5rem; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; }
    h3 { margin-bottom: 1rem; color: var(--gray-900); }
    p { color: var(--gray-600); line-height: 1.6; }
  }
}

.types-section {
  padding: 5rem 0;
  background: var(--gray-50);

  .types-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 3rem;
    @media (max-width: 1024px) { grid-template-columns: repeat(2, 1fr); }
    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .type-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    transition: all 0.3s ease;
    &:hover { transform: translateY(-5px); box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); }
    .type-icon { width: 50px; height: 50px; background: var(--gradient-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.25rem; margin-bottom: 1rem; i.pi { color: white !important; } }
    h3 { margin-bottom: 0.5rem; color: var(--gray-900); }
    p { color: var(--gray-600); line-height: 1.5; margin-bottom: 1rem; font-size: 0.9rem; }
    .type-example { padding: 0.75rem; background: var(--gray-100); border-radius: 8px; font-size: 0.8rem; color: var(--gray-700); font-style: italic; }
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
