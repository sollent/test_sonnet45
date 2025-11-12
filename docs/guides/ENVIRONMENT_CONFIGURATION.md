# 🔐 Конфигурация Окружения (Environment Configuration)

> **Документация по управлению переменными окружения для Docker и Symfony**
> **Версия**: 1.0
> **Дата**: 2025-11-12

---

## 📋 Содержание

1. [Обзор](#обзор)
2. [Структура Файлов](#структура-файлов)
3. [Docker Environment Файлы](#docker-environment-файлы)
4. [Symfony/Backend Environment Файлы](#symfonybacked-environment-файлы)
5. [Использование по Окружениям](#использование-по-окружениям)
6. [GitHub Actions / CI/CD](#github-actions--cicd)
7. [Best Practices Безопасности](#best-practices-безопасности)
8. [Troubleshooting](#troubleshooting)

---

## Обзор

### Зачем Нужна Эта Структура?

Проект использует **двухуровневую систему переменных окружения**:

1. **Docker-уровень** (`.env.docker*`) - управляет инфраструктурой:
   - Порты контейнеров
   - Credentials для PostgreSQL, RabbitMQ
   - Настройки сервисов

2. **Application-уровень** (`apps/backend/.env*`) - управляет приложением:
   - Symfony конфигурация
   - Database connection strings
   - JWT secrets, Google OAuth
   - Message queue настройки

### Как Это Работает?

**Поток данных:**

```
.env.docker (корень проекта)
    ↓ (читается docker-compose)
Docker Compose
    ↓ (передает как environment переменные)
PHP Container (backend-php83)
    ↓ (Symfony читает из окружения)
apps/backend/.env (использует ${POSTGRES_USER:-fallback})
    ↓ (подставляет в DATABASE_URL)
Symfony Application
```

**Ключевой механизм:**
- `.env.docker` задает credentials (например `POSTGRES_USER=user`)
- `docker-compose.app.yml` передает эти переменные в PHP контейнер через `environment:`
- `apps/backend/.env` использует синтаксис `${POSTGRES_USER:-fallback}` для чтения из окружения
- Symfony видит финальный DATABASE_URL с правильными credentials

### Преимущества

✅ **Безопасность**: Sensitive данные не коммитятся в git
✅ **Гибкость**: Легко переключаться между окружениями
✅ **CI/CD Ready**: Переменные можно переопределить в GitHub Actions
✅ **Документированность**: `.example` файлы служат документацией
✅ **Единый источник истины**: `.env.docker` контролирует все credentials

---

## Структура Файлов

```
test_sonnet45/
├── .env.docker              # Docker dev окружение (НЕ в git)
├── .env.docker.prod         # Docker production (НЕ в git)
├── .env.docker.test         # Docker test/CI (НЕ в git)
├── .env.docker.example      # Шаблон (В git ✅)
├── .gitignore               # Защищает sensitive файлы
│
├── apps/backend/
│   ├── .env                 # Symfony default (В git ✅)
│   ├── .env.dev             # Symfony dev (В git ✅)
│   ├── .env.prod            # Symfony prod template (В git ✅)
│   ├── .env.test            # Symfony test (В git ✅)
│   ├── .env.example         # Шаблон (В git ✅)
│   └── .env.local           # Local overrides (НЕ в git)
│
└── docker-compose.yml       # Подключает .env файлы
```

### Что Коммитится в Git?

**✅ Коммитится:**
- `.env.docker.example` - шаблон для Docker
- `apps/backend/.env` - default Symfony конфигурация
- `apps/backend/.env.{dev,prod,test,example}` - шаблоны для окружений

**❌ НЕ коммитится (.gitignore):**
- `.env.docker` - содержит реальные credentials
- `.env.docker.{prod,test}` - production/test credentials
- `apps/backend/.env.local` - локальные переопределения

---

## Docker Environment Файлы

### `.env.docker` (Development - по умолчанию)

**Расположение**: `/test_sonnet45/.env.docker`

```bash
# PostgreSQL Configuration
POSTGRES_DB=backend-app
POSTGRES_USER=user
POSTGRES_PASSWORD=password
POSTGRES_PORT=15432

# RabbitMQ Configuration
RABBITMQ_USER=user
RABBITMQ_PASSWORD=password
RABBITMQ_PORT=5672
RABBITMQ_MANAGEMENT_PORT=15672

# Nginx Configuration
NGINX_PORT=8089

# PHP-FPM Configuration
PHP_FPM_PORT=9009
```

### `.env.docker.prod` (Production)

**Критично**: Все пароли **ОБЯЗАТЕЛЬНО** переопределяются в production!

```bash
POSTGRES_DB=backend_prod
POSTGRES_USER=prod_user
POSTGRES_PASSWORD=CHANGE_ME_IN_PRODUCTION  # ⚠️ Установить через GitHub Secrets
POSTGRES_PORT=5432

RABBITMQ_USER=prod_user
RABBITMQ_PASSWORD=CHANGE_ME_IN_PRODUCTION  # ⚠️ Установить через GitHub Secrets
RABBITMQ_PORT=5672
RABBITMQ_MANAGEMENT_PORT=15672

NGINX_PORT=80
PHP_FPM_PORT=9000
```

### `.env.docker.test` (CI/CD Testing)

```bash
POSTGRES_DB=backend_test
POSTGRES_USER=test_user
POSTGRES_PASSWORD=test_password
POSTGRES_PORT=15433

RABBITMQ_USER=test_user
RABBITMQ_PASSWORD=test_password
# ... остальные переменные
```

### Как Используются в docker-compose.yml

```yaml
# infrastructure/docker/docker-compose.app.yml
services:
  # PostgreSQL контейнер - использует credentials из .env.docker
  psql16:
    environment:
      POSTGRES_DB: ${POSTGRES_DB:-backend-app}
      POSTGRES_USER: ${POSTGRES_USER:-user}
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD:-password}
    ports:
      - "${POSTGRES_PORT:-15432}:5432"

  # PHP контейнер - получает те же credentials для передачи в Symfony
  php83-fpm:
    environment:
      # Передаем credentials из .env.docker в PHP окружение
      POSTGRES_DB: ${POSTGRES_DB:-backend-app}
      POSTGRES_USER: ${POSTGRES_USER:-user}
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD:-password}
      RABBITMQ_USER: ${RABBITMQ_USER:-user}
      RABBITMQ_PASSWORD: ${RABBITMQ_PASSWORD:-password}
```

**Синтаксис**: `${VAR:-default}` - использует `VAR` из `.env.docker` или default значение

**Ключевой момент**: Переменные из `.env.docker` передаются в **оба** контейнера:
- PostgreSQL использует их для создания user/database
- PHP контейнер получает те же переменные для использования в Symfony

---

## Symfony/Backend Environment Файлы

### `apps/backend/.env` (Default)

**Расположение**: `/test_sonnet45/apps/backend/.env`

Используется **по умолчанию** для разработки. Содержит безопасные development credentials.

```bash
APP_ENV=dev
APP_SECRET=256fb1d32ad7bb1f1cdac90db6834621
APP_DEBUG=true

# Database URL использует environment переменные из Docker контейнера
# Переменные POSTGRES_USER, POSTGRES_PASSWORD, POSTGRES_DB передаются через docker-compose
DATABASE_URL="postgresql://${POSTGRES_USER:-user}:${POSTGRES_PASSWORD:-password}@psql16:5432/${POSTGRES_DB:-backend-app}?serverVersion=16&charset=utf8"

# RabbitMQ DSN также использует environment переменные
MESSENGER_TRANSPORT_DSN=amqp://${RABBITMQ_USER:-user}:${RABBITMQ_PASSWORD:-password}@rabbitmq:5672/%2f/messages

JWT_PASSPHRASE=e79f40ab30b66599fe3ab08a3513543dba5c814bb228690366d4b1d79ad4d003

GOOGLE_CLIENT_ID=1084991394082-upgn45i5u4g8jc3u1p9n8h9i1sldpsa1.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=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
```

### `apps/backend/.env.prod` (Production Template)

**Критично**: Production переменные **ОБЯЗАТЕЛЬНО** переопределяются!

```bash
APP_ENV=prod
APP_SECRET=CHANGE_ME_IN_PRODUCTION  # ⚠️ Генерируется в production
APP_DEBUG=false

DATABASE_URL="postgresql://prod_user:CHANGE_ME@psql16:5432/backend_prod?serverVersion=16&charset=utf8"

JWT_PASSPHRASE=CHANGE_ME_IN_PRODUCTION  # ⚠️ Генерируется в production

GOOGLE_CLIENT_ID=YOUR_PRODUCTION_CLIENT_ID
GOOGLE_CLIENT_SECRET=YOUR_PRODUCTION_CLIENT_SECRET
```

### `apps/backend/.env.test` (Testing)

Используется для **PHPUnit тестов** и CI/CD.

```bash
APP_ENV=test
APP_SECRET=test_secret_for_ci_cd_only
APP_DEBUG=false

DATABASE_URL="postgresql://test_user:test_password@psql16:5432/backend_test?serverVersion=16&charset=utf8"

GOOGLE_CLIENT_ID=test_client_id
GOOGLE_CLIENT_SECRET=test_client_secret
```

### `apps/backend/.env.local` (Локальные Переопределения)

**НЕ коммитится в git!** Используйте для локальных изменений.

```bash
# Пример: переопределить DATABASE_URL для локальной БД
DATABASE_URL="postgresql://myuser:mypass@localhost:15432/backend-app?serverVersion=16&charset=utf8"
```

---

## Использование по Окружениям

### Development (Локальная Разработка)

**Шаг 1**: Создайте `.env.docker` из шаблона

```bash
cp .env.docker.example .env.docker
# Отредактируйте если нужно изменить порты/пароли
```

**Шаг 2**: Запустите Docker контейнеры

```bash
docker-compose up -d
```

**Результат**:
- Использует `.env.docker` для инфраструктуры
- Использует `apps/backend/.env` для Symfony
- Все работает из коробки с dev credentials

### Production (Боевое Окружение)

**Шаг 1**: Создайте production env файлы

```bash
cp .env.docker.example .env.docker.prod
cp apps/backend/.env.example apps/backend/.env.prod
```

**Шаг 2**: **КРИТИЧНО** - Измените все `CHANGE_ME_*` значения!

```bash
# Генерация APP_SECRET
php -r "echo bin2hex(random_bytes(16));"

# Генерация JWT_PASSPHRASE
openssl rand -hex 32
```

**Шаг 3**: Запустите с production конфигурацией

```bash
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose-prod.yml up -d
```

**Результат**:
- Использует `.env.docker.prod`
- Использует `apps/backend/.env.prod`
- Все credentials должны быть изменены!

### Testing / CI/CD

**Локально**:

```bash
# Создать test окружение
cp .env.docker.example .env.docker.test

# Запустить с test конфигурацией
APP_ENV=test docker-compose up -d
docker exec backend-php83 php bin/phpunit
```

**В GitHub Actions** (см. следующий раздел)

---

## GitHub Actions / CI/CD

### Настройка GitHub Secrets

1. Перейдите в **Settings → Secrets and variables → Actions**
2. Добавьте secrets для production:

```
PROD_POSTGRES_PASSWORD=your_strong_password
PROD_RABBITMQ_PASSWORD=your_strong_password
PROD_APP_SECRET=your_generated_secret
PROD_JWT_PASSPHRASE=your_generated_passphrase
PROD_GOOGLE_CLIENT_ID=your_client_id
PROD_GOOGLE_CLIENT_SECRET=your_client_secret
```

### Пример GitHub Actions Workflow

**`.github/workflows/deploy.yml`:**

```yaml
name: Deploy to Production

on:
  push:
    branches: [main]

env:
  # Docker infrastructure variables
  POSTGRES_DB: backend_prod
  POSTGRES_USER: prod_user
  POSTGRES_PASSWORD: ${{ secrets.PROD_POSTGRES_PASSWORD }}
  POSTGRES_PORT: 5432

  RABBITMQ_USER: prod_user
  RABBITMQ_PASSWORD: ${{ secrets.PROD_RABBITMQ_PASSWORD }}

  NGINX_PORT: 80
  PHP_FPM_PORT: 9000

jobs:
  deploy:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Create production env files
        run: |
          # Docker env file
          cat > .env.docker.prod << EOF
          POSTGRES_DB=${{ env.POSTGRES_DB }}
          POSTGRES_USER=${{ env.POSTGRES_USER }}
          POSTGRES_PASSWORD=${{ env.POSTGRES_PASSWORD }}
          POSTGRES_PORT=${{ env.POSTGRES_PORT }}
          RABBITMQ_USER=${{ env.RABBITMQ_USER }}
          RABBITMQ_PASSWORD=${{ env.RABBITMQ_PASSWORD }}
          NGINX_PORT=${{ env.NGINX_PORT }}
          PHP_FPM_PORT=${{ env.PHP_FPM_PORT }}
          EOF

          # Symfony env file
          cat > apps/backend/.env.prod << EOF
          APP_ENV=prod
          APP_SECRET=${{ secrets.PROD_APP_SECRET }}
          APP_DEBUG=false
          DATABASE_URL="postgresql://${{ env.POSTGRES_USER }}:${{ env.POSTGRES_PASSWORD }}@psql16:5432/${{ env.POSTGRES_DB }}?serverVersion=16&charset=utf8"
          JWT_PASSPHRASE=${{ secrets.PROD_JWT_PASSPHRASE }}
          GOOGLE_CLIENT_ID=${{ secrets.PROD_GOOGLE_CLIENT_ID }}
          GOOGLE_CLIENT_SECRET=${{ secrets.PROD_GOOGLE_CLIENT_SECRET }}
          EOF

      - name: Deploy with Docker Compose
        run: |
          docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose-prod.yml up -d
```

### Преимущества

✅ **Secrets не в коде** - все sensitive данные в GitHub Secrets
✅ **Runtime подстановка** - переменные подставляются при деплое
✅ **Переиспользование** - одни secrets для staging и production

---

## Best Practices Безопасности

### ❌ НИКОГДА НЕ ДЕЛАЙТЕ ТАК

```bash
# НЕ коммитьте файлы с реальными credentials
git add .env.docker
git add apps/backend/.env.local

# НЕ используйте слабые пароли в production
POSTGRES_PASSWORD=password
RABBITMQ_PASSWORD=123456

# НЕ храните secrets в коде
const API_KEY = "sk-1234567890abcdef";
```

### ✅ ПРАВИЛЬНО

```bash
# 1. Используйте .example файлы как шаблоны
cp .env.docker.example .env.docker
# Затем редактируйте .env.docker (он в .gitignore)

# 2. Генерируйте сильные пароли
openssl rand -base64 32

# 3. Используйте переменные окружения в CI/CD
POSTGRES_PASSWORD: ${{ secrets.PROD_POSTGRES_PASSWORD }}

# 4. Регулярно ротируйте secrets
# Обновляйте JWT_PASSPHRASE, APP_SECRET каждые 90 дней
```

### Проверка Перед Коммитом

```bash
# Проверьте что sensitive файлы не попадут в git
git status

# Должны видеть:
# .env.docker.example (зеленый - tracked)
# .env.docker (серый - ignored) ✅

# Если .env.docker зеленый (tracked) - ОСТАНОВИТЕСЬ!
# Добавьте его в .gitignore!
```

---

## Troubleshooting

### Проблема: "Database connection failed"

**Причина**: Несовпадение credentials между `.env.docker` и `apps/backend/.env`

**Решение**:

```bash
# Проверьте что credentials совпадают
cat .env.docker | grep POSTGRES
cat apps/backend/.env | grep DATABASE_URL

# Должны использовать одинаковые user/password!
```

### Проблема: "Port already in use"

**Причина**: Порт занят другим сервисом

**Решение**:

```bash
# Измените порт в .env.docker
POSTGRES_PORT=15433  # Вместо 15432
NGINX_PORT=8090      # Вместо 8089

# Перезапустите контейнеры
docker-compose down && docker-compose up -d
```

### Проблема: "Environment variables not loaded in production"

**Причина**: Забыли создать `.env.docker.prod` или `.env.prod`

**Решение**:

```bash
# Убедитесь что файлы существуют
ls -la .env.docker.prod
ls -la apps/backend/.env.prod

# Проверьте что docker-compose использует правильный файл
docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose-prod.yml config
```

### Проблема: "JWT authentication fails"

**Причина**: JWT ключи не сгенерированы или неправильный passphrase

**Решение**:

```bash
# Сгенерируйте JWT ключи
docker exec backend-php83 php bin/console lexik:jwt:generate-keypair

# Убедитесь что JWT_PASSPHRASE совпадает в .env
cat apps/backend/.env | grep JWT_PASSPHRASE
```

### Отладка: Просмотр Загруженных Переменных

```bash
# Docker переменные
docker-compose config

# Symfony переменные
docker exec backend-php83 php bin/console debug:container --env-vars

# Конкретная переменная
docker exec backend-php83 php -r "echo getenv('DATABASE_URL');"
```

---

## Дополнительные Ресурсы

- [Symfony Environment Variables](https://symfony.com/doc/current/configuration.html#configuration-environments)
- [Docker Compose Environment Variables](https://docs.docker.com/compose/environment-variables/)
- [GitHub Actions Secrets](https://docs.github.com/en/actions/security-guides/encrypted-secrets)
- [12-Factor App: Config](https://12factor.net/config)

---

**Последнее обновление**: 2025-11-12
**Версия документа**: 1.0
**Автор**: Claude Code AI
