# 🐳 Docker Архитектура - Полное Объяснение

> **Версия**: 1.0
> **Дата**: 2025-11-13
> **Для**: Разработчиков, которые хотят понять как устроена Docker инфраструктура проекта

---

## 📋 Содержание

1. [Модульная Структура Docker Compose](#модульная-структура-docker-compose)
2. [Как Работает `make init`](#как-работает-make-init)
3. [Dev vs Prod Режимы](#dev-vs-prod-режимы)
4. [Fail-Fast Принцип](#fail-fast-принцип)
5. [Все Доступные Команды](#все-доступные-команды)
6. [Частые Вопросы](#частые-вопросы)

---

## 🏗️ Модульная Структура Docker Compose

### Философия

Вместо **одного монолитного** `docker-compose.yml`, мы используем **модульный подход**:

```
🔴 docker-compose.yml (корневой)
    ↓ include
    ├── 🟡 docker-compose.app.yml        (базовый конфиг - БЕЗ fallback)
    ├── 🟢 docker-compose.dev.yml        (dev overrides - С fallback)
    ├── 🟢 docker-compose.frontend-dev.yml (frontend dev - С fallback)
    ├── 🔵 docker-compose-prod.yml       (prod overrides - БЕЗ fallback)
    └── 🔵 docker-compose.frontend-prod.yml (frontend prod - БЕЗ fallback)
```

### Структура Файлов

```
test_sonnet45/
├── docker-compose.yml                          # 🔴 Корневой (только include)
├── .env.docker                                 # Dev переменные окружения
├── .env.docker.example                         # Шаблон для .env.docker
├── Makefile                                    # ⭐ Удобные команды!
│
├── apps/
│   ├── backend/
│   │   ├── Dockerfile                          # Backend PHP-FPM
│   │   └── .env                                # Symfony dev конфиг
│   └── frontend/
│       ├── Dockerfile.dev                      # Frontend dev (Vite)
│       ├── Dockerfile.prod                     # Frontend prod (Nginx)
│       └── nginx.conf                          # Nginx конфиг для SPA
│
└── infrastructure/docker/
    ├── docker-compose.app.yml                  # 🟡 Базовый (все сервисы)
    ├── docker-compose.dev.yml                  # 🟢 Dev backend
    ├── docker-compose.frontend-dev.yml         # 🟢 Dev frontend
    ├── docker-compose-prod.yml                 # 🔵 Prod backend
    ├── docker-compose.frontend-prod.yml        # 🔵 Prod frontend
    └── docker-compose.ai.yml                   # 🟣 AI сервисы (Voice AI)
```

---

## ⚙️ Как Работает `make init`

### 1️⃣ Что Происходит Когда Вы Запускаете `make init`

```bash
make init
```

**Выполняется следующая последовательность:**

```makefile
init: down build up migrate
  ↓      ↓     ↓    ↓     ↓
  1.    2.    3.   4.    5.
```

### Шаг за Шагом:

#### **1. `make down`** - Очистка

```bash
docker compose \
  -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.dev.yml \
  -f infrastructure/docker/docker-compose.frontend-dev.yml \
  down
```

**Что делает:**
- Останавливает все контейнеры
- Удаляет остановленные контейнеры
- Удаляет созданные сети

**Результат:**
```
✓ Все контейнеры остановлены и удалены
✓ Чистый старт гарантирован
```

---

#### **2. `make build`** - Сборка Образов

```bash
docker compose \
  -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.dev.yml \
  -f infrastructure/docker/docker-compose.frontend-dev.yml \
  build
```

**Что делает:**
- **Backend**: Собирает PHP-FPM образ (Symfony + Composer)
- **Frontend**: Собирает Node.js образ (Vite dev server)
- **PostgreSQL, Nginx, RabbitMQ**: Используют готовые образы (не собираются)

**Результат:**
```
✓ backend-php83:     ~800MB (PHP 8.3 + Composer + dependencies)
✓ frontend-dev:      ~456MB (Node.js 20 + npm dependencies)
✓ Total build time:  ~2-5 минут (первый раз)
```

---

#### **3. `make up`** - Запуск Сервисов

```bash
docker compose \
  -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.dev.yml \
  -f infrastructure/docker/docker-compose.frontend-dev.yml \
  up -d
```

**Что делает:**

**🟡 Загружает `docker-compose.app.yml` (базовый конфиг):**
- `psql16` - PostgreSQL 16
- `nginx` - Nginx для backend
- `php83-fpm` - PHP-FPM 8.3
- `rabbitmq` - RabbitMQ очередь
- `frontend` - Placeholder (будет переопределен)

**🟢 Накладывает `docker-compose.dev.yml` (dev overrides):**
- Добавляет `APP_DEBUG=true`
- Добавляет fallback для credentials (удобство dev)
- Монтирует volumes для hot-reload backend

**🟢 Накладывает `docker-compose.frontend-dev.yml` (frontend dev):**
- Переопределяет `frontend` сервис
- Запускает `Dockerfile.dev` (Vite dev server)
- Монтирует `apps/frontend:/app` (hot-reload)
- Монтирует `/app/node_modules` (anonymous volume!)
- Порт `3000` с fallback

**Результат:**
```
✓ 5 контейнеров запущено:
  ├─ backend-psql16      (PostgreSQL)  :15432
  ├─ backend-nginx       (Nginx)       :8089
  ├─ backend-php83       (PHP-FPM)     :9009
  ├─ backend-rabbitmq    (RabbitMQ)    :5672, :15672
  └─ frontend-dev        (Vite)        :3000

✓ Hot Module Replacement (HMR) работает!
```

---

#### **4. `make migrate`** - Миграции БД

```bash
docker exec backend-php83 \
  php bin/console doctrine:migrations:migrate --no-interaction
```

**Что делает:**
- Проверяет какие миграции не применены
- Применяет их по порядку
- Создает/обновляет таблицы в PostgreSQL

**Результат:**
```
✓ Database schema обновлена
✓ Все таблицы созданы (users, tasks, tags, recurrence_rules, ...)
```

---

#### **5. Финальный Вывод**

```
✅ Development environment ready!
   Backend:  http://localhost:8089
   Frontend: http://localhost:3000

📝 Useful commands:
   make logs          - View all logs
   make logs-backend  - View backend logs
   make logs-frontend - View frontend logs
   make console       - Enter backend container
   make down          - Stop all services
```

---

## 🔄 Dev vs Prod Режимы

### Development Mode

**Команда:**
```bash
make init
# или
make up
```

**Какие файлы загружаются:**
```
docker-compose.yml (include)
  ├─ docker-compose.app.yml        (базовый)
  ├─ docker-compose.dev.yml        (dev backend)
  └─ docker-compose.frontend-dev.yml (dev frontend)
```

**Что особенного:**
- ✅ **Fallback credentials** - можно не создавать `.env.docker` (удобно!)
- ✅ **Hot Reload** - изменения в коде видны мгновенно (backend + frontend)
- ✅ **Debug mode** - `APP_DEBUG=true`, подробные логи
- ✅ **Volume mounts** - код монтируется из хоста
- ✅ **Dev tools** - Xdebug, Symfony debug toolbar

**Контейнеры:**
```yaml
# Frontend (Dev)
frontend-dev:
  build: Dockerfile.dev              # Vite dev server
  volumes:
    - ../../apps/frontend:/app       # Hot reload!
    - /app/node_modules              # Anonymous volume
  ports:
    - "3000:3000"
  command: npm run dev -- --host 0.0.0.0
```

---

### Production Mode

**Команда:**
```bash
make prod-up
```

**Какие файлы загружаются:**
```
docker-compose.yml (include)
  ├─ docker-compose.app.yml              (базовый)
  ├─ docker-compose-prod.yml             (prod backend)
  └─ docker-compose.frontend-prod.yml    (prod frontend)
```

**Что особенного:**
- ⚠️ **БЕЗ fallback** - `.env.docker.prod` ОБЯЗАТЕЛЕН (Fail-Fast!)
- ⚠️ **Immutable** - код внутри образа, volumes НЕ монтируются
- ⚠️ **Optimized** - multi-stage build, минификация, gzip
- ⚠️ **Production ready** - Nginx, SSL-ready, health checks

**Контейнеры:**
```yaml
# Frontend (Prod)
frontend-prod:
  build: Dockerfile.prod             # Multi-stage build!
  # NO volumes - immutable!
  ports:
    - "${FRONTEND_PROD_PORT}:80"     # БЕЗ fallback!
  restart: unless-stopped

# Multi-stage build:
# Stage 1: Node.js 20 → npm ci → npm run build (discarded)
# Stage 2: Nginx + dist/ only (~57MB final image!)
```

**Размеры образов:**
```
Dev:   456MB (Node.js + все зависимости)
Prod:   57MB (Nginx + минифицированный dist/) - 8x меньше!
```

---

## 🚨 Fail-Fast Принцип

### Что Это?

**Fail-Fast** = Приложение **немедленно падает с ошибкой**, если критичные переменные не заданы.

### Зачем Это Нужно?

**❌ БЕЗ Fail-Fast (ОПАСНО!):**
```yaml
environment:
  POSTGRES_PASSWORD: ${POSTGRES_PASSWORD:-password}
```

**Проблема:**
- Администратор забывает задать `POSTGRES_PASSWORD`
- Приложение **молча** запускается с паролем `password`
- **CRITICAL SECURITY VULNERABILITY!** 🔓

**✅ С Fail-Fast (ПРАВИЛЬНО!):**
```yaml
environment:
  POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}
```

**Результат:**
- Администратор забывает задать `POSTGRES_PASSWORD`
- Docker Compose **падает с ошибкой** при запуске
- Администратор **видит проблему и исправляет** перед запуском ✅

### Где Применяется?

**🟡 Базовый конфиг (`docker-compose.app.yml`) - БЕЗ fallback:**
```yaml
services:
  psql16:
    environment:
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}  # Fail-Fast!
```

**🟢 Dev конфиг (`docker-compose.dev.yml`) - С fallback:**
```yaml
services:
  psql16:
    environment:
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD:-password}  # Удобство dev
```

**🔵 Prod конфиг (`docker-compose-prod.yml`) - БЕЗ fallback:**
```yaml
services:
  psql16:
    environment:
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}  # Fail-Fast!
```

**Итог:**
- **Dev**: Fallback разрешены (удобство)
- **Prod**: Fallback запрещены (безопасность)

---

## 📋 Все Доступные Команды

### 🏗️ Development

| Команда | Описание | Что Делает |
|---------|----------|------------|
| `make init` | ⭐ **Главная команда** | `down` → `build` → `up` → `migrate` |
| `make build` | Собрать все образы | Backend + Frontend dev образы |
| `make up` | Запустить все сервисы | Backend + Frontend в dev режиме |
| `make down` | Остановить и удалить | Останавливает контейнеры + удаляет |
| `make restart` | Перезапустить | `down` → `up` |
| `make stop` | Только остановить | Контейнеры остаются (можно `up`) |
| `make remove` | Полная очистка | Удаляет все (контейнеры + образы + volumes) |

### 🔍 Логи и Мониторинг

| Команда | Описание |
|---------|----------|
| `make logs` | Все логи (follow mode) |
| `make logs-backend` | Логи PHP-FPM + Nginx |
| `make logs-frontend` | Логи Vite dev server |
| `make logs-db` | Логи PostgreSQL |
| `make status` | Статус всех контейнеров |

### 🐚 Доступ к Контейнерам

| Команда | Описание |
|---------|----------|
| `make console` | Bash в backend PHP контейнере |
| `make console-frontend` | Shell во frontend контейнере |
| `make psql` | Bash в PostgreSQL контейнере |
| `make db-cli` | PostgreSQL CLI (`psql`) |

### 🗄️ База Данных

| Команда | Описание |
|---------|----------|
| `make migrate` | Применить миграции |
| `make migrate-create` | Создать новую миграцию |
| `make db-reset` | ⚠️ Пересоздать БД (удаляет данные!) |

### 🎨 Frontend (Локальный)

| Команда | Описание |
|---------|----------|
| `make frontend-install` | `npm install` локально |
| `make frontend-dev-local` | `npm run dev` локально (БЕЗ Docker) |
| `make frontend-build` | `npm run build` |
| `make kill-frontend` | Убить процесс на порту 3000 |

### 🏭 Production

| Команда | Описание |
|---------|----------|
| `make prod-build` | Собрать production образы |
| `make prod-up` | Запустить production окружение |
| `make prod-down` | Остановить production |
| `make prod-logs` | Логи production |

### ✅ Качество Кода

| Команда | Описание |
|---------|----------|
| `make cs-fixer-check` | Проверить стиль кода (dry-run) |
| `make cs-fixer-fix` | Исправить стиль кода |
| `make phpstan` | Статический анализ (PHPStan) |
| `make quality-check` | cs-fixer + phpstan (проверка) |
| `make quality-fix` | cs-fixer fix + phpstan |

### 🧪 Тестирование

| Команда | Описание |
|---------|----------|
| `make test` | Все тесты (PHPUnit) |
| `make test-unit` | Только unit тесты |
| `make test-integration` | Только integration тесты |
| `make test-coverage` | Генерация coverage отчета |

---

## ❓ Частые Вопросы

### 1. Почему так много docker-compose файлов?

**Ответ:** Модульность и переиспользуемость!

**Альтернатива (монолит):**
```yaml
# docker-compose.yml (1000+ строк)
services:
  backend-dev: ...
  backend-prod: ...
  frontend-dev: ...
  frontend-prod: ...
  # Копипаста и дублирование!
```

**Наш подход (модули):**
```yaml
# docker-compose.app.yml (базовый - 200 строк)
# docker-compose.dev.yml (dev overrides - 100 строк)
# docker-compose-prod.yml (prod overrides - 100 строк)
# Итого: 400 строк, 0 дублирования!
```

---

### 2. Нужно ли создавать `.env.docker` для dev?

**Ответ:** НЕТ! В dev режиме fallback работают.

**Dev (с fallback):**
```bash
# Без .env.docker - все равно работает!
make init

# Использует fallback значения:
# POSTGRES_PASSWORD=password
# FRONTEND_DEV_PORT=3000
```

**Prod (БЕЗ fallback):**
```bash
# БЕЗ .env.docker.prod - ОШИБКА!
make prod-up
# ERROR: POSTGRES_PASSWORD is not set!

# Нужно создать .env.docker.prod:
cp .env.docker.example .env.docker.prod
# Отредактировать и задать ПРАВИЛЬНЫЕ пароли!
```

---

### 3. Как работает HMR (Hot Module Replacement)?

**Backend (Symfony):**
```yaml
volumes:
  - ../../apps/backend:/var/www  # Код монтируется

# PHP не компилируется, поэтому HMR работает "из коробки"
# Изменения в .php файлах видны сразу!
```

**Frontend (Vite):**
```yaml
volumes:
  - ../../apps/frontend:/app      # Код монтируется
  - /app/node_modules             # Anonymous volume!

# Vite отслеживает изменения и перезагружает браузер
# Anonymous volume предотвращает перезапись node_modules!
```

**Важно:** Anonymous volume `/app/node_modules`!

Без него:
```
Host node_modules (macOS)  →  Контейнер (Linux)  →  💥 Несовместимость!
```

С ним:
```
Host code → Контейнер code ✅
Контейнер node_modules (Linux) → Не перезаписываются ✅
```

---

### 4. Почему prod образ такой маленький (57MB)?

**Ответ:** Multi-stage build!

**Dockerfile.prod:**
```dockerfile
# Stage 1: Builder (DISCARDED!)
FROM node:20-alpine AS builder
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci                        # Все зависимости
COPY . .
RUN npm run build                 # Сборка dist/

# Stage 2: Production (FINAL IMAGE!)
FROM nginx:alpine                 # Только nginx!
COPY --from=builder /app/dist /usr/share/nginx/html
# Копируем ТОЛЬКО dist/ из builder stage
# Все остальное (node_modules, src/, etc.) выбрасывается!
```

**Результат:**
```
Stage 1 (builder):  ~1GB  (discarded after build)
Stage 2 (final):     57MB (nginx + dist/)
```

---

### 5. Как запустить только backend БЕЗ frontend?

```bash
# Вариант 1: Docker Compose напрямую
docker compose \
  -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.dev.yml \
  up -d

# Вариант 2: Можно добавить команду в Makefile
# Makefile:
backend-only:
	docker compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml up -d
```

---

### 6. Как переключиться с Docker frontend на локальный?

```bash
# Остановить Docker frontend
docker stop frontend-dev

# Запустить локальный frontend
make frontend-dev-local
# или
cd apps/frontend && npm run dev

# Backend продолжает работать в Docker!
```

---

### 7. Где хранятся данные PostgreSQL?

**Ответ:** В Docker volume `psql-data`.

```bash
# Посмотреть volumes
docker volume ls
# DRIVER    VOLUME NAME
# local     test_sonnet45_psql-data

# Удалить volume (⚠️ УДАЛИТ ВСЕ ДАННЫЕ!)
docker volume rm test_sonnet45_psql-data

# После make init - БД будет пустой!
```

---

### 8. Как обновить зависимости backend?

```bash
# Войти в контейнер
make console

# Внутри контейнера:
composer update
composer install

# Или снаружи:
docker exec backend-php83 composer update
```

---

### 9. Как обновить зависимости frontend?

**В Docker:**
```bash
# Войти в контейнер
make console-frontend

# Внутри:
npm update
npm install
```

**Локально:**
```bash
cd apps/frontend
npm update
npm install
```

---

### 10. Что делать если порты заняты?

**Ответ:** Изменить в `.env.docker`!

```bash
# Отредактировать .env.docker
NGINX_PORT=8090              # Было 8089
FRONTEND_DEV_PORT=3001       # Было 3000
POSTGRES_PORT=15433          # Было 15432

# Перезапустить
make restart
```

**Или убить процессы:**
```bash
make kill-port PORT=3000
make kill-port PORT=8089
```

---

## 🎯 Итоговая Шпаргалка

### Первый Запуск Проекта

```bash
# 1. Клонировать репозиторий
git clone <repo>
cd test_sonnet45

# 2. (Опционально) Создать .env.docker
cp .env.docker.example .env.docker
# Отредактировать если нужно изменить порты/пароли

# 3. Запустить все (backend + frontend)
make init

# 4. Открыть браузер
open http://localhost:3000
```

### Ежедневная Разработка

```bash
# Запустить
make up

# Посмотреть логи
make logs

# Остановить
make down
```

### Проблемы?

```bash
# Полная перезагрузка
make down
make build
make up

# Логи конкретного сервиса
make logs-backend
make logs-frontend

# Войти в контейнер
make console
make console-frontend
```

---

## 📚 Связанные Документы

- **[DEVELOPMENT_WORKFLOW.md](DEVELOPMENT_WORKFLOW.md)** - Ежедневная разработка
- **[ENVIRONMENT_CONFIGURATION.md](ENVIRONMENT_CONFIGURATION.md)** - Environment файлы
- **[TROUBLESHOOTING.md](TROUBLESHOOTING.md)** - Решение проблем с Docker
- **[INDEX.md](../INDEX.md)** - Главная карта документации

---

**Последнее обновление**: 2025-11-13
**Версия**: 1.0
**Автор**: Claude Code AI
