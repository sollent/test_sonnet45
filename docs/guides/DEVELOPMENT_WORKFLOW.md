# 🛠 Процесс разработки - Руководство для ежедневной работы

> **Кратко**: Docker для бэкенда (Symfony + PostgreSQL) и фронтенда (Vue + Vite) через `docker-compose.yml` в корне. Frontend можно запускать локально (npm) или в Docker. Миграции БД через Doctrine. Git workflow с feature-ветками.

---

## 📋 Структура проекта

```
test_sonnet45/
├── docker-compose.yml              # Главный Docker Compose (подключает конфиги инфраструктуры)
├── Makefile                        # Общие команды
├── apps/
│   ├── backend/                    # Symfony приложение
│   │   ├── src/                    # PHP исходный код
│   │   ├── config/                 # Конфигурационные файлы
│   │   └── ...
│   └── frontend/                   # Vue.js приложение
│       ├── src/                    # TypeScript исходный код
│       └── ...
├── infrastructure/
│   ├── docker/                              # Docker конфигурация
│   │   ├── docker-compose.app.yml           # Базовые сервисы (backend + frontend placeholder)
│   │   ├── docker-compose.dev.yml           # Backend dev переопределения
│   │   ├── docker-compose-prod.yml          # Backend prod переопределения
│   │   ├── docker-compose.frontend-dev.yml  # Frontend dev (Vite dev server) 🆕
│   │   ├── docker-compose.frontend-prod.yml # Frontend prod (Nginx + статика) 🆕
│   │   ├── docker-compose.ai.yml            # AI сервисы (заглушка)
│   │   ├── dev/
│   │   │   ├── nginx/                       # Nginx конфигурация для backend API
│   │   │   └── php/                         # PHP-FPM конфигурация
│   │   └── cron/                            # Cron задачи
│   └── ai-services/                         # AI инфраструктура (заглушка)
└── scripts/                                 # Утилитные скрипты
```

---

## Первоначальная настройка

### 1. Клонирование репозитория

```bash
git clone <repository-url>
cd test_sonnet45
```

### 2. Настройка бэкенда (Docker)

**ВАЖНО**: Docker конфигурация находится в `docker-compose.yml` в корне проекта

```bash
cd apps/backend

# Копируем файл окружения (опционально - .env уже содержит dev конфигурацию)
# cp .env .env.local

# Настраиваем .env.local (если нужно переопределить)
# ⚠️ Credentials берутся из .env.docker через Docker окружение!
# DATABASE_URL использует переменные: ${POSTGRES_USER}, ${POSTGRES_PASSWORD}, ${POSTGRES_DB}
# DATABASE_URL="postgresql://${POSTGRES_USER:-user}:${POSTGRES_PASSWORD:-password}@psql16:5432/${POSTGRES_DB:-backend-app}?serverVersion=16&charset=utf8"

# Настройте другие переменные при необходимости:
# JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
# JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
# GOOGLE_CLIENT_ID=your-google-client-id
# GOOGLE_CLIENT_SECRET=your-google-client-secret

# Генерируем JWT ключи
mkdir -p config/jwt
openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

# Запускаем Docker сервисы (из корня проекта)
cd ../..
# Development режим - явно указываем dev конфигурацию
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml up -d

# Устанавливаем зависимости
docker exec backend-php83 composer install

# Выполняем миграции
docker exec backend-php83 php bin/console doctrine:migrations:migrate

# (Опционально) Загружаем фикстуры
docker exec backend-php83 php bin/console doctrine:fixtures:load
```

### 3. Настройка фронтенда

**Вариант A: Docker (рекомендуется)** 🐳

```bash
# Создаем .env.docker файл (опционально - fallback значения работают)
# Frontend уже настроен и запускается вместе с backend через docker-compose
# См. раздел "Запуск сервисов" ниже

# Проверка что frontend сервис добавлен в docker-compose:
grep -A 5 "frontend:" infrastructure/docker/docker-compose.frontend-dev.yml
```

**Вариант B: Локально (без Docker)**

```bash
cd apps/frontend

# Устанавливаем зависимости
npm install

# Копируем файл окружения (при необходимости)
cp .env.example .env

# Настраиваем .env
VITE_API_BASE_URL=http://localhost:8089
VITE_GOOGLE_CLIENT_ID=your-google-client-id

# Запускаем dev сервер
npm run dev
```

**Преимущества Docker варианта:**
- ✅ Не нужен Node.js на хост-машине
- ✅ Одинаковое окружение для всех разработчиков
- ✅ Запуск всего стека одной командой
- ✅ Hot Module Replacement (HMR) работает через Docker!

### 4. Доступ к приложению

- **Фронтенд (Docker):** http://localhost:3000 (Vite dev server в контейнере)
- **Фронтенд (локально):** http://localhost:3000 (Vite dev server, если запущен через npm)
- **Backend API:** http://localhost:8089/api (Nginx)
- **PostgreSQL:** localhost:15432 (credentials из `.env.docker`: `${POSTGRES_USER}/${POSTGRES_PASSWORD}`)
- **RabbitMQ Management:** http://localhost:15672 (credentials из `.env.docker`: `${RABBITMQ_USER}/${RABBITMQ_PASSWORD}`)

### 5. Установка Git хуков (рекомендуется)

```bash
# Устанавливаем pre-commit хуки для проверки качества кода
bash scripts/install-git-hooks.sh
```

Это устанавливает Git хуки, которые автоматически запускают:
- **PHP-CS-Fixer** - Проверка стиля кода (PSR-12)
- **PHPStan** - Статический анализ (уровень 5)

Хуки выполняются перед каждым коммитом и блокируют коммиты с проблемами.

---

## Ежедневная разработка

### Запуск сервисов

#### Вариант A: Полный стек в Docker (рекомендуется) 🐳

```bash
# Запуск Backend + Frontend в development режиме (из корня проекта)
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.dev.yml \
  -f infrastructure/docker/docker-compose.frontend-dev.yml up -d

# Проверка статуса контейнеров
docker ps

# Просмотр логов
docker logs -f frontend-dev   # Frontend логи
docker logs -f backend-php83  # Backend логи
```

**Результат:**
- ✅ Backend API: http://localhost:8089/api
- ✅ Frontend: http://localhost:3000 (Hot Reload работает!)
- ✅ Изменения в `apps/frontend/src/` видны мгновенно (volume mount)

#### Вариант B: Backend в Docker, Frontend локально

```bash
# Запуск только бэкенда (из корня проекта)
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml up -d

# Запуск фронтенда локально
cd apps/frontend
npm run dev
```

#### Вариант C: Только Frontend в Docker (Backend уже запущен)

```bash
# Если backend уже работает, запустить только frontend
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.dev.yml \
  -f infrastructure/docker/docker-compose.frontend-dev.yml up -d frontend
```

### Остановка сервисов

```bash
# Остановка всех сервисов (из корня проекта)
docker-compose down

# Остановка только frontend (если backend нужен)
docker stop frontend-dev
docker rm frontend-dev

# Остановка фронтенда (локальный npm)
Ctrl+C в терминале (где запущен npm run dev)
```

### Пересборка Docker контейнеров

#### Backend контейнеры

```bash
# Пересборка всех backend контейнеров (при изменении Dockerfile)
# ⚠️ Из корня проекта
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml down
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml build --no-cache
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml up -d

# Пересборка конкретного сервиса
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml build --no-cache php83-fpm
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml up -d php83-fpm
```

#### Frontend контейнеры 🆕

```bash
# Пересборка frontend dev образа (при изменении Dockerfile.dev или package.json)
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.dev.yml \
  -f infrastructure/docker/docker-compose.frontend-dev.yml build --no-cache frontend
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.dev.yml \
  -f infrastructure/docker/docker-compose.frontend-dev.yml up -d frontend

# Пересборка frontend production образа (для тестирования)
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml build --no-cache frontend
```

**Когда нужна пересборка frontend:**
- ✅ Изменился `package.json` (добавлены/удалены зависимости)
- ✅ Изменился `Dockerfile.dev` или `Dockerfile.prod`
- ✅ Изменился `nginx.conf` (только для prod)
- ❌ НЕ нужна при изменении кода в `src/` (volume mount)

### Просмотр логов Docker

```bash
# Все сервисы (из корня проекта)
docker-compose logs -f

# Конкретный сервис - Backend
docker-compose logs -f backend-php83
docker-compose logs -f backend-nginx
docker-compose logs -f backend-psql16

# Конкретный сервис - Frontend 🆕
docker-compose logs -f frontend-dev  # Dev режим
docker-compose logs -f frontend-prod # Production режим
```

---

## Операции с базой данных

### Создание миграции

```bash
# Автогенерация миграции на основе изменений в сущностях
docker exec backend-php83 php bin/console make:migration

# Просмотрите файл миграции в migrations/
# Затем выполните миграцию
docker exec backend-php83 php bin/console doctrine:migrations:migrate
```

### Откат миграции

```bash
docker exec backend-php83 php bin/console doctrine:migrations:migrate prev
```

### Создание сущности

```bash
docker exec backend-php83 php bin/console make:entity
```

---

## Операции с Docker контейнерами

### Управление контейнерами

```bash
# Список всех запущенных контейнеров
docker ps

# Список всех контейнеров (включая остановленные)
docker ps -a

# Проверка логов контейнера
docker logs backend-php83
docker logs -f backend-nginx     # Следить за логами в реальном времени
docker logs --tail 100 backend-php83  # Последние 100 строк

# Перезапуск конкретного контейнера
docker restart backend-php83

# Остановка конкретного контейнера
docker stop backend-php83

# Запуск конкретного контейнера
docker start backend-php83

# Удаление остановленных контейнеров
docker rm backend-php83
```

### Доступ к контейнерам

```bash
# Выполнение команд в контейнерах
docker exec backend-php83 php --version
docker exec backend-php83 composer --version

# Интерактивный доступ к shell
docker exec -it backend-php83 bash
docker exec -it backend-psql16 bash
```

### Операции с PostgreSQL базой данных

```bash
# Подключение к PostgreSQL
# ⚠️ Credentials берутся из .env.docker: POSTGRES_USER и POSTGRES_DB
# По умолчанию (dev): sollent / task-manager
docker exec -it backend-psql16 psql -U sollent -d task-manager

# Основные команды PostgreSQL (внутри psql)
\dt              # Список таблиц
\d+ tasks        # Описание таблицы tasks
\l               # Список баз данных
\q               # Выход

# Выполнение SQL с хоста
docker exec backend-psql16 psql -U sollent -d task-manager -c "SELECT COUNT(*) FROM tasks;"

# Резервное копирование базы данных
docker exec backend-psql16 pg_dump -U sollent task-manager > backup.sql

# Восстановление базы данных
docker exec -i backend-psql16 psql -U sollent -d task-manager < backup.sql

# Удаление и пересоздание базы данных (ОСТОРОЖНО!)
docker exec backend-php83 php bin/console doctrine:database:drop --force
docker exec backend-php83 php bin/console doctrine:database:create
docker exec backend-php83 php bin/console doctrine:migrations:migrate --no-interaction
```

### Команды Symfony Console

```bash
# Операции с кешем
docker exec backend-php83 php bin/console cache:clear
docker exec backend-php83 php bin/console cache:warmup

# Операции с базой данных
docker exec backend-php83 php bin/console doctrine:database:create
docker exec backend-php83 php bin/console doctrine:schema:update --dump-sql
docker exec backend-php83 php bin/console doctrine:migrations:status

# Команды отладки
docker exec backend-php83 php bin/console debug:router
docker exec backend-php83 php bin/console debug:container
docker exec backend-php83 php bin/console debug:autowiring

# Операции с Messenger (очередь)
docker exec backend-php83 php bin/console messenger:consume async -vv
docker exec backend-php83 php bin/console messenger:stats
```

### Инструменты качества кода

#### PHP-CS-Fixer (Стиль кода)

PHP-CS-Fixer автоматически исправляет PHP код для соответствия PSR-12 и современным стандартам PHP 8.3.

```bash
# Проверка стиля кода (dry-run, без изменений)
make cs-fixer-check
# ИЛИ
docker exec backend-php83 vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

# Автоматическое исправление стиля кода
make cs-fixer-fix
# ИЛИ
docker exec backend-php83 vendor/bin/php-cs-fixer fix --verbose
```

**Конфигурация:** `apps/backend/.php-cs-fixer.php`

**Ключевые возможности:**
- Соответствие PSR-12
- Современные возможности PHP 8.3 (strict types, readonly, enums)
- Завершающие запятые в массивах
- Выравнивание бинарных операторов
- Упорядоченные элементы классов
- Форматирование PHPDoc

#### PHPStan (Статический анализ)

PHPStan выполняет статический анализ для поиска ошибок без запуска кода.

```bash
# Запуск статического анализа (уровень 5)
make phpstan
# ИЛИ
docker exec backend-php83 vendor/bin/phpstan analyse --memory-limit=1G

# Генерация baseline (игнорировать существующие ошибки)
make phpstan-baseline
# ИЛИ
docker exec backend-php83 vendor/bin/phpstan analyse --generate-baseline
```

**Конфигурация:** `apps/backend/phpstan.neon`

**Текущий уровень:** 5 (хорошая стартовая точка)

**Включенные расширения:**
- phpstan-symfony (Symfony-специфичные проверки)
- phpstan-doctrine (Doctrine ORM проверки)

#### Запуск всех проверок качества

```bash
# Проверка стиля кода + статический анализ
make quality-check

# Исправление стиля кода + запуск статического анализа
make quality-fix
```

#### Git Pre-Commit хуки

Автоматически запускает проверки качества перед каждым коммитом.

**Установка:**
```bash
# Установка Git хуков (выполнить один раз после клонирования)
bash scripts/install-git-hooks.sh
```

**Что делает:**
1. Обнаруживает измененные PHP файлы в `apps/backend/`
2. Запускает проверку PHP-CS-Fixer (dry-run)
3. Запускает анализ PHPStan
4. Блокирует коммит при обнаружении проблем

**Обход хука** (не рекомендуется):
```bash
git commit --no-verify
```

### Полная пересборка проекта

```bash
# ВНИМАНИЕ: Это удалит ВСЕ данные (база данных, кеш, логи)

# 1. Остановка и удаление всех контейнеров (из корня проекта)
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml down -v  # -v удаляет volumes (данные базы!)

# 2. Удаление всех образов (опционально)
docker rmi $(docker images -q 'test_sonnet45*')

# 3. Пересборка контейнеров с нуля
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml build --no-cache

# 4. Запуск контейнеров
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml up -d

# 5. Переустановка зависимостей бэкенда
docker exec backend-php83 composer install

# 6. Пересоздание базы данных
docker exec backend-php83 php bin/console doctrine:database:create
docker exec backend-php83 php bin/console doctrine:migrations:migrate --no-interaction

# 7. (Опционально) Загрузка фикстур
docker exec backend-php83 php bin/console doctrine:fixtures:load --no-interaction

# 8. Очистка кеша
docker exec backend-php83 php bin/console cache:clear
```

### Проверка работоспособности контейнеров

```bash
# Проверка запущены ли все контейнеры
docker-compose ps

# Проверка использования ресурсов контейнерами
docker stats

# Проверка работоспособности конкретного контейнера
docker inspect backend-php83 | grep -i health
docker inspect backend-psql16 | grep -i status

# Тест ответа backend API
curl http://localhost:8089/api/health

# Тест подключения к базе данных
docker exec backend-php83 php bin/console doctrine:query:sql "SELECT 1"
```

---

## Тестирование

### Тесты бэкенда

```bash
# Запуск всех тестов
docker exec backend-php83 php bin/phpunit

# Запуск конкретного тестового файла
docker exec backend-php83 php bin/phpunit tests/Unit/Service/TaskServiceTest.php

# Запуск с покрытием кода
docker exec backend-php83 php bin/phpunit --coverage-text
```

### Тесты фронтенда

```bash
cd frontend

# Запуск всех тестов
npm run test:run

# Запуск тестов в режиме watch
npm run test

# Запуск с покрытием кода
npm run test:coverage
```

---

## Устранение неполадок

### Контейнер не запускается

```bash
# Проверка логов
docker logs backend-php83

# Проверка занят ли порт
lsof -i :8089
lsof -i :15432

# Принудительное удаление и пересоздание (из корня проекта)
docker-compose down
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml up -d --force-recreate
```

### Проблемы с подключением к базе данных

```bash
# Проверка запущен ли PostgreSQL
docker ps | grep psql16

# Тест подключения из PHP контейнера
# ⚠️ Используйте credentials из .env.docker (по умолчанию: sollent / Pahan1998 / task-manager)
docker exec backend-php83 php -r "
try {
    \$pdo = new PDO('pgsql:host=psql16;port=5432;dbname=task-manager', 'sollent', 'Pahan1998');
    echo 'Database connected!';
} catch (Exception \$e) {
    echo 'Database error: ' . \$e->getMessage();
}
"
```

### Проблемы с производительностью

```bash
# Проверка использования ресурсов
docker stats

# Очистка всех кешей
docker exec backend-php83 php bin/console cache:clear

# Перезапуск контейнеров (из корня проекта)
docker-compose restart
```

---

## 🚀 Production Deployment (Frontend)

### Запуск Frontend в Production режиме

**Production использует Multi-stage Docker build:**
- Stage 1: Node.js для сборки (npm run build)
- Stage 2: Nginx для раздачи статики (~56MB финальный образ)

```bash
# Production режим (из корня проекта)
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml up -d

# Проверка статуса
docker ps | grep frontend-prod

# Проверка логов
docker logs -f frontend-prod
```

**Результат:**
- ✅ Frontend: http://localhost:8080 (Nginx serving static files)
- ✅ Gzip + Brotli compression активна
- ✅ SPA routing работает (fallback на index.html)
- ✅ Proxy /api → backend-nginx:80
- ✅ Immutable кеширование для файлов с хешами

### Production Environment переменные

**ВАЖНО**: Production требует настройки переменных БЕЗ fallback (Fail-Fast принцип!)

```bash
# Создайте .env.docker.prod в корне проекта
cat > .env.docker.prod << EOF
# Frontend Production Configuration
FRONTEND_PROD_PORT=80
VITE_API_BASE_URL=https://api.production.com  # БЕЗ fallback!
EOF

# Запуск с production credentials
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml up -d
```

### Проверка Production сборки локально

```bash
# Build production образа
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml build frontend

# Проверка размера образа (должен быть ~50-60MB)
docker images | grep frontend-prod

# Тест production сборки
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml up frontend

# Проверка compression в браузере (Network tab):
# - Content-Encoding: gzip или br
# - Cache-Control: public, immutable (для *.js, *.css)
```

### Отличия Dev vs Prod

| Аспект | Development | Production |
|--------|-------------|------------|
| **Технология** | Vite dev server | Nginx |
| **Размер образа** | ~456MB | ~56MB |
| **Hot Reload** | ✅ Да | ❌ Нет |
| **Volumes** | ✅ Volume mount | ❌ Immutable |
| **Порт** | 3000 | 80/443 |
| **Compression** | ❌ Нет | ✅ Gzip + Brotli |
| **Env fallback** | ✅ Да | ❌ Нет (Fail-Fast!) |
| **Sourcemaps** | ✅ Да | ❌ Нет |

---

## Git Workflow

### Feature Branch

```bash
# Создание feature ветки
git checkout -b feature/my-feature

# Внесение изменений, коммит
git add .
git commit -m "Add my feature"

# Push на удаленный репозиторий
git push -u origin feature/my-feature

# Создание pull request на GitHub
```

---

## Связанные документы

- **[Архитектура Backend](../backend/ARCHITECTURE.md)** - Организация кода backend
- **[Архитектура Frontend](../frontend/ARCHITECTURE.md)** - Организация кода frontend
- **[Тестирование](testing/TESTING.md)** - Написание тестов
- **[Frontend Dockerization Plan](../ci-cd-plans/FRONTEND_DOCKERIZATION_PLAN.md)** - Детальный план докеризации frontend

---

*Последнее обновление: 2025-11-13*
*Версия: 2.0 - Обновлено для новой структуры docker-compose и переменных окружения*
