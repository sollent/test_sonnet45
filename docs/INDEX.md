# 📚 Task Manager - Полный Индекс Документации

> **Для AI-ассистентов**: Эта документация специально разработана для предоставления полного контекста проекта. Сначала прочитайте этот ИНДЕКС, затем переходите к релевантным разделам в зависимости от вашей задачи.

---

## 🎯 Быстрый Старт для AI

**Новичок в этом проекте?** Следуйте этому порядку чтения:

1. **[Обзор Проекта](PROJECT_OVERVIEW.md)** - Понимание того, что мы строим *(5 мин чтения)*
2. **[Технологический Стек](TECH_STACK.md)** - Технологии и версии *(3 мин чтения)*
3. **[Стандарты Кодирования](CODING_STANDARDS.md)** - Как мы пишем код (КРИТИЧНО) *(10 мин чтения)*
4. **[Архитектура](backend/ARCHITECTURE.md)** - Паттерны проектирования системы *(8 мин чтения)*

**Уже знакомы?** Переходите к:
- **[Справочник API](backend/API_REFERENCE.md)** - Все эндпоинты задокументированы
- **[Решение Проблем](guides/TROUBLESHOOTING.md)** - Частые проблемы и решения
- **[Рабочий Процесс Разработки](guides/DEVELOPMENT_WORKFLOW.md)** - Ежедневная разработка

---

## 📖 Структура Документации

### 🌍 Общее

#### [`PROJECT_OVERVIEW.md`](PROJECT_OVERVIEW.md)
Что это за приложение, его цель, основные функции и бизнес-логика

**Ключевые Темы:**
- Описание приложения
- Основные функции (задачи, подзадачи, календарь, аналитика)
- Бизнес-требования
- Рабочие процессы пользователя

#### [`TECH_STACK.md`](TECH_STACK.md)
Полный технологический стек с версиями и обоснованиями

**Ключевые Темы:**
- Backend стек (Symfony 7.1, PHP 8.3, PostgreSQL)
- Frontend стек (Vue.js 3, TypeScript, PrimeVue)
- Инфраструктура (Docker, Nginx)
- Сторонние сервисы (Google OAuth)

#### [`CODING_STANDARDS.md`](CODING_STANDARDS.md)
**⚠️ КРИТИЧЕСКИЙ ДОКУМЕНТ** - Принципы кодирования и лучшие практики

**Ключевые Темы:**
- Принципы SOLID (применяются везде)
- Принципы GRASP (паттерны проектирования)
- Паттерны GoF (конкретные реализации)
- Backend конвенции (Symfony)
- Frontend конвенции (Vue + TypeScript)
- Чеклист качества кода

---

### 🔧 Backend (`project/backend/`)

#### [`backend/ARCHITECTURE.md`](backend/ARCHITECTURE.md)
Архитектура Backend, слои и паттерны проектирования

**Ключевые Темы:**
- Слоистая архитектура (Controller → Service → Repository)
- Принципы Domain-Driven Design
- Внедрение зависимостей
- Паттерн DTO
- Система событий
- Аутентификация и авторизация


#### [`backend/API_REFERENCE.md`](backend/API_REFERENCE.md)
Полная документация API эндпоинтов

**Ключевые Темы:**
- Эндпоинты аутентификации (JWT, Google OAuth)
- CRUD операции для задач
- Управление тегами
- Эндпоинты аналитики
- Примеры Request/Response
- Ответы с ошибками
- Query параметры и фильтрация

#### [`backend/DATABASE.md`](backend/DATABASE.md)
Схема базы данных, сущности и связи

**Ключевые Темы:**
- Диаграмма связей сущностей
- Сущность Task (с неограниченной вложенностью)
- Сущность User
- Сущность Tag
- Хранение JWT refresh токенов
- Рабочий процесс миграций

#### [`backend/AUTHENTICATION.md`](backend/AUTHENTICATION.md)
Реализация аутентификации и авторизации

**Ключевые Темы:**
- Поток JWT токенов (access + refresh)
- Интеграция Google OAuth2
- Механизм обновления токенов
- Security voters
- Контроль доступа на основе ролей

#### [`backend/RECURRENCE_TASKS.md`](backend/RECURRENCE_TASKS.md)
Функциональность и реализация повторяющихся задач

**Ключевые Темы:**
- Сущность RecurrenceRule и связи
- Бизнес-логика RecurrenceService
- Паттерн Strategy для типов повторения (daily, weekly, monthly, yearly, custom)
- Автоматическая генерация задач на основе Cron
- CLI команда для обработки правил
- Тестирование и решение проблем
- Примеры использования для каждого типа повторения

#### [`backend/TEST_COVERAGE.md`](backend/TEST_COVERAGE.md)
**📊 Обновлено 2025-11-10** - Полный отчет и анализ покрытия тестами backend

**Ключевые Темы:**
- Статистика покрытия по слоям (Controllers, Services, Repositories, и т.д.)
- Анализ качества тестов (Unit, Integration, Functional)
- Детальные таблицы со всеми 33 тестовыми файлами
- Что покрыто vs что отсутствует (29 компонентов идентифицировано)
- Обновленная оценка покрытия: ~65-70% (было ~75-80%)
- Ссылка на план реализации

#### [`backend/MISSING_TEST_COVERAGE_PLAN.md`](backend/MISSING_TEST_COVERAGE_PLAN.md)
**🎯 НОВОЕ** - Пошаговый план реализации для написания недостающих тестов

**Ключевые Темы:**
- **29 компонентов**, которые нуждаются в тестовом покрытии
- Полная разбивка по приоритету (Critical → High → Medium → Low)
- Детальные тест-кейсы для каждого компонента (с примерами кода)
- 5-фазная дорожная карта реализации (25-30 часов всего)
- Руководство по тестированию (AAA паттерн, моки, фабрики)
- Критерии успеха и цели покрытия
- **Готово к немедленной реализации** ✅

---

### 🎨 Frontend (`project/frontend/`)

#### [`frontend/ARCHITECTURE.md`](frontend/ARCHITECTURE.md)
Архитектура Frontend и организация компонентов

**Ключевые Темы:**
- Паттерны Composition API
- Smart/Dumb компоненты
- Управление состоянием (Pinia stores)
- Архитектура Composables
- Слой сервисов (API вызовы)
- TypeScript strict mode

#### [`frontend/COMPONENTS.md`](frontend/COMPONENTS.md)
Библиотека компонентов и паттерны использования

**Ключевые Темы:**
- Иерархия компонентов
- Интеграция PrimeVue
- Переиспользуемые компоненты
- Паттерны Props & Events
- Использование слотов
- Конвенции стилизации

#### [`frontend/STATE_MANAGEMENT.md`](frontend/STATE_MANAGEMENT.md)
Pinia stores и паттерны состояния

**Ключевые Темы:**
- Организация Store (по доменам)
- TaskStore (основное состояние)
- AuthStore (аутентификация)
- Actions vs Getters
- Оптимистичные обновления

#### [`frontend/API_INTEGRATION.md`](frontend/API_INTEGRATION.md)
Как frontend общается с backend

**Ключевые Темы:**
- Конфигурация Axios
- Слой API сервисов
- Перехватчики Request/Response
- Обработка ошибок
- Управление токенами
- Логика повторных попыток

---

### 🚀 CI/CD & Планы Оптимизации (`project/docs/ci-cd-plans/`)

#### [`CI_CD_SETUP_GUIDE.md`](CI_CD_SETUP_GUIDE.md)
**🚀 НОВОЕ 2025-11-15** - Пошаговая инструкция по активации CI/CD системы (95% → 100%)

**Ключевые Темы:**
- Финальная настройка CI/CD pipeline (7 шагов, ~40 минут)
- Генерация Production Credentials (APP_SECRET, JWT_PASSPHRASE)
- Создание SSH ключа для GitHub Actions
- Создание Telegram бота для уведомлений о деплое
- Настройка 12 GitHub Secrets
- Тестовый запуск CI (backend, frontend, e2e, security)
- Manual Deploy тестирование
- Автоматический деплой при merge в main
- Детальный Troubleshooting для каждого шага
- Вспомогательные скрипты (check-github-secrets.sh, test-ssh-connection.sh)

#### [`CI_CD_COMPLETE_GUIDE.md`](CI_CD_COMPLETE_GUIDE.md)
**📚 ПОЛНЫЙ ГАЙД** - Комплексная система CI/CD на базе GitHub Actions

**Ключевые Темы:**
- Обзор системы (CI + CD архитектура)
- 3 GitHub Actions workflows (CI, Deploy, Rollback)
- Предварительная подготовка (VDS, SSH, Telegram)
- Полный список GitHub Secrets (12 secrets)
- Модификация скриптов для CI/CD
- Health checks и smoke tests
- Rollback стратегия
- Troubleshooting (20+ решений)
- Чеклист готовности

#### [`CI_CD_CONTEXT.md`](CI_CD_CONTEXT.md)
**⚡ КОНТЕКСТ** - Анализ текущего состояния и план внедрения CI/CD

**Ключевые Темы:**
- Что уже есть (Production deployment, Docker, Code quality)
- Что отсутствует для CI/CD
- 5-фазный план внедрения
- Технические детали (env variables, Docker configs)
- Quick Start для CI/CD
- Метрики успеха

#### [`ci-cd-plans/FRONTEND_OPTIMIZATION_PLAN.md`](ci-cd-plans/FRONTEND_OPTIMIZATION_PLAN.md)
**🎯 ПЛАН** - Комплексный план оптимизации производительности Frontend

**Ключевые Темы:**
- Анализ текущих проблем (PrimeVue, ECharts, bundle size)
- Tree-shaking для больших библиотек
- Продвинутая конфигурация Vite для production
- Компрессия Gzip + Brotli
- Manual chunk splitting для оптимального кеширования
- CSS оптимизация и PurgeCSS
- Lazy loading тяжелых компонентов
- Service Worker и PWA
- Preload/Prefetch стратегии
- 3-фазный план реализации (3 недели)
- Ожидаемое улучшение производительности: **60-70%**
- Целевые метрики: Initial bundle ~300KB (gzip ~100KB), Lighthouse 90+

#### [`ci-cd-plans/FRONTEND_DOCKERIZATION_PLAN.md`](ci-cd-plans/FRONTEND_DOCKERIZATION_PLAN.md)
**🐳 НОВОЕ** - План докеризации Frontend с разделением dev/prod окружений

**Ключевые Темы:**
- Анализ текущего состояния (локальный npm run dev)
- Архитектура решения: Dev (Vite dev server) vs Prod (Nginx + статика)
- Dockerfile.dev - Vite dev server с HMR и Hot Reload
- Dockerfile.prod - Multi-stage build (Node.js build → Nginx)
- Nginx конфигурация для SPA (fallback на index.html, proxy /api)
- Интеграция с существующей docker-compose структурой
- Следование Fail-Fast принципу (prod БЕЗ fallback)
- Environment переменные (.env.docker расширение)
- Детальный пошаговый чек-лист реализации
- Ожидаемые результаты: Dev ~1GB, Prod ~30MB
- Команды для запуска полного стека (Backend + Frontend)

---

### 📘 Руководства (`project/docs/guides/`)

#### [`guides/DEVELOPMENT_WORKFLOW.md`](guides/DEVELOPMENT_WORKFLOW.md)
Процесс ежедневной разработки ⚡ **Обновлено 2025-11-13** - добавлена докеризация frontend

**Ключевые Темы:**
- **Настройка Docker** - `docker-compose.yml` в корне + `infrastructure/docker/*.yml` конфиги
- Запуск backend (Symfony через Docker с dev конфигурацией)
- **Запуск frontend (3 варианта):** 🆕
  - Docker (рекомендуется): Vite dev server в контейнере с HMR
  - Локально: `cd apps/frontend && npm run dev`
  - Только frontend: docker-compose frontend-dev.yml
- **Frontend Production режим:** Multi-stage build (Node.js → Nginx ~56MB) 🆕
- Пересборка Docker контейнеров (backend + frontend) 🆕
- Команды полной пересборки проекта
- Миграции базы данных и операции
- Операции PostgreSQL
- Команды консоли Symfony
- Управление контейнерами (логи, перезапуск, проверка здоровья)
- Рабочий процесс тестирования
- Git рабочий процесс и коммиты

#### [`guides/DOCKER_ARCHITECTURE_EXPLAINED.md`](guides/DOCKER_ARCHITECTURE_EXPLAINED.md)
**🆕 НОВОЕ 2025-11-13** - Полное объяснение Docker инфраструктуры

**Ключевые Темы:**
- **Модульная структура** Docker Compose (base + dev/prod overrides)
- **Как работает `make init`** - пошаговое объяснение всех шагов
- **Dev vs Prod режимы** - Hot Reload vs Immutable, fallback vs Fail-Fast
- **Fail-Fast принцип безопасности** - почему это критически важно
- **Makefile команды** - все 40+ команд с подробным описанием
- **FAQ по Docker** - HMR, multi-stage build, volumes, порты, node_modules конфликт
- **Шпаргалка** - первый запуск, ежедневная разработка, решение проблем

#### [`guides/CODE_QUALITY.md`](guides/CODE_QUALITY.md)
Инструменты качества кода и автоматические проверки

**Ключевые Темы:**
- PHP-CS-Fixer (PSR-12 + PHP 8.3 стиль кода)
- PHPStan (статический анализ уровень 5)
- Настройка Git pre-commit hooks
- Makefile команды для проверки качества
- Детали конфигурации и кастомизация
- Решение проблем с инструментами качества

#### [`guides/ENVIRONMENT_CONFIGURATION.md`](guides/ENVIRONMENT_CONFIGURATION.md)
**🔐 НОВОЕ** - Управление переменными окружения для Docker и Symfony

**Ключевые Темы:**
- Двухуровневая система environment файлов (Docker + Symfony)
- Структура `.env.docker*` файлов для инфраструктуры
- Структура `apps/backend/.env*` файлов для приложения
- Конфигурация для dev, prod, test окружений
- Интеграция с GitHub Actions / CI/CD
- Best practices безопасности (что коммитить, что нет)
- Runtime переопределение переменных в production
- Troubleshooting частых проблем с credentials

#### [`guides/PWA_TESTING_GUIDE.md`](guides/PWA_TESTING_GUIDE.md)
**🧪 НОВОЕ** - Руководство по тестированию PWA и Offline кеширования

**Ключевые Темы:**
- Проверка Service Worker регистрации и статуса
- Тестирование Precache (40+ статических файлов)
- Проверка Runtime кеширования API (NetworkFirst стратегия)
- Тест offline режима (работа без интернета)
- Проверка автоматического обновления кеша
- Пошаговые инструкции с примерами
- Troubleshooting частых проблем с PWA
- Чеклист успешного тестирования

#### [`guides/GIT_WORKTREE_STRATEGY.md`](guides/GIT_WORKTREE_STRATEGY.md)
**🌳 НОВОЕ 2025-11-14** - Стратегия параллельной разработки в нескольких ветках

**Ключевые Темы:**
- Git Worktree для одновременной работы в разных ветках
- Изоляция Docker контейнеров через уникальные порты
- Bash скрипты для автоматизации (create, remove, list, stop-all)
- Работа с несколькими сессиями Claude Code параллельно
- Best practices и troubleshooting
- Полная автоматизация через `COMPOSE_PROJECT_NAME`

#### [`guides/TROUBLESHOOTING.md`](guides/TROUBLESHOOTING.md)
Частые проблемы и их решения

**Ключевые Темы:**
- CORS ошибки (решено)
- Смещение дат (решено)
- Мерцание UI (решено)
- Исчерпание памяти (решено)
- Проблемы с Docker
- Проблемы с подключением к базе данных

#### [`guides/testing/TESTING.md`](guides/testing/TESTING.md)
Комплексная стратегия тестирования для backend и frontend

**Ключевые Темы:**
- Backend тестирование (PHPUnit 9.6): Unit, Integration, Functional тесты
- Frontend тестирование (Vitest): 7 тестовых файлов покрывающих все слои
- Организация и структура тестов (3 типа тестов на backend)
- Руководство по написанию (AAA паттерн, моки, изоляция)
- Zenstruck Foundry фабрики для тестовых данных
- ResetDatabase trait и DAMA DoctrineTestBundle
- Happy-dom окружение для frontend
- Интеграция CI/CD и цели покрытия
- Детальные примеры для каждого типа тестов
- Решение частых проблем с тестами

#### [`guides/e2e/E2E_TESTING_PLAN.md`](guides/e2e/E2E_TESTING_PLAN.md)
Полный план и стратегия End-to-End (E2E) браузерного тестирования

**Ключевые Темы:**
- Рекомендация технологического стека (Playwright)
- Архитектура тестов (Page Object Model)
- 100+ детальных тестовых сценариев покрывающих все функции
- Тест-кейсы по функциям (Auth, Tasks, Filters, Calendar, Analytics, Profile)
- Фазы реализации (5-недельный план)
- Примеры интеграции CI/CD
- Цели тестового покрытия и метрики успеха
- Структура page objects и лучшие практики

#### [`guides/e2e/E2E_GIT_WORKFLOW.md`](guides/e2e/E2E_GIT_WORKFLOW.md)
Руководство по Git workflow для разработки E2E тестов в отдельной ветке

**Ключевые Темы:**
- Управление ветками для E2E тестов
- Синхронизация с main веткой без переключения
- Стратегии Rebase vs Merge
- Рабочий процесс Pull Request
- Критические правила для параллельной работы AI

#### [`guides/DEPLOYMENT.md`](guides/DEPLOYMENT.md)
Руководство по production развертыванию

**Ключевые Темы:**
- Конфигурация окружения
- Docker production сборка
- Настройка базы данных
- Настройка SSL/HTTPS
- Мониторинг и логи

#### [`deployment/HTTPS_SETUP.md`](deployment/HTTPS_SETUP.md)
**🆕 НОВОЕ** - Детальное руководство по настройке HTTPS и SSL на production VDS

**Ключевые Темы:**
- Настройка DNS A-записей для доменов
- Генерация Let's Encrypt SSL сертификатов с Certbot
- Полные конфигурации Nginx для frontend и backend
- HTTPS редиректы и www редиректы
- CORS конфигурация для API
- Security headers (HSTS, X-Frame-Options, etc.)
- Обновление Docker контейнеров с новыми доменами
- Автоматическое обновление сертификатов (certbot timer)
- Troubleshooting SSL проблем
- Checklist для production deployment

#### [`guides/voice-ai/VOICE_AI_ASSISTANT_PLAN.md`](guides/voice-ai/VOICE_AI_ASSISTANT_PLAN.md)
План реализации голосового AI ассистента с интеграцией LLM

**Ключевые Темы:**
- Технологический стек (Qwen 2.5, Ollama, Whisper, Centrifugo)
- Архитектура системы и поток данных
- 5-фазный план реализации (19 дней)
- Структура backend сервисов (SOLID/GRASP)
- Frontend компоненты и интеграция WebSocket
- Интеграция Telegram бота
- Стратегия тестирования и критические точки
- Будущее масштабирование и поддержка мульти-мессенджеров

#### [`guides/voice-ai/VOICE_AI_TESTING_STRATEGY.md`](guides/voice-ai/VOICE_AI_TESTING_STRATEGY.md)
Стратегия тестирования для функции голосового AI ассистента

**Ключевые Темы:**
- Unit тесты для сервисов (LLM, STT, Command Executor)
- Integration тесты для полного потока голосовых команд
- Стратегии создания моков для AI сервисов
- Тестирование производительности обработки голоса
- Граничные случаи и тесты обработки ошибок

#### [`guides/CENTRIFUGO_REALTIME_IMPLEMENTATION.md`](guides/CENTRIFUGO_REALTIME_IMPLEMENTATION.md)
**🆕 НОВОЕ 2025-11-21** - Пошаговый план реализации Real-time обновлений через Centrifugo

**Ключевые Темы:**
- Архитектура WebSocket соединений с Centrifugo v5
- Миграция с pusher на phpcent библиотеку
- CommandEventMapper с использованием ParsedCommand констант
- JWT аутентификация для WebSocket каналов
- Frontend интеграция с centrifuge-js
- Glassmorphism Toast компонент для уведомлений
- Статистика и мониторинг real-time событий
- Production конфигурация и безопасность
- 6 фаз реализации (10-14 часов)
- Полные примеры кода для всех компонентов

#### [`guides/performance/PERFORMANCE_OPTIMIZATION_PLAN.md`](guides/performance/PERFORMANCE_OPTIMIZATION_PLAN.md)
План оптимизации производительности Backend для 2M+ задач (Улучшено Opus 4.1)

**Ключевые Темы:**
- Критические проблемы N+1 запросов идентифицированы
- Проблемы ленивой загрузки DTO
- Стратегия индексирования базы данных (15+ составных индексов)
- Оптимизация PostgreSQL и connection pooling
- Кеширование результатов запросов с Doctrine
- 11-этапный план реализации (6-8 дней)
- Ожидаемое 100x улучшение производительности
- Стратегии оптимизации памяти

#### [`guides/performance/DOCTRINE_CACHING_SETUP.md`](guides/performance/DOCTRINE_CACHING_SETUP.md)
**📊 НОВОЕ** - Детальное руководство по настройке кеширования Doctrine (Dev + Prod)

**Ключевые Темы:**
- Понимание типов кеша Doctrine (Query, Metadata, Result)
- Конфигурации специфичные для окружения (dev vs prod)
- Настройка и оптимизация APCu для production
- Пошаговое руководство по реализации
- Тестирование и решение проблем с кешированием
- Ожидаемое 4-10x улучшение производительности
- Мониторинг кеша и статистика

---

## 🔑 Критические Области Знаний

### Для Backend Разработки
**Обязательно Прочитать:**
1. [`CODING_STANDARDS.md`](CODING_STANDARDS.md) - Принципы SOLID/GRASP
2. [`backend/ARCHITECTURE.md`](backend/ARCHITECTURE.md) - Слоистая архитектура

**Справочник:**
- [`backend/API_REFERENCE.md`](backend/API_REFERENCE.md) - API контракты
- [`backend/DATABASE.md`](backend/DATABASE.md) - Дизайн схемы
- [`backend/RECURRENCE_TASKS.md`](backend/RECURRENCE_TASKS.md) - Система повторяющихся задач

**Тестирование:**
- [`backend/TEST_COVERAGE.md`](backend/TEST_COVERAGE.md) - Текущий отчет о покрытии тестами
- [`backend/MISSING_TEST_COVERAGE_PLAN.md`](backend/MISSING_TEST_COVERAGE_PLAN.md) - 🎯 **План реализации написания тестов**

### Для Frontend Разработки
**Обязательно Прочитать:**
1. [`CODING_STANDARDS.md`](CODING_STANDARDS.md) - TypeScript/Vue конвенции
2. [`frontend/ARCHITECTURE.md`](frontend/ARCHITECTURE.md) - Паттерны компонентов
3. [`frontend/STATE_MANAGEMENT.md`](frontend/STATE_MANAGEMENT.md) - Pinia stores

**Справочник:**
- [`frontend/COMPONENTS.md`](frontend/COMPONENTS.md) - Библиотека компонентов
- [`frontend/API_INTEGRATION.md`](frontend/API_INTEGRATION.md) - API вызовы

### Для Решения Проблем
**Первая Остановка:**
- [`guides/TROUBLESHOOTING.md`](guides/TROUBLESHOOTING.md) - Все известные проблемы и исправления

**Если Проблема Сохраняется:**
- [`guides/DEVELOPMENT_WORKFLOW.md`](guides/DEVELOPMENT_WORKFLOW.md) - Проблемы с настройкой

---

## 📊 Статистика Проекта

```
Backend:
- Строк Кода: ~15,000
- Контроллеры: 4 (Auth, Task, Tag, Analytics)
- Сервисы: 10+ (TaskService, RecurrenceService, AnalyticsService, и т.д.)
- Сущности: 6 (User, Task, Tag, Media, RefreshToken, RecurrenceRule)
- Тесты: PHPUnit (Unit + Integration)

Frontend:
- Строк Кода: ~8,000
- Компоненты: 25+ (views, cards, modals, forms)
- Composables: 8 (useTaskCompletion, useAuth, useTagSuggestions, и т.д.)
- Stores: 3 (TaskStore, AuthStore, LoaderStore)
- Тесты: 115 (Vitest - 100% проходят)
```

---

## 🎓 Путь Обучения для Новых AI Ассистентов

### Фаза 1: Понимание (30 минут)
1. Прочитайте `PROJECT_OVERVIEW.md` - Что мы строим?
2. Прочитайте `TECH_STACK.md` - Какие технологии?
3. Просмотрите `CODING_STANDARDS.md` - Как мы пишем код?

### Фаза 2: Глубокое Погружение в Backend (45 минут)
1. Изучите `backend/ARCHITECTURE.md` - Как структурирован backend?
2. Справка `backend/API_REFERENCE.md` - Знайте все эндпоинты
3. Прочитайте `backend/DATABASE.md` - Схема базы данных и связи
4. Прочитайте `backend/RECURRENCE_TASKS.md` - Функция повторяющихся задач (опционально)

### Фаза 3: Глубокое Погружение во Frontend (45 минут)
1. Изучите `frontend/ARCHITECTURE.md` - Как структурирован frontend?
2. Прочитайте `frontend/STATE_MANAGEMENT.md` - Как работает состояние
3. Прочитайте `frontend/API_INTEGRATION.md` - Как frontend общается с backend

### Фаза 4: Практические Знания (30 минут)
1. Прочитайте `guides/DEVELOPMENT_WORKFLOW.md` - Как разрабатывать
2. Добавьте в закладки `guides/TROUBLESHOOTING.md` - Для случаев, когда что-то ломается

**Общая Инвестиция Времени:** ~2.5 часа для полного контекста

---

## 🚀 Быстрые Справочные Команды

### CI/CD Helper Scripts

```bash
# Проверка готовности GitHub Secrets
./scripts/check-github-secrets.sh ~/taskflow-production-secrets.txt

# Тестирование SSH подключения к VDS
./scripts/test-ssh-connection.sh 45.129.186.88 root

# Генерация production secrets
./scripts/generate-secrets.sh

# Health check production сервера
./scripts/health-check.sh

# Production deployment (вручную)
./scripts/deploy-production.sh
```

### Backend (Docker)

**ВАЖНО**: Конфигурация Docker в `docker-compose.yml` (корень) + `infrastructure/docker/*.yml`

```bash
# Запустить сервисы в development режиме (из корня проекта)
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml up -d

# Остановить сервисы
docker-compose down

# Полностью пересобрать проект
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml down -v  # Удаляет volumes (данные БД!)
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml build --no-cache
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml up -d
docker exec backend-php83 composer install
docker exec backend-php83 php bin/console doctrine:database:create
docker exec backend-php83 php bin/console doctrine:migrations:migrate --no-interaction

# Запустить Symfony команды
docker exec backend-php83 php bin/console <команда>

# Миграции базы данных
docker exec backend-php83 php bin/console make:migration
docker exec backend-php83 php bin/console doctrine:migrations:migrate

# Очистить кеш
docker exec backend-php83 php bin/console cache:clear

# Операции PostgreSQL
# ⚠️ Credentials из .env.docker (dev): sollent / task-manager
docker exec -it backend-psql16 psql -U sollent -d task-manager
docker exec backend-psql16 psql -U sollent -d task-manager -c "SELECT COUNT(*) FROM tasks;"

# Логи контейнеров
docker logs -f backend-php83
docker logs -f backend-nginx

# Здоровье контейнеров
docker ps  # Список работающих контейнеров
docker stats  # Использование ресурсов
```

### Frontend

**Расположение**: `apps/frontend/` директория

```bash
# Перейти во frontend
cd apps/frontend

# Установить зависимости
npm install

# Development сервер (запускается на http://localhost:3000)
npm run dev

# Проверка типов
npm run type-check

# Сборка для production
npm run build

# Запустить тесты
npm run test:run
```

---

## 📝 Конвенции Документации

### Именование Файлов
- Все файлы документации: `UPPERCASE_WITH_UNDERSCORES.md`
- Backend-специфичные: `backend/FILE_NAME.md`
- Frontend-специфичные: `frontend/FILE_NAME.md`
- Руководства: `guides/FILE_NAME.md`

### Структура Документа
Каждый документ следует этому паттерну:
1. **Заголовок** с emoji
2. **Краткое Резюме** (TL;DR)
3. **Оглавление** (для длинных доков)
4. **Основной Контент** с четкими заголовками
5. **Примеры** (фрагменты кода)
6. **Связанные Документы** (ссылки)

### Примеры Кода
- Всегда показывайте и ❌ ПЛОХИЕ и ✅ ХОРОШИЕ примеры
- Включайте пути к файлам для контекста
- Добавляйте комментарии объясняющие ПОЧЕМУ

---

## 🔗 Внешние Ресурсы

### Официальная Документация
- **Symfony**: https://symfony.com/doc/current/index.html
- **Vue.js 3**: https://vuejs.org/guide/introduction.html
- **TypeScript**: https://www.typescriptlang.org/docs/
- **PrimeVue**: https://primevue.org/
- **Pinia**: https://pinia.vuejs.org/

### Книги в Справочнике
- **Clean Architecture** - Robert C. Martin
- **Clean Code** - Robert C. Martin
- **Code Complete** - Steve McConnell
- **Design Patterns** - Gang of Four

---

## ⚠️ Важные Заметки для AI

### Всегда Помните
1. **Принципы SOLID не подлежат обсуждению** - Каждый класс следует им
2. **Никакой бизнес-логики в контроллерах** - Контроллеры - тонкие координаторы
3. **TypeScript strict mode** - Типы `any` не разрешены
4. **Оптимистичные обновления UI** - Никаких полных перезагрузок списков

### Перед Внесением Изменений
- [ ] Вы прочитали релевантную документацию?
- [ ] Вы понимаете применяемые принципы SOLID?
- [ ] Вы проверили `TROUBLESHOOTING.md` на похожие проблемы?
- [ ] Вы поддерживаете существующие паттерны кода?

### Когда Застряли
1. Проверьте `guides/TROUBLESHOOTING.md` сначала
2. Перечитайте релевантный документ по архитектуре
3. Просмотрите похожий существующий код
4. Попросите разъяснения, если действительно неоднозначно

---

## 📅 Последнее Обновление

**Версия:** 1.5.0
**Дата:** 2025-11-14
**Сопровождающий:** Claude Code AI
**Фаза Проекта:** Production-Ready + HTTPS/SSL Setup Complete

---

## 🎯 Следующие Шаги

После прочтения этого ИНДЕКСА:

**Для Понимания Проекта:**
→ Начните с [`PROJECT_OVERVIEW.md`](PROJECT_OVERVIEW.md)

**Для Написания Backend Кода:**
→ Прочитайте [`CODING_STANDARDS.md`](CODING_STANDARDS.md) затем [`backend/ARCHITECTURE.md`](backend/ARCHITECTURE.md)

**Для Написания Frontend Кода:**
→ Прочитайте [`CODING_STANDARDS.md`](CODING_STANDARDS.md) затем [`frontend/ARCHITECTURE.md`](frontend/ARCHITECTURE.md)

**Для Исправления Проблем:**
→ Переходите к [`guides/TROUBLESHOOTING.md`](guides/TROUBLESHOOTING.md)

---

*Эта документация живая и развивается вместе с проектом. Когда вы принимаете архитектурные решения, обновляйте релевантную документацию.*

---

## 🎯 Новые Документы - Backend Architecture (2025-11-14)

### [`BACKEND_ARCHITECTURE_MAP.md`](BACKEND_ARCHITECTURE_MAP.md)
**🏗️ ПОЛНАЯ карта backend архитектуры** - 9K+ строк детального анализа

**Содержит:**
- Полная структура `/src` (15 директорий, 17K LOC)
- Все 9 сущностей с полями и отношениями
- 8 API контроллеров с таблицами
- 11 Core Services с описанием функций
- 7 Database Repositories с примерами
- JWT + Google OAuth 2.0 поток аутентификации
- CORS конфигурация (regex домены, preflight кеширование)
- Production Doctrine конфигурация (APCu кеширование)
- Environment variables (Fail-Fast принцип)
- DTO структура (Request/Response)
- Слоистая архитектура (Presentation → Business → Data → DB)
- Request/Response flow example (создание задачи)
- 11 compound indexes для оптимизации
- Все эндпоинты (Authentication, Tasks, Tags, Analytics, Profile)

**Для кого**: Разработчикам, которым нужен **полный контекст** backend архитектуры

### [`BACKEND_QUICK_REFERENCE.md`](BACKEND_QUICK_REFERENCE.md)
**⚡ БЫСТРАЯ справка** - 2K строк для быстрого поиска информации

**Содержит:**
- 5 ключевых сущностей (1 таблица)
- 8 API контроллеров (структурированный список)
- 3 способа аутентификации (примеры)
- 11 Core Services (список + размер файлов)
- 7 Repositories (методы)
- Поток создания задачи (пошаговый)
- Security Architecture (диаграмма)
- Performance Tips (DO/DON'T)
- DTOs structure (Request/Response примеры)
- Enums (современный PHP 8.1+)
- Environment variables (production)
- Testing структура
- Common Issues таблица
- Key Files to Know
- One-Minute Answers (FAQ)
- Best Practices Checklist

**Для кого**: Разработчикам, которым нужна **быстрая справка** для дневной работы

---

