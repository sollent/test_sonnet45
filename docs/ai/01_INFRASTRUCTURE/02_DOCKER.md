# 🐳 Фаза 1.2: Руководство по Конфигурации Docker

> **Версия Документа**: 2.0.0
> **Последнее Обновление**: 2025-11-27
> **Предполагаемое Время**: 0.5 дня
> **Сложность**: СРЕДНЯЯ
> **Предварительные Требования**: Основы Docker, понимание YAML

---

## ⚠️ ВАЖНО: AI Сервисы НЕ в Docker!

**С ноября 2025 года Ollama и Whisper работают НАТИВНО на хосте, а не в Docker контейнерах!**

Этот документ описывает Docker конфигурацию только для **сервисов приложения**:
- PHP/Symfony backend
- PostgreSQL
- RabbitMQ
- Redis
- Centrifugo
- Nginx
- Frontend (опционально)

**AI сервисы (Ollama, Whisper)** доступны из Docker через `host.docker.internal`:
- Ollama: `http://host.docker.internal:11434`
- Whisper: `http://host.docker.internal:9001`

Инструкции по нативной установке AI: [`NATIVE_INSTALLATION.md`](../NATIVE_INSTALLATION.md)

---

## 📋 Содержание

1. [Обзор Архитектуры](#обзор-архитектуры)
2. [Доступ к Нативным AI Сервисам](#доступ-к-нативным-ai-сервисам)
3. [Конфигурация Docker Compose](#конфигурация-docker-compose)
4. [Переменные Окружения](#переменные-окружения)
5. [Сетевая Конфигурация](#сетевая-конфигурация)

---

## 🏗️ Обзор Архитектуры

### Топология Сервисов

```yaml
Architecture:
  Docker Контейнеры (Application):
    - backend-php83 (Symfony)
    - backend-nginx
    - backend-psql16
    - backend-rabbitmq
    - backend-redis
    - centrifugo (WebSocket)

  Нативные Сервисы (Host):
    - Ollama (LLM) → порт 11434
    - Whisper (STT) → порт 9001

  Сетевое Взаимодействие:
    - Docker → Host: через host.docker.internal
    - Host → Docker: через localhost:PORT
```

### Поток Коммуникации

```
┌─────────────────────────────────────────────────────────────────┐
│                         HOST (нативно)                          │
│  ┌─────────────────┐    ┌─────────────────┐                    │
│  │     Ollama      │    │     Whisper     │                    │
│  │   :11434        │    │    :9001        │                    │
│  └────────▲────────┘    └────────▲────────┘                    │
│           │                      │                              │
│           │  host.docker.internal                               │
│           │                      │                              │
│  ┌────────┴──────────────────────┴────────────────────────────┐│
│  │                    Docker Network                           ││
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   ││
│  │  │  PHP83   │  │PostgreSQL│  │ RabbitMQ │  │Centrifugo│   ││
│  │  │  :9009   │  │  :15432  │  │  :5672   │  │  :8000   │   ││
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘   ││
│  └─────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔗 Доступ к Нативным AI Сервисам

### Конфигурация PHP контейнера

Docker контейнеры обращаются к нативным AI сервисам через `host.docker.internal`:

```yaml
# docker-compose.yml или infrastructure/docker/docker-compose.app.yml
services:
  php83-fpm:
    # ... existing config ...
    environment:
      - OLLAMA_BASE_URL=http://host.docker.internal:11434
      - WHISPER_BASE_URL=http://host.docker.internal:9001
    extra_hosts:
      - "host.docker.internal:host-gateway"  # Требуется для Linux!
```

### Symfony .env конфигурация

```bash
# apps/backend/.env

# AI Services (нативные на хосте)
OLLAMA_BASE_URL=http://host.docker.internal:11434
OLLAMA_MODEL=qwen2.5:14b-instruct-q4_K_M

WHISPER_BASE_URL=http://host.docker.internal:9001
WHISPER_MODEL=large-v3
WHISPER_LANGUAGE=ru
```

### Linux: host.docker.internal

На Linux `host.docker.internal` не работает по умолчанию. **Обязательно** добавьте `extra_hosts`:

```yaml
services:
  php83-fpm:
    extra_hosts:
      - "host.docker.internal:host-gateway"

  # Добавьте для всех сервисов, которые обращаются к AI
  backend-nginx:
    extra_hosts:
      - "host.docker.internal:host-gateway"
```

### Проверка доступа из Docker

```bash
# Проверка Ollama
docker exec backend-php83 curl -s http://host.docker.internal:11434/api/tags

# Проверка Whisper
docker exec backend-php83 curl -s http://host.docker.internal:9001/health

# Тест генерации LLM
docker exec backend-php83 curl -X POST http://host.docker.internal:11434/api/generate \
  -H "Content-Type: application/json" \
  -d '{"model":"qwen2.5:14b-instruct-q4_K_M","prompt":"test","stream":false}'
```

---

## 🐳 Конфигурация Docker Compose

### Структура файлов

```
project-root/
├── docker-compose.yml              # Главный (includes others)
├── .env.docker                     # Docker переменные окружения
└── infrastructure/docker/
    ├── docker-compose.app.yml      # Базовые сервисы приложения
    ├── docker-compose.dev.yml      # Dev overrides
    └── docker-compose-prod.yml     # Production overrides
```

### Базовый docker-compose.app.yml

```yaml
# infrastructure/docker/docker-compose.app.yml

services:
  psql16:
    image: postgres:16-alpine
    container_name: backend-psql16
    restart: unless-stopped
    volumes:
      - psql16-data:/var/lib/postgresql/data
    environment:
      - POSTGRES_DB=${POSTGRES_DB}
      - POSTGRES_USER=${POSTGRES_USER}
      - POSTGRES_PASSWORD=${POSTGRES_PASSWORD}
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${POSTGRES_USER} -d ${POSTGRES_DB}"]
      interval: 10s
      timeout: 5s
      retries: 5

  php83-fpm:
    build:
      context: ./dev/php
      dockerfile: Dockerfile
    container_name: backend-php83
    restart: unless-stopped
    volumes:
      - ../../apps/backend:/var/www/html
    environment:
      # Database
      - POSTGRES_DB=${POSTGRES_DB}
      - POSTGRES_USER=${POSTGRES_USER}
      - POSTGRES_PASSWORD=${POSTGRES_PASSWORD}
      # AI Services (нативные)
      - OLLAMA_BASE_URL=http://host.docker.internal:11434
      - WHISPER_BASE_URL=http://host.docker.internal:9001
    extra_hosts:
      - "host.docker.internal:host-gateway"
    depends_on:
      psql16:
        condition: service_healthy

  nginx:
    image: nginx:alpine
    container_name: backend-nginx
    restart: unless-stopped
    volumes:
      - ../../apps/backend/public:/var/www/html/public:ro
      - ./dev/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    ports:
      - "${NGINX_PORT:-8089}:80"
    depends_on:
      - php83-fpm

  rabbitmq:
    image: rabbitmq:3.12-management-alpine
    container_name: backend-rabbitmq
    restart: unless-stopped
    environment:
      - RABBITMQ_DEFAULT_USER=${RABBITMQ_USER}
      - RABBITMQ_DEFAULT_PASS=${RABBITMQ_PASSWORD}
    ports:
      - "${RABBITMQ_PORT:-5672}:5672"
      - "${RABBITMQ_MANAGEMENT_PORT:-15672}:15672"

  redis:
    image: redis:7.2-alpine
    container_name: backend-redis
    restart: unless-stopped
    command: redis-server --requirepass ${REDIS_PASSWORD:-redis}
    ports:
      - "${REDIS_PORT:-6379}:6379"

  centrifugo:
    image: centrifugo/centrifugo:v5
    container_name: centrifugo
    restart: unless-stopped
    volumes:
      - ./configs/centrifugo/config.json:/centrifugo/config.json:ro
    command: centrifugo -c /centrifugo/config.json
    ports:
      - "${CENTRIFUGO_PORT:-8000}:8000"
    depends_on:
      - redis

volumes:
  psql16-data:
```

### Dev overrides (docker-compose.dev.yml)

```yaml
# infrastructure/docker/docker-compose.dev.yml

services:
  psql16:
    ports:
      - "${POSTGRES_PORT:-15432}:5432"
    environment:
      - POSTGRES_DB=${POSTGRES_DB:-backend-app}
      - POSTGRES_USER=${POSTGRES_USER:-user}
      - POSTGRES_PASSWORD=${POSTGRES_PASSWORD:-password}

  php83-fpm:
    environment:
      - APP_ENV=dev
      - APP_DEBUG=true
      - POSTGRES_DB=${POSTGRES_DB:-backend-app}
      - POSTGRES_USER=${POSTGRES_USER:-user}
      - POSTGRES_PASSWORD=${POSTGRES_PASSWORD:-password}
    volumes:
      - ../../apps/backend:/var/www/html
      - ./dev/php/php-dev.ini:/usr/local/etc/php/conf.d/php-dev.ini:ro

  nginx:
    ports:
      - "${NGINX_PORT:-8089}:80"
```

---

## 🔐 Переменные Окружения

### .env.docker (пример)

```bash
# .env.docker

# PostgreSQL
POSTGRES_DB=backend-app
POSTGRES_USER=user
POSTGRES_PASSWORD=password
POSTGRES_PORT=15432

# RabbitMQ
RABBITMQ_USER=user
RABBITMQ_PASSWORD=password
RABBITMQ_PORT=5672
RABBITMQ_MANAGEMENT_PORT=15672

# Redis
REDIS_PASSWORD=redis
REDIS_PORT=6379

# Nginx
NGINX_PORT=8089

# Centrifugo
CENTRIFUGO_PORT=8000
CENTRIFUGO_TOKEN_HMAC_SECRET=your-secret-key-min-32-chars
CENTRIFUGO_API_KEY=your-api-key-min-32-chars

# AI Services (информационно - они нативные)
# Ollama: http://localhost:11434
# Whisper: http://localhost:9001
```

### apps/backend/.env (Symfony)

```bash
# apps/backend/.env

APP_ENV=dev
APP_SECRET=your-app-secret

# Database
DATABASE_URL="postgresql://${POSTGRES_USER:-user}:${POSTGRES_PASSWORD:-password}@psql16:5432/${POSTGRES_DB:-backend-app}?serverVersion=16"

# RabbitMQ
MESSENGER_TRANSPORT_DSN=amqp://${RABBITMQ_USER:-user}:${RABBITMQ_PASSWORD:-password}@rabbitmq:5672/%2f/messages

# AI Services (НАТИВНЫЕ - через host.docker.internal)
OLLAMA_BASE_URL=http://host.docker.internal:11434
OLLAMA_MODEL=qwen2.5:14b-instruct-q4_K_M
WHISPER_BASE_URL=http://host.docker.internal:9001
WHISPER_MODEL=large-v3
WHISPER_LANGUAGE=ru

# Centrifugo
CENTRIFUGO_URL=http://centrifugo:8000
CENTRIFUGO_API_KEY=your-api-key-min-32-chars
CENTRIFUGO_SECRET=your-secret-key-min-32-chars
```

---

## 🌐 Сетевая Конфигурация

### Схема портов

| Сервис | Внутренний порт | Внешний порт | Тип |
|--------|-----------------|--------------|-----|
| **Ollama** | - | 11434 | Нативный |
| **Whisper** | - | 9001 | Нативный |
| PostgreSQL | 5432 | 15432 | Docker |
| PHP-FPM | 9000 | - | Docker (internal) |
| Nginx | 80 | 8089 | Docker |
| RabbitMQ | 5672 | 5672 | Docker |
| RabbitMQ Admin | 15672 | 15672 | Docker |
| Redis | 6379 | 6379 | Docker |
| Centrifugo | 8000 | 8000 | Docker |
| Frontend | 3000 | 3000 | Local/Docker |

### Сетевые имена для внутреннего обращения

```yaml
# Из PHP контейнера:
PostgreSQL: psql16:5432
RabbitMQ: rabbitmq:5672
Redis: redis:6379
Centrifugo: centrifugo:8000
Ollama: host.docker.internal:11434  # Нативный!
Whisper: host.docker.internal:9001  # Нативный!
```

---

## 🚀 Команды Управления

### Запуск

```bash
# Development
docker-compose up -d

# С логами
docker-compose up

# Пересборка
docker-compose up -d --build
```

### Остановка

```bash
# Остановить
docker-compose down

# Остановить и удалить volumes (осторожно!)
docker-compose down -v
```

### Проверка

```bash
# Статус
docker-compose ps

# Логи
docker-compose logs -f php83-fpm
docker-compose logs -f --tail=100

# Проверка AI доступа
docker exec backend-php83 curl http://host.docker.internal:11434/api/tags
docker exec backend-php83 curl http://host.docker.internal:9001/health
```

### Консоль

```bash
# PHP контейнер
docker exec -it backend-php83 bash

# PostgreSQL
docker exec -it backend-psql16 psql -U user -d backend-app

# Symfony команды
docker exec backend-php83 php bin/console cache:clear
docker exec backend-php83 php bin/console doctrine:migrations:migrate
```

---

## 🔧 Troubleshooting

### Проблема: Cannot connect to host.docker.internal

**Симптом**: PHP не может подключиться к Ollama/Whisper

```bash
# Проверить что AI сервисы запущены
curl http://localhost:11434/api/tags
curl http://localhost:9001/health

# Проверить extra_hosts в docker-compose
docker inspect backend-php83 | grep -A5 ExtraHosts

# Linux: убедиться что extra_hosts добавлен
extra_hosts:
  - "host.docker.internal:host-gateway"
```

### Проблема: Медленное подключение к AI

**Симптом**: Таймауты при обращении к Ollama/Whisper

```bash
# Увеличить таймаут в PHP
# config/packages/framework.yaml
framework:
    http_client:
        default_options:
            timeout: 300  # 5 минут для LLM

# Или в сервисе
$response = $this->httpClient->request('POST', $url, [
    'timeout' => 300,
]);
```

### Проблема: Контейнер не видит изменения кода

```bash
# Пересобрать контейнер
docker-compose up -d --build php83-fpm

# Очистить кеш Symfony
docker exec backend-php83 php bin/console cache:clear
```

---

## ✅ Следующие Шаги

1. ✅ Docker для приложения настроен
2. ✅ Доступ к нативным AI сервисам настроен
3. → Перейти к [AI Сервисы (детальная настройка)](03_AI_SERVICES.md)
4. → Затем [Безопасность и Сеть](04_SECURITY.md)

---

**Статус Документа**: Обновлен для нативной архитектуры AI
**Последнее Тестирование**: 2025-11-27
**Автор**: AI Architecture Team

---

## 📝 История Изменений

### v2.0.0 (2025-11-27)
- **КРИТИЧЕСКОЕ ИЗМЕНЕНИЕ**: AI сервисы теперь нативные, не Docker
- Добавлена конфигурация `host.docker.internal`
- Удалены Docker конфигурации для Ollama/Whisper
- Обновлена схема сетевого взаимодействия
- Добавлены примеры проверки доступа к AI

### v1.0.0 (2025-11-08)
- Первоначальная версия (AI в Docker)
