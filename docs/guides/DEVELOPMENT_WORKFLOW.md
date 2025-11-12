# 🛠 Процесс разработки - Руководство для ежедневной работы

> **Кратко**: Docker для бэкенда (Symfony + PostgreSQL) через `docker-compose.yml` в корне, npm для фронтенда (Vue + Vite). Миграции БД через Doctrine. Git workflow с feature-ветками.

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
│   ├── docker/                     # Docker конфигурация
│   │   ├── docker-compose.app.yml  # Сервисы приложения
│   │   ├── docker-compose.ai.yml   # AI сервисы (заглушка)
│   │   ├── docker-compose.dev.yml  # Dev переопределения
│   │   ├── dev/
│   │   │   ├── nginx/              # Nginx конфигурация
│   │   │   └── php/                # PHP-FPM конфигурация
│   │   └── cron/                   # Cron задачи
│   └── ai-services/                # AI инфраструктура (заглушка)
└── scripts/                        # Утилитные скрипты
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

# Копируем файл окружения
cp .env .env.local

# Настраиваем .env.local
DATABASE_URL="postgresql://user:password@psql16:5432/backend-app"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret

# Генерируем JWT ключи
mkdir -p config/jwt
openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

# Запускаем Docker сервисы (из корня проекта)
cd ../..
docker-compose up -d

# Устанавливаем зависимости
docker exec backend-php83 composer install

# Выполняем миграции
docker exec backend-php83 php bin/console doctrine:migrations:migrate

# (Опционально) Загружаем фикстуры
docker exec backend-php83 php bin/console doctrine:fixtures:load
```

### 3. Настройка фронтенда

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

### 4. Доступ к приложению

- **Фронтенд:** http://localhost:3000 (Vite dev server)
- **Backend API:** http://localhost:8089/api (Nginx)
- **PostgreSQL:** localhost:15432 (внешний порт)
- **RabbitMQ Management:** http://localhost:15672 (user/password)

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

```bash
# Запуск бэкенда (из директории docker)
cd docker
docker-compose up -d

# Или из любого места проекта:
docker-compose -f docker/docker-compose.yml up -d

# Запуск фронтенда (из директории frontend)
cd frontend
npm run dev
```

### Остановка сервисов

```bash
# Остановка бэкенда (из директории docker)
cd docker
docker-compose down

# Или из любого места:
docker-compose -f docker/docker-compose.yml down

# Остановка фронтенда
Ctrl+C в терминале
```

### Пересборка Docker контейнеров

```bash
# Пересборка всех контейнеров (при изменении Dockerfile)
cd docker
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Пересборка конкретного сервиса
docker-compose build --no-cache php83-fpm
docker-compose up -d php83-fpm
```

### Просмотр логов Docker

```bash
# Все сервисы
docker-compose logs -f

# Конкретный сервис
docker-compose logs -f php83-fpm
docker-compose logs -f nginx
docker-compose logs -f psql16
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
docker exec -it backend-psql16 psql -U user -d backend-app

# Основные команды PostgreSQL (внутри psql)
\dt              # Список таблиц
\d+ tasks        # Описание таблицы tasks
\l               # Список баз данных
\q               # Выход

# Выполнение SQL с хоста
docker exec backend-psql16 psql -U user -d backend-app -c "SELECT COUNT(*) FROM tasks;"

# Резервное копирование базы данных
docker exec backend-psql16 pg_dump -U user backend-app > backup.sql

# Восстановление базы данных
docker exec -i backend-psql16 psql -U user -d backend-app < backup.sql

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

# 1. Остановка и удаление всех контейнеров
cd docker
docker-compose down -v  # -v удаляет volumes (данные базы!)

# 2. Удаление всех образов (опционально)
docker rmi $(docker images -q 'docker_*')

# 3. Пересборка контейнеров с нуля
docker-compose build --no-cache

# 4. Запуск контейнеров
docker-compose up -d

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

# Принудительное удаление и пересоздание
cd docker
docker-compose down
docker-compose up -d --force-recreate
```

### Проблемы с подключением к базе данных

```bash
# Проверка запущен ли PostgreSQL
docker ps | grep psql16

# Тест подключения из PHP контейнера
docker exec backend-php83 php -r "
try {
    \$pdo = new PDO('pgsql:host=psql16;port=5432;dbname=backend-app', 'user', 'password');
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

# Перезапуск контейнеров
cd docker
docker-compose restart
```

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

- **[Архитектура](../backend/ARCHITECTURE.md)** - Организация кода
- **[Тестирование](TESTING.md)** - Написание тестов

---

*Последнее обновление: 2025-01-05*
