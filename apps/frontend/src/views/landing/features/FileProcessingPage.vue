<template>
  <LandingLayout>
    <!-- Hero Section -->
    <section class="feature-hero file-hero">
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
              <i class="pi pi-file"></i>
              Обработка файлов
            </div>

            <h1>Любой файл — <span class="gradient-text-light">готовая задача</span></h1>
            <p class="lead">
              Загрузите PDF, Word или изображение — AI проанализирует содержимое
              и создаст структурированные задачи с дедлайнами и приоритетами.
            </p>

            <div class="hero-actions">
              <button class="btn-primary btn-large" @click="uploadFile">
                <i class="pi pi-upload"></i>
                Загрузить первый файл
              </button>
              <button class="btn-glass btn-large" @click="showDemo = true">
                <i class="pi pi-video"></i>
                Смотреть демо
              </button>
            </div>
          </div>

          <div class="hero-visual fade-in-right">
            <div class="file-animation">
              <div class="file-stack">
                <div class="file-item pdf"><i class="pi pi-file-pdf"></i> PDF</div>
                <div class="file-item doc"><i class="pi pi-file"></i> DOCX</div>
                <div class="file-item img"><i class="pi pi-image"></i> IMG</div>
              </div>
              <div class="arrow-down"><i class="pi pi-arrow-down"></i></div>
              <div class="task-result"><i class="pi pi-check-circle"></i> Задачи созданы</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Supported Formats Section -->
    <section class="formats-section">
      <div class="container">
        <div class="section-header">
          <h2>Поддерживаемые форматы</h2>
          <p>AI работает с любыми типами документов</p>
        </div>

        <div class="formats-grid">
          <div class="format-card fade-in-up" v-for="format in formats" :key="format.title">
            <div class="format-icon" :style="`background: ${format.color}`">
              <i :class="format.icon"></i>
            </div>
            <h3>{{ format.title }}</h3>
            <p>{{ format.description }}</p>
            <div class="format-examples">
              <span v-for="ext in format.extensions" :key="ext">{{ ext }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Processing Examples Section -->
    <section class="processing-section">
      <div class="container">
        <div class="section-header">
          <h2>Примеры обработки</h2>
          <p>Как AI превращает файлы в задачи</p>
        </div>

        <div class="processing-examples">
          <div class="processing-card" v-for="example in processingExamples" :key="example.file">
            <div class="processing-header">
              <div class="file-preview">
                <i :class="example.icon"></i>
                <span>{{ example.file }}</span>
              </div>
            </div>
            <div class="processing-result">
              <div class="result-title">Извлеченные задачи:</div>
              <ul>
                <li v-for="task in example.tasks" :key="task">{{ task }}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- User Cases Section -->
    <section class="cases-section">
      <div class="container">
        <div class="section-header">
          <h2>Кейсы использования</h2>
          <p>Как профессионалы экономят время с TaskFlow</p>
        </div>

        <div class="cases-grid">
          <div class="case-card" v-for="userCase in userCases" :key="userCase.role">
            <div class="case-header">
              <div class="case-avatar">{{ userCase.avatar }}</div>
              <div>
                <h4>{{ userCase.role }}</h4>
                <span class="case-stat">{{ userCase.stat }}</span>
              </div>
            </div>
            <p>{{ userCase.description }}</p>
            <div class="case-result">
              <i class="pi pi-chart-line"></i>
              {{ userCase.result }}
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Benefits Section -->
    <section class="benefits-section">
      <div class="container">
        <div class="benefits-content">
          <div class="benefits-text">
            <h2>Преимущества обработки файлов</h2>
            <p class="lead">Экономьте часы на ручном разборе документов</p>

            <div class="benefits-list">
              <div class="benefit-item" v-for="benefit in benefits" :key="benefit.title">
                <div class="benefit-icon"><i :class="benefit.icon"></i></div>
                <div>
                  <h4>{{ benefit.title }}</h4>
                  <p>{{ benefit.description }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="benefits-visual">
            <div class="stats-card glass-card">
              <div class="stat-item">
                <div class="stat-value">30 сек</div>
                <div class="stat-label">Анализ документа</div>
              </div>
              <div class="stat-item">
                <div class="stat-value">100%</div>
                <div class="stat-label">Контекстное понимание</div>
              </div>
              <div class="stat-item">
                <div class="stat-value">Есть</div>
                <div class="stat-label">Связь с источником</div>
              </div>
            </div>
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
    <Dialog v-model:visible="showDemo" modal header="Демо обработки файлов"
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
import { useSeoMeta, seoConfigs } from '@/composables/useSeoMeta'

const router = useRouter()
const showDemo = ref(false)

useScrollAnimations()
useSeoMeta(seoConfigs.fileProcessing)

const formats = [
  { icon: 'pi pi-file-pdf', title: 'PDF документы', description: 'Договоры, ТЗ, отчеты — AI извлекает задачи и дедлайны', extensions: ['.pdf'], color: 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)' },
  { icon: 'pi pi-file', title: 'Word документы', description: 'Протоколы встреч, планы, спецификации', extensions: ['.doc', '.docx'], color: 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)' },
  { icon: 'pi pi-image', title: 'Изображения (OCR)', description: 'Фото документов, скриншоты, рукописные заметки', extensions: ['.jpg', '.png', '.webp'], color: 'linear-gradient(135deg, #10b981 0%, #059669 100%)' },
  { icon: 'pi pi-file', title: 'Текстовые файлы', description: 'Заметки, списки, markdown файлы', extensions: ['.txt', '.md'], color: 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)' }
]

const processingExamples = [
  { icon: 'pi pi-file-pdf', file: 'contract_2024.pdf', tasks: ['Подписать договор до 25 ноября', 'Отправить копию бухгалтерии', 'Запланировать первую поставку на декабрь'] },
  { icon: 'pi pi-file', file: 'meeting_notes.docx', tasks: ['Подготовить презентацию к пятнице', 'Связаться с дизайнером по макетам', 'Обновить roadmap продукта'] },
  { icon: 'pi pi-image', file: 'whiteboard_photo.jpg', tasks: ['Реализовать авторизацию через Google', 'Добавить экспорт в Excel', 'Оптимизировать загрузку страницы'] }
]

const userCases = [
  { avatar: 'Ю', role: 'Юрист', stat: '15+ договоров/неделю', description: 'Загружаю договоры — TaskFlow извлекает все сроки и обязательства.', result: 'Экономия 15+ часов/неделю' },
  { avatar: 'Д', role: 'UX-дизайнер', stat: '50+ экранов/проект', description: 'Фото с доски после брейншторма превращаются в задачи с тегами.', result: '-80% времени на документирование' },
  { avatar: 'П', role: 'Project Manager', stat: '10+ проектов', description: 'Протоколы встреч автоматически становятся задачами с исполнителями.', result: '200 задач/месяц автоматически' },
  { avatar: 'И', role: 'Исследователь', stat: '100+ статей/месяц', description: 'PDF научных статей превращаются в структурированные заметки.', result: '500+ заметок организовано' }
]

const benefits = [
  { icon: 'pi pi-bolt', title: '30 секунд анализ', description: 'AI анализирует документ любого размера за полминуты' },
  { icon: 'pi pi-brain', title: 'Контекстное понимание', description: 'Понимает смысл, а не просто ищет ключевые слова' },
  { icon: 'pi pi-link', title: 'Связь с источником', description: 'Каждая задача содержит ссылку на исходный документ' },
  { icon: 'pi pi-history', title: 'История версий', description: 'Отслеживание изменений при повторной загрузке' }
]

const uploadFile = () => { router.push('/register') }
</script>

<style scoped lang="scss">
@import '@/styles/landing.scss';

.feature-hero {
  position: relative;
  padding: 8rem 0 4rem;
  background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
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

  h1 { color: white; font-size: 3.5rem; margin-bottom: 1rem; @media (max-width: 768px) { font-size: 2.5rem; } }
  .lead { color: rgba(255, 255, 255, 0.9); font-size: 1.25rem; line-height: 1.6; margin-bottom: 2rem; }
  .hero-actions { display: flex; gap: 1rem; @media (max-width: 768px) { flex-direction: column; } }
  .btn-large { padding: 1rem 2rem; font-size: 1.1rem; display: inline-flex; align-items: center; gap: 0.5rem; }

  .file-animation {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.5rem;

    .file-stack {
      display: flex;
      gap: 1rem;

      .file-item {
        width: 80px;
        height: 100px;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
        animation: bounce 2s ease-in-out infinite;
        i { font-size: 2rem; }
        &.pdf { background: #ef4444; animation-delay: 0s; }
        &.doc { background: #3b82f6; animation-delay: 0.2s; }
        &.img { background: #10b981; animation-delay: 0.4s; }
      }
    }

    .arrow-down { color: white; font-size: 1.5rem; animation: pulse 1.5s ease-in-out infinite; }
    .task-result {
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(10px);
      padding: 1rem 2rem;
      border-radius: 12px;
      color: white;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-weight: 600;
      i { color: #4ade80; }
    }
  }
}

@keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

.formats-section {
  padding: 5rem 0;
  background: white;

  .formats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
    margin-top: 3rem;
    @media (max-width: 1024px) { grid-template-columns: repeat(2, 1fr); }
    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .format-card {
    text-align: center;
    padding: 2rem;
    border-radius: 16px;
    background: var(--gray-50);
    transition: all 0.3s ease;
    &:hover { transform: translateY(-5px); box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); background: white;
      .format-icon { color: var(--primary); }
    }

    .format-icon {
      width: 60px;
      height: 60px;
      margin: 0 auto 1.5rem;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
      transition: color 0.3s ease;
    }

    h3 { margin-bottom: 0.5rem; color: var(--gray-900); }
    p { color: var(--gray-600); line-height: 1.5; margin-bottom: 1rem; font-size: 0.9rem; }

    .format-examples {
      display: flex;
      gap: 0.5rem;
      justify-content: center;
      flex-wrap: wrap;
      span {
        background: var(--gray-200);
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        color: var(--gray-700);
        font-family: monospace;
      }
    }
  }
}

.processing-section {
  padding: 5rem 0;
  background: var(--gray-50);

  .processing-examples {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    margin-top: 3rem;
    @media (max-width: 1024px) { grid-template-columns: 1fr; }
  }

  .processing-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
    &:hover { transform: translateY(-5px); box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); }

    .processing-header {
      padding: 1.5rem;
      background: var(--gray-100);
      .file-preview { display: flex; align-items: center; gap: 0.75rem; i { font-size: 1.5rem; color: var(--primary); } span { font-family: monospace; color: var(--gray-700); } }
    }

    .processing-result {
      padding: 1.5rem;
      .result-title { font-weight: 600; color: var(--gray-900); margin-bottom: 1rem; }
      ul { list-style: none; padding: 0; margin: 0;
        li { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0; color: var(--gray-700); font-size: 0.9rem;
          &::before { content: '✓'; color: var(--primary); font-weight: 600; }
        }
      }
    }
  }
}

.cases-section {
  padding: 5rem 0;
  background: white;

  .cases-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
    margin-top: 3rem;
    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .case-card {
    background: var(--gray-50);
    border-radius: 16px;
    padding: 2rem;
    transition: all 0.3s ease;
    &:hover { transform: translateY(-5px); box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); }

    .case-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
      .case-avatar { width: 50px; height: 50px; background: var(--gradient-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1.25rem; }
      h4 { margin: 0; color: var(--gray-900); }
      .case-stat { font-size: 0.875rem; color: var(--gray-500); }
    }

    p { color: var(--gray-700); line-height: 1.6; margin-bottom: 1rem; }
    .case-result { display: flex; align-items: center; gap: 0.5rem; color: var(--primary); font-weight: 600; }
  }
}

.benefits-section {
  padding: 5rem 0;
  background: var(--gray-50);

  .benefits-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
    @media (max-width: 768px) { grid-template-columns: 1fr; }
  }

  .benefits-list {
    margin-top: 2rem;
    .benefit-item {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;
      .benefit-icon { width: 48px; height: 48px; background: var(--gradient-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; }
      h4 { margin-bottom: 0.5rem; color: var(--gray-900); }
      p { color: var(--gray-600); line-height: 1.5; }
    }
  }

  .stats-card {
    padding: 2rem;
    .stat-item {
      text-align: center;
      padding: 1.5rem 0;
      border-bottom: 1px solid var(--gray-200);
      &:last-child { border-bottom: none; }
      .stat-value { font-size: 2rem; font-weight: 700; color: var(--primary); }
      .stat-label { color: var(--gray-600); margin-top: 0.5rem; }
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

.btn-white {
  background: white;
  color: var(--primary);
  padding: 12px 32px;
  border-radius: 50px;
  font-weight: 600;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  &:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); }
}

.demo-content video { width: 100%; border-radius: 12px; }
.section-header { text-align: center; margin-bottom: 3rem; h2 { font-size: 2.5rem; margin-bottom: 1rem; color: var(--gray-900); } p { font-size: 1.25rem; color: var(--gray-600); } }
.container { max-width: 1280px; margin: 0 auto; padding: 0 1rem; }
.fade-in-up { opacity: 0; transform: translateY(30px); transition: all 0.6s ease-out; &.visible { opacity: 1; transform: translateY(0); } }
.fade-in-right { opacity: 0; transform: translateX(30px); transition: all 0.6s ease-out; &.visible { opacity: 1; transform: translateX(0); } }
@keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
</style>
