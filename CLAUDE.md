# 🤖 Claude Code Краткий Справочник

> **Для AI-ассистента Claude Code**: Этот файл предоставляет мгновенный контекст о проекте и навигацию по полной документации.

---

## 📍 Начните Отсюда

**Полная карта документации**: [`docs/INDEX.md`](docs/INDEX.md)

👆 **Всегда начинайте отсюда** - Содержит полную навигацию, путь обучения и быстрые справочные команды.

---

## ⚡ Основной Контекст

### Что это за проект?

**Система Управления Задачами** - Full-stack приложение с продвинутыми функциями:
- **Backend**: Symfony 7.1 + PHP 8.3 + PostgreSQL 16
- **Frontend**: Vue.js 3.4 + TypeScript 5.4 + Pinia + PrimeVue
- **Инфраструктура**: Docker (все backend сервисы)

### Ключевые Возможности
- ✅ Задачи с подзадачами (неограниченная вложенность)
- ✅ Теги, приоритеты, статусы, сроки выполнения
- ✅ Продвинутая аналитика
- ✅ Интеграция с календарем
- ✅ JWT + Google OAuth аутентификация
- ✅ Поддержка i18n (EN/RU/UK)

---

## 🗺️ Карта Документации

### 🔥 Обязательно Прочитайте Первым (Перед Изменениями Кода)

1. **[`docs/CODING_STANDARDS.md`](docs/CODING_STANDARDS.md)** ⚠️ **КРИТИЧЕСКИ ВАЖНО**
   - SOLID, GRASP, GoF паттерны проектирования
   - Современные стандарты PHP 8.3 (readonly, enums, match)
   - Правила TypeScript strict mode
   - ✅ ХОРОШИЕ / ❌ ПЛОХИЕ примеры кода

### 📚 Архитектура и Настройка

| Тема | Документ | Ключевая Информация |
|------|----------|---------------------|
| **Обзор Проекта** | [`docs/PROJECT_OVERVIEW.md`](docs/PROJECT_OVERVIEW.md) | Функции, рабочие процессы, возможности |
| **Технологический Стек** | [`docs/TECH_STACK.md`](docs/TECH_STACK.md) | Все технологии с версиями |
| **Архитектура Backend** | [`docs/backend/ARCHITECTURE.md`](docs/backend/ARCHITECTURE.md) | Слоистая архитектура, примеры SOLID |
| **Схема БД** | [`docs/backend/DATABASE.md`](docs/backend/DATABASE.md) | Сущности, связи, миграции |
| **Справочник API** | [`docs/backend/API_REFERENCE.md`](docs/backend/API_REFERENCE.md) | Все 37 эндпоинтов с примерами |
| **Аутентификация** | [`docs/backend/AUTHENTICATION.md`](docs/backend/AUTHENTICATION.md) | JWT + Google OAuth потоки |
| **Архитектура Frontend** | [`docs/frontend/ARCHITECTURE.md`](docs/frontend/ARCHITECTURE.md) | Vue Composition API, Smart/Dumb компоненты |
| **Управление Состоянием** | [`docs/frontend/STATE_MANAGEMENT.md`](docs/frontend/STATE_MANAGEMENT.md) | Pinia stores (task, auth, tag, analytics) |
| **Интеграция API** | [`docs/frontend/API_INTEGRATION.md`](docs/frontend/API_INTEGRATION.md) | Axios, перехватчики, обработка ошибок |

### 🛠️ Руководства по Разработке

| Тема | Документ | Ключевая Информация |
|------|----------|---------------------|
| **Ежедневный Рабочий Процесс** | [`docs/guides/DEVELOPMENT_WORKFLOW.md`](docs/guides/DEVELOPMENT_WORKFLOW.md) | Docker команды, миграции, PostgreSQL |
| **Качество Кода** | [`docs/guides/CODE_QUALITY.md`](docs/guides/CODE_QUALITY.md) | PHP-CS-Fixer, PHPStan, Git hooks |
| **Тестирование** | [`docs/guides/testing/TESTING.md`](docs/guides/testing/TESTING.md) | PHPUnit, Vitest, организация тестов |
| **Решение Проблем** | [`docs/guides/TROUBLESHOOTING.md`](docs/guides/TROUBLESHOOTING.md) | Все решенные проблемы и решения |

---

## 🚀 Быстрые Команды

### Docker (Backend)

```bash
# Запустить все сервисы (из корня проекта)
docker-compose up -d

# Остановить сервисы
docker-compose down

# Просмотр логов
docker logs -f backend-php83

# Команды Symfony
docker exec backend-php83 php bin/console <команда>

# Миграции
docker exec backend-php83 php bin/console doctrine:migrations:migrate

# Проверки качества кода
make cs-fixer-fix              # Исправить стиль кода
make phpstan                   # Статический анализ
make quality-check             # Запустить обе проверки
```

**ВАЖНО**: Главный Docker конфиг - `docker-compose.yml` в корне (включает infrastructure/docker/*.yml)

### Frontend

```bash
# Запустить dev сервер
cd apps/frontend && npm run dev

# Сборка для production
npm run build

# Запустить тесты
npm run test:run
```

---

## 🔍 Поиск Информации

### Когда нужно узнать...

**"Как писать код?"**
→ Читайте [`docs/CODING_STANDARDS.md`](docs/CODING_STANDARDS.md)

**"Какие эндпоинты существуют?"**
→ Читайте [`docs/backend/API_REFERENCE.md`](docs/backend/API_REFERENCE.md)

**"Как запустить проект?"**
→ Читайте [`docs/guides/DEVELOPMENT_WORKFLOW.md`](docs/guides/DEVELOPMENT_WORKFLOW.md)

**"Как исправить [конкретную ошибку]?"**
→ Читайте [`docs/guides/TROUBLESHOOTING.md`](docs/guides/TROUBLESHOOTING.md)

**"Какая структура базы данных?"**
→ Читайте [`docs/backend/DATABASE.md`](docs/backend/DATABASE.md)

**"Как тестировать?"**
→ Читайте [`docs/guides/testing/TESTING.md`](docs/guides/testing/TESTING.md)

**"Нужен полный контекст?"**
→ Начните с [`docs/INDEX.md`](docs/INDEX.md) (2.5 часа чтения)

---

## ⚠️ Критические Правила (Всегда Следуйте!)

### Стандарты Кода
✅ **ДЕЛАЙТЕ**: Используйте type hints везде (PHP & TypeScript)
✅ **ДЕЛАЙТЕ**: Следуйте принципам SOLID (см. CODING_STANDARDS.md)
✅ **ДЕЛАЙТЕ**: Используйте readonly свойства (PHP 8.2+)
✅ **ДЕЛАЙТЕ**: Используйте enums вместо констант
✅ **ДЕЛАЙТЕ**: Используйте match выражения вместо switch
❌ **НЕ ДЕЛАЙТЕ**: Используйте тип `any` в TypeScript
❌ **НЕ ДЕЛАЙТЕ**: Пишите толстые контроллеры (используйте сервисы!)
❌ **НЕ ДЕЛАЙТЕ**: Смешивайте бизнес-логику с HTTP слоем

### Docker
✅ **ДЕЛАЙТЕ**: Используйте `docker-compose.yml` в корне (главный конфиг, включает infrastructure/docker/*.yml)
✅ **ДЕЛАЙТЕ**: Запускайте backend команды через `docker exec backend-php83`
✅ **ДЕЛАЙТЕ**: Проверяйте логи: `docker logs -f backend-php83`
❌ **НЕ ДЕЛАЙТЕ**: Запускайте PHP команды напрямую на хосте

---

## 📊 Структура Проекта

```
test_sonnet45/
├── CLAUDE.md                       # ← Вы здесь!
├── docker-compose.yml              # Главный Docker compose (includes)
├── Makefile                        # Общие команды
├── docs/                           # ← Полная документация
│   ├── INDEX.md                   # ← Начните отсюда для полной навигации
│   ├── CODING_STANDARDS.md        # ⚠️ КРИТИЧЕСКИ ВАЖНО
│   ├── PROJECT_OVERVIEW.md
│   ├── TECH_STACK.md
│   ├── backend/
│   │   ├── API_REFERENCE.md
│   │   ├── ARCHITECTURE.md
│   │   ├── DATABASE.md
│   │   └── AUTHENTICATION.md
│   ├── frontend/
│   │   ├── ARCHITECTURE.md
│   │   ├── STATE_MANAGEMENT.md
│   │   └── API_INTEGRATION.md
│   └── guides/
│       ├── DEVELOPMENT_WORKFLOW.md
│       ├── TESTING.md
│       └── TROUBLESHOOTING.md
├── apps/
│   ├── backend/                    # Symfony 7.1
│   │   ├── src/
│   │   │   ├── Controller/
│   │   │   ├── Service/
│   │   │   ├── Repository/
│   │   │   ├── Entity/
│   │   │   └── Dto/
│   │   └── config/
│   └── frontend/                   # Vue.js 3.4
│       ├── src/
│       │   ├── components/
│       │   ├── views/
│       │   ├── stores/
│       │   └── services/
│       └── package.json
├── infrastructure/
│   ├── docker/
│   │   ├── docker-compose.app.yml
│   │   ├── docker-compose.ai.yml   # AI сервисы (заглушка)
│   │   └── docker-compose.dev.yml
│   └── ai-services/                # AI инфраструктура (заглушка)
└── scripts/                        # Вспомогательные скрипты
```

---

## 🎯 Рабочий Процесс для Claude Code

### Когда начинаете новую задачу:

1. **Прочитайте этот файл** (CLAUDE.md) для быстрого контекста
2. **Перейдите к релевантной доке** используя ссылки выше
3. **Следуйте стандартам кодирования** (CODING_STANDARDS.md)
4. **Пишите код** следуя паттернам из документации
5. **Тестируйте тщательно** (TESTING.md)

### Перед внесением изменений:

- ✅ Понимайте архитектуру (ARCHITECTURE.md)
- ✅ Проверяйте существующие паттерны в кодовой базе
- ✅ Следуйте принципам SOLID
- ✅ Обновляйте тесты при необходимости

### Если застряли или столкнулись с ошибками:

1. Проверьте [`docs/guides/TROUBLESHOOTING.md`](docs/guides/TROUBLESHOOTING.md)
2. Просмотрите соответствующий раздел документации
3. Проверьте Docker логи: `docker logs -f backend-php83`
4. Попросите разъяснение с конкретным контекстом

---

## 📞 Точки Доступа

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8089/api
- **PostgreSQL**: localhost:15432 (user/password/backend-app)
- **RabbitMQ**: http://localhost:15672 (user/password)

---

## 💡 Профессиональные Советы

1. **Всегда читайте CODING_STANDARDS.md** перед написанием кода
2. **Все Docker команды** запускаются из корня проекта используя `docker-compose.yml`
3. **Состояние Frontend** управляется через Pinia stores (никакого Vuex!)
4. **Слои Backend**: Controller → Service → Repository → Entity
5. **TypeScript strict mode** - типы `any` не разрешены!
6. **Полная пересборка**: См. DEVELOPMENT_WORKFLOW.md "Complete Project Rebuild"

---

## 📚 Путь Обучения (Новый AI Ассистент)

Если вы новый экземпляр Claude Code или нужен полный контекст:

**Фаза 1 - Основное (30 мин)**
1. Этот файл (CLAUDE.md) - Быстрый справочник
2. docs/INDEX.md - Карта навигации
3. docs/PROJECT_OVERVIEW.md - Что мы строим

**Фаза 2 - Стандарты Кода (45 мин)**
4. docs/CODING_STANDARDS.md ⚠️ **ОБЯЗАТЕЛЬНО К ПРОЧТЕНИЮ**

**Фаза 3 - Основная Архитектура (30 мин)**
5. docs/backend/ARCHITECTURE.md
6. docs/frontend/ARCHITECTURE.md

**Фаза 4 - Разработка (30 мин)**
7. docs/guides/DEVELOPMENT_WORKFLOW.md
8. docs/guides/TROUBLESHOOTING.md

**Всего**: ~2 часа для полного контекста

---

**Последнее Обновление**: 2025-01-05

**Версия Документации**: 1.0

**Для вопросов или разъяснений**: Обратитесь к [`docs/INDEX.md`](docs/INDEX.md) для детальной навигации

---

## 📋 Важные Инструкции для AI

- Когда реализуешь довольно глобальный и важный для проекта функционал - всегда обновляй полностью всю документацию в @docs/ а также по необходимости @CLAUDE.md
- Когда сталкиваешься с трудностями как пересобрать backend или frontend чтобы запустить тестирование (не важно - через bash скрипты или через mcp браузер) - всегда смотри в доку @docs/INDEX.md и оттуда в Development Workflow
- Делай коммит после каждого выполненного тобой промта - с понятным заголовком и супер минимальным описанием! И ГЛАВНОЕ ИМЕЙ ВВИДУ - ДЕЛАЙ КОММИТЫ ТОЛЬКО ТЕХ ИЗМЕНЕНИЙ КОТОРЫЕ СДЕЛАЛ ИМЕННО ТЫ, потому что параллельно с тобой могу работать я или другая нейронка - например курсор, и все происходит в одной ветке!
- Всегда когда пишешь какую-то документацию пиши ее в нужной директории внутри @docs/ там уже решай куда ложить в корень @docs/ или в @docs/frontend/ @docs/backend/ @docs/guides/, всегда обновляй @docs/INDEX.md и обновляй в нем нужную инфу и навигацию по документации
- Всегда смотри в доку @docs/* и предварительно изучай все файлы md и в подпапках все доки в md формате - чтобы обновлять контекст каждый раз (особенно когда я очищаю чат через /clear)
- Не забывай делать коммит после любых изменениях которые ты вносишь и когда ты закончил работу (по моему промту) - сразу делай коммит! чтобы я потом в случае чего мог откатить его
- **Пиши всю документацию (АБСОЛЮТНО ВСЮ, даже когда нужно создать маленькую доку-план) на РУССКОМ ЯЗЫКЕ! ТОЛЬКО НА РУССКОМ ЯЗЫКЕ!**
