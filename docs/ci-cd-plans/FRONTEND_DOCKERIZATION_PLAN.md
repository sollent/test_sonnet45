# 🐳 План Докеризации Frontend

> **Цель**: Завернуть Vue.js frontend в Docker с разделением dev/prod окружений
> **Версия**: 1.0
> **Дата**: 2025-11-13
> **Статус**: 📋 Планирование

---

## 📋 Содержание

1. [Анализ Текущего Состояния](#анализ-текущего-состояния)
2. [Архитектура Решения](#архитектура-решения)
3. [Рекомендации по Реализации](#рекомендации-по-реализации)
4. [Детальный План Реализации](#детальный-план-реализации)
5. [Структура Файлов](#структура-файлов)
6. [Environment Переменные](#environment-переменные)
7. [Чек-лист Реализации](#чек-лист-реализации)

---

## Анализ Текущего Состояния

### Текущая Ситуация

**Frontend запускается локально:**
```bash
cd apps/frontend
npm install
npm run dev  # → http://localhost:3000
```

**Проблемы:**
- ❌ Требуется установленный Node.js на хост-машине
- ❌ Разные версии Node.js у разработчиков → "works on my machine"
- ❌ Нет изоляции окружения
- ❌ Production сборка не автоматизирована
- ❌ Нет единого docker-compose для всего проекта

### Существующая Docker Инфраструктура

**Backend уже докеризован:**
```
infrastructure/docker/
├── docker-compose.app.yml       # Базовый конфиг (БЕЗ fallback)
├── docker-compose.dev.yml       # Dev overrides (С fallback)
├── docker-compose-prod.yml      # Prod overrides (БЕЗ fallback)
├── docker-compose.test.yml      # Test конфиг
├── docker-compose.ai.yml        # AI сервисы (заглушка)
└── dev/
    ├── nginx/default.conf       # Nginx конфиг для backend API
    └── php/Dockerfile           # PHP-FPM image
```

**Принципы проекта:**
- ✅ **Fail-Fast**: Production БЕЗ fallback, dev С fallback
- ✅ **Трехуровневая структура**: base + dev/prod overrides
- ✅ **Environment переменные**: `.env.docker*` для инфраструктуры
- ✅ **Монорепозиторий**: apps/backend и apps/frontend

### Что Нужно Добавить для Frontend

1. **Dockerfile для Development** (Vite dev server с HMR)
2. **Dockerfile для Production** (Multi-stage: build + nginx)
3. **Nginx конфигурация** для SPA (fallback на index.html)
4. **Docker Compose конфигурация** (интеграция с существующей структурой)
5. **Environment переменные** (.env.docker расширение)

---

## Архитектура Решения

### 🎯 Основной Принцип

**Dev и Prod - РАЗНЫЕ подходы!**

| Аспект | Development | Production |
|--------|-------------|------------|
| **Технология** | Vite dev server (Node.js) | Nginx (статика) |
| **Размер** | ~1GB (с node_modules) | ~30MB (nginx + dist/) |
| **Hot Reload** | ✅ Да (HMR) | ❌ Нет |
| **Volumes** | ✅ Да (live code changes) | ❌ Нет (immutable) |
| **Build** | ❌ Нет (on-the-fly) | ✅ Да (npm run build) |
| **Порт** | 3000 (dev server) | 80/443 (nginx) |

### 🏗 Архитектурная Диаграмма

#### Development Режим

```
┌─────────────────────────────────────────────────────────┐
│  Host Machine                                           │
│  ┌───────────────────────────────────────────────────┐  │
│  │  Browser: http://localhost:3000                   │  │
│  └────────────────┬──────────────────────────────────┘  │
│                   │                                      │
└───────────────────┼──────────────────────────────────────┘
                    │ Port 3000
┌───────────────────▼──────────────────────────────────────┐
│  Docker Container: frontend-dev                          │
│  ┌────────────────────────────────────────────────────┐  │
│  │  Vite Dev Server (Node 20)                         │  │
│  │  - HMR (Hot Module Replacement)                    │  │
│  │  - Source maps                                     │  │
│  │  - Fast refresh                                    │  │
│  └────────────────┬───────────────────────────────────┘  │
│                   │                                      │
│  ┌────────────────▼───────────────────────────────────┐  │
│  │  Volume Mount: apps/frontend → /app               │  │
│  │  (Live code changes без rebuild!)                 │  │
│  └────────────────────────────────────────────────────┘  │
└────────────────────┬─────────────────────────────────────┘
                     │ API Proxy: /api → backend-nginx:80
┌────────────────────▼─────────────────────────────────────┐
│  Backend Nginx Container (уже существует)                │
│  http://backend-nginx:80/api/...                         │
└──────────────────────────────────────────────────────────┘
```

#### Production Режим

```
┌─────────────────────────────────────────────────────────┐
│  Host Machine                                           │
│  ┌───────────────────────────────────────────────────┐  │
│  │  Browser: http://localhost:8080                   │  │
│  └────────────────┬──────────────────────────────────┘  │
│                   │                                      │
└───────────────────┼──────────────────────────────────────┘
                    │ Port 80
┌───────────────────▼──────────────────────────────────────┐
│  Docker Container: frontend-prod                         │
│  ┌────────────────────────────────────────────────────┐  │
│  │  Nginx (Alpine ~15MB)                              │  │
│  │  ┌──────────────────────────────────────────────┐  │  │
│  │  │  /usr/share/nginx/html/                      │  │  │
│  │  │  ├── index.html                               │  │  │
│  │  │  ├── js/main-[hash].js (minified)            │  │  │
│  │  │  ├── css/main-[hash].css (purged)            │  │  │
│  │  │  └── assets/                                  │  │  │
│  │  └──────────────────────────────────────────────┘  │  │
│  │                                                     │  │
│  │  Nginx Config:                                     │  │
│  │  - Gzip/Brotli compression                         │  │
│  │  - SPA routing (fallback → index.html)            │  │
│  │  - Cache headers (immutable assets)               │  │
│  │  - Proxy /api → backend-nginx                     │  │
│  └────────────────────────────────────────────────────┘  │
└────────────────────┬─────────────────────────────────────┘
                     │ API Proxy: /api → backend-nginx:80
┌────────────────────▼─────────────────────────────────────┐
│  Backend Nginx Container (уже существует)                │
│  http://backend-nginx:80/api/...                         │
└──────────────────────────────────────────────────────────┘
```

---

## Рекомендации по Реализации

### ✅ Рекомендация 1: Multi-Stage Dockerfile для Production

**Почему?**
- Уменьшение размера финального образа в 30-40 раз (~1GB → ~30MB)
- Безопасность: нет node_modules и исходников в production
- Быстрый деплой и старт контейнера

**Как?**
```dockerfile
# Stage 1: Build (используем Node.js для сборки)
FROM node:20-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production
COPY . .
RUN npm run build  # → создаст dist/

# Stage 2: Production (только nginx + dist/)
FROM nginx:alpine
COPY --from=builder /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
```

**Результат:**
- Builder stage: ~1GB (выбрасывается после build!)
- Final image: ~30MB ✅

---

### ✅ Рекомендация 2: Отдельный Dockerfile для Development

**Почему?**
- Dev режим НЕ нуждается в multi-stage (замедляет rebuild)
- Нужен полный Node.js с npm для HMR
- Volume mount для live reload

**Как?**
```dockerfile
FROM node:20-alpine
WORKDIR /app
# Устанавливаем зависимости
COPY package*.json ./
RUN npm install
# НЕ копируем код! Используем volume mount
EXPOSE 3000
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
```

**Результат:**
- Быстрая разработка с HMR
- Изменения в коде видны мгновенно (без rebuild)

---

### ✅ Рекомендация 3: Nginx Конфигурация для SPA

**Почему?**
- Vue Router использует HTML5 History Mode
- Прямые переходы на `/tasks` или `/calendar` вернут 404 без правильного конфига
- Нужен fallback на `index.html` для всех маршрутов

**Как?**
```nginx
server {
    listen 80;
    server_name localhost;
    root /usr/share/nginx/html;
    index index.html;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
    gzip_min_length 1000;

    # Brotli (если модуль установлен)
    brotli on;
    brotli_types text/plain text/css application/json application/javascript text/xml application/xml;

    # Статические файлы с immutable кешированием
    location ~* \.(?:css|js|jpg|jpeg|gif|png|svg|ico|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Proxy /api к backend
    location /api {
        proxy_pass http://backend-nginx:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # SPA fallback - ВСЕ остальные запросы → index.html
    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

**Результат:**
- Vue Router работает корректно
- API проксируется на backend
- Оптимальное кеширование

---

### ✅ Рекомендация 4: Environment Переменные (Fail-Fast)

**Расширить `.env.docker` для frontend:**

```bash
# Frontend Configuration
FRONTEND_DEV_PORT=3000       # Vite dev server
FRONTEND_PROD_PORT=8080      # Nginx production

# Vite Build Variables (передаем в контейнер)
VITE_API_BASE_URL=http://localhost:8089
```

**В Production БЕЗ fallback!**

```yaml
# docker-compose.app.yml (базовый)
frontend:
  environment:
    VITE_API_BASE_URL: ${VITE_API_BASE_URL}  # БЕЗ fallback!
```

**В Dev С fallback для удобства:**

```yaml
# docker-compose.dev.yml (overrides)
frontend:
  environment:
    VITE_API_BASE_URL: ${VITE_API_BASE_URL:-http://localhost:8089}
```

**Результат:**
- Production упадет если переменная не задана ✅
- Dev работает из коробки с fallback ✅

---

### ✅ Рекомендация 5: Интеграция с Существующей Структурой

**НЕ создавать отдельный docker-compose для frontend!**

Использовать существующую структуру:

```yaml
# infrastructure/docker/docker-compose.app.yml
# Добавить frontend сервис рядом с backend
services:
  # ... существующие сервисы (nginx, php83-fpm, psql16, rabbitmq)

  frontend:
    # Будет переопределяться в dev/prod
    image: placeholder  # Заменится в overrides
    networks:
      - nginx-php83-psql16
```

```yaml
# infrastructure/docker/docker-compose.dev.yml
services:
  frontend:
    build:
      context: ../../apps/frontend
      dockerfile: Dockerfile.dev
    ports:
      - "${FRONTEND_DEV_PORT:-3000}:3000"
    volumes:
      - ../../apps/frontend:/app
      - /app/node_modules  # Не перезаписываем node_modules из контейнера!
    environment:
      - VITE_API_BASE_URL=${VITE_API_BASE_URL:-http://localhost:8089}
```

```yaml
# infrastructure/docker/docker-compose-prod.yml
services:
  frontend:
    build:
      context: ../../apps/frontend
      dockerfile: Dockerfile.prod
    ports:
      - "${FRONTEND_PROD_PORT}:80"
    environment:
      - VITE_API_BASE_URL=${VITE_API_BASE_URL}  # БЕЗ fallback!
```

**Результат:**
- Единая команда для запуска всего проекта
- Соответствие существующим принципам проекта

---

## Детальный План Реализации

### Фаза 1: Подготовка (30 мин)

#### 1.1 Создать Dockerfile.dev

**Файл:** `apps/frontend/Dockerfile.dev`

```dockerfile
# Development Dockerfile для Frontend
# Использует Vite dev server с HMR

FROM node:20-alpine

# Рабочая директория
WORKDIR /app

# Установка зависимостей (кешируется если package.json не изменился)
COPY package.json package-lock.json ./
RUN npm install

# Код НЕ копируем - используем volume mount для live reload!

# Открываем порт Vite dev server
EXPOSE 3000

# Запуск dev server (--host 0.0.0.0 для доступа снаружи контейнера)
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
```

**Почему `--host 0.0.0.0`?**
- По умолчанию Vite слушает только localhost (127.0.0.1)
- Из-за этого host-машина НЕ сможет достучаться до dev server
- `0.0.0.0` разрешает подключения со всех интерфейсов

---

#### 1.2 Создать Dockerfile.prod (Multi-Stage)

**Файл:** `apps/frontend/Dockerfile.prod`

```dockerfile
# Production Dockerfile для Frontend
# Multi-stage build: build → nginx

# ============================================
# Stage 1: Build
# ============================================
FROM node:20-alpine AS builder

WORKDIR /app

# Установка зависимостей
COPY package.json package-lock.json ./
RUN npm ci --only=production --prefer-offline

# Копирование исходников
COPY . .

# Production build
# Создаст директорию dist/ с оптимизированными файлами
RUN npm run build

# Проверка что build прошел успешно
RUN test -d dist || (echo "ERROR: dist/ directory not created!" && exit 1)

# ============================================
# Stage 2: Production (Nginx)
# ============================================
FROM nginx:alpine

# Копируем только собранные файлы из builder stage
COPY --from=builder /app/dist /usr/share/nginx/html

# Копируем nginx конфигурацию
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Проверка health
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD wget --quiet --tries=1 --spider http://localhost/ || exit 1

EXPOSE 80

# Запуск nginx
CMD ["nginx", "-g", "daemon off;"]
```

**Результат:**
- Builder: ~1GB (node + node_modules + src) - выбрасывается!
- Final: ~30MB (nginx + dist/) ✅

---

#### 1.3 Создать Nginx Конфигурацию

**Файл:** `apps/frontend/nginx.conf`

```nginx
# Nginx конфигурация для Vue.js SPA (Production)
# Оптимизировано для performance и SPA routing

server {
    listen 80;
    server_name localhost;

    root /usr/share/nginx/html;
    index index.html;

    # Логи
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log warn;

    # ============================================
    # Gzip Compression
    # ============================================
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript
               application/json application/javascript application/xml+rss
               application/rss+xml font/truetype font/opentype
               application/vnd.ms-fontobject image/svg+xml;
    gzip_min_length 1000;
    gzip_disable "msie6";

    # ============================================
    # Brotli Compression (если модуль установлен)
    # ============================================
    # brotli on;
    # brotli_comp_level 6;
    # brotli_types text/plain text/css application/json application/javascript
    #              text/xml application/xml application/xml+rss text/javascript;

    # ============================================
    # Security Headers
    # ============================================
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # ============================================
    # Статические Файлы с Immutable Кешированием
    # ============================================
    # JS/CSS файлы с хешами (например: main-abc123.js)
    location ~* \.(?:css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Изображения, шрифты
    location ~* \.(?:jpg|jpeg|gif|png|ico|svg|webp|woff|woff2|ttf|eot|otf)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # ============================================
    # Прекешированные файлы PWA (Service Worker)
    # ============================================
    location ~* \.(?:gz|br)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # ============================================
    # Service Worker и Manifest (НЕ кешируем!)
    # ============================================
    location ~* \.(?:sw\.js|workbox.*\.js|manifest\.webmanifest)$ {
        expires -1;
        add_header Cache-Control "no-cache, no-store, must-revalidate";
    }

    # ============================================
    # Proxy /api к Backend
    # ============================================
    location /api {
        # Proxy к backend nginx контейнеру
        proxy_pass http://backend-nginx:80;

        # Headers
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;

        # Buffering
        proxy_buffering off;
        proxy_request_buffering off;
    }

    # ============================================
    # SPA Routing - Fallback на index.html
    # ============================================
    location / {
        # Сначала пробуем найти файл, затем директорию,
        # если не найдено - fallback на index.html
        try_files $uri $uri/ /index.html;

        # НЕ кешируем index.html (может измениться при деплое)
        add_header Cache-Control "no-cache, no-store, must-revalidate";
    }

    # ============================================
    # Обработка Ошибок
    # ============================================
    error_page 404 /index.html;

    error_page 500 502 503 504 /50x.html;
    location = /50x.html {
        root /usr/share/nginx/html;
    }
}
```

**Ключевые моменты:**
- ✅ Gzip compression для всех текстовых файлов
- ✅ Immutable кеширование для файлов с хешами
- ✅ НЕ кешируем index.html и service worker
- ✅ SPA routing (fallback на index.html)
- ✅ Proxy /api → backend
- ✅ Security headers

---

### Фаза 2: Docker Compose Интеграция (20 мин)

#### 2.1 Обновить docker-compose.app.yml (Базовый конфиг)

**Файл:** `infrastructure/docker/docker-compose.app.yml`

**Добавить frontend сервис:**

```yaml
# В конец файла, после rabbitmq и cron сервисов

  # ============================================
  # Frontend Service (Placeholder - переопределяется в dev/prod)
  # ============================================
  frontend:
    image: placeholder  # Будет заменено в docker-compose.dev/prod.yml
    container_name: frontend
    networks:
      - nginx-php83-psql16
    depends_on:
      - nginx  # Backend nginx должен запуститься первым
    # Порты и volumes определены в dev/prod overrides
```

**Почему placeholder?**
- Базовый конфиг НЕ содержит build/image - это добавляется в overrides
- Следуем принципу разделения base (общее) и dev/prod (специфичное)

---

#### 2.2 Создать docker-compose.frontend-dev.yml (Dev Overrides)

**Файл:** `infrastructure/docker/docker-compose.frontend-dev.yml`

```yaml
# Frontend Development Overrides
# Используется вместе с docker-compose.app.yml и docker-compose.dev.yml
# Команда: docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose.dev.yml -f infrastructure/docker/docker-compose.frontend-dev.yml up -d

version: '2'

services:
  frontend:
    build:
      context: ../../apps/frontend
      dockerfile: Dockerfile.dev
    container_name: frontend-dev
    ports:
      # Fallback разрешен ТОЛЬКО для dev!
      - "${FRONTEND_DEV_PORT:-3000}:3000"
    volumes:
      # Volume mount для live code changes
      - ../../apps/frontend:/app
      # Не перезаписываем node_modules из контейнера!
      - /app/node_modules
    environment:
      # Environment переменные с fallback (удобство для dev)
      - VITE_API_BASE_URL=${VITE_API_BASE_URL:-http://localhost:8089}
      - NODE_ENV=development
    networks:
      - nginx-php83-psql16
    depends_on:
      - nginx
```

**Ключевые моменты:**
- ✅ Volume mount для live reload
- ✅ `/app/node_modules` - не затираем установленные в контейнере пакеты
- ✅ Fallback для удобства (следуем принципам проекта)
- ✅ Порт 3000 (Vite dev server)

---

#### 2.3 Создать docker-compose.frontend-prod.yml (Prod Overrides)

**Файл:** `infrastructure/docker/docker-compose.frontend-prod.yml`

```yaml
# Frontend Production Overrides
# Используется вместе с docker-compose.app.yml и docker-compose-prod.yml
# Команда: docker-compose -f docker-compose.yml -f infrastructure/docker/docker-compose-prod.yml -f infrastructure/docker/docker-compose.frontend-prod.yml up -d

version: '2'

services:
  frontend:
    build:
      context: ../../apps/frontend
      dockerfile: Dockerfile.prod
      # Build args (если нужно передать во время сборки)
      args:
        - VITE_API_BASE_URL=${VITE_API_BASE_URL}
    container_name: frontend-prod
    ports:
      # БЕЗ fallback - Fail-Fast принцип!
      - "${FRONTEND_PROD_PORT}:80"
    # НЕТ volumes в production - immutable контейнер!
    environment:
      # Environment переменные БЕЗ fallback (Fail-Fast!)
      - VITE_API_BASE_URL=${VITE_API_BASE_URL}
      - NODE_ENV=production
    networks:
      - nginx-php83-psql16
    depends_on:
      - nginx
    restart: unless-stopped  # Автоматический перезапуск в production
```

**Ключевые моменты:**
- ✅ НЕТ volumes - immutable контейнер
- ✅ БЕЗ fallback - Fail-Fast!
- ✅ Порт 80 (nginx)
- ✅ Автоматический restart

---

### Фаза 3: Environment Переменные (15 мин)

#### 3.1 Обновить .env.docker.example

**Файл:** `.env.docker.example`

**Добавить секцию Frontend:**

```bash
# ... существующие переменные (PostgreSQL, RabbitMQ, etc.)

# ============================================
# Frontend Configuration
# ============================================
# Development
FRONTEND_DEV_PORT=3000

# Production
FRONTEND_PROD_PORT=8080

# Vite Environment Variables
# ВАЖНО: В production ОБЯЗАТЕЛЬНО переопределить через .env.docker.prod!
VITE_API_BASE_URL=http://localhost:8089
```

---

#### 3.2 Создать apps/frontend/.env.docker

**Файл:** `apps/frontend/.env.docker` (для dev окружения)

```bash
# Frontend Development Environment
# Эти переменные передаются в контейнер через docker-compose

VITE_API_BASE_URL=http://localhost:8089
VITE_API_TIMEOUT=5000
```

---

#### 3.3 Создать apps/frontend/.env.docker.prod

**Файл:** `apps/frontend/.env.docker.prod` (НЕ коммитить!)

```bash
# Frontend Production Environment
# ⚠️ БЕЗ fallback - Fail-Fast принцип!

# API URL (ОБЯЗАТЕЛЬНО переопределить для production!)
VITE_API_BASE_URL=https://api.production.com

# Feature Flags
VITE_ENABLE_ANALYTICS=true
VITE_ENABLE_DEBUG=false

# Build Optimization
VITE_SOURCEMAP=false
VITE_DROP_CONSOLE=true
```

---

#### 3.4 Обновить .gitignore

**Добавить:**

```gitignore
# Frontend Docker env files
apps/frontend/.env.docker
apps/frontend/.env.docker.prod
apps/frontend/.env.docker.test
```

---

### Фаза 4: Обновление Документации (20 мин)

#### 4.1 Обновить docs/guides/DEVELOPMENT_WORKFLOW.md

**Добавить секцию "Запуск Frontend":**

```markdown
### Запуск Frontend

#### Development (с Hot Reload)

bash
# Из корня проекта
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.dev.yml \
  -f infrastructure/docker/docker-compose.frontend-dev.yml up -d frontend

# Проверка логов
docker logs -f frontend-dev


#### Production (статика через nginx)

bash
# Из корня проекта
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml up -d frontend

# Проверка
curl http://localhost:8080


#### Полный Стек (Backend + Frontend)

bash
# Development
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.dev.yml \
  -f infrastructure/docker/docker-compose.frontend-dev.yml up -d

# Production
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml up -d

```

---

#### 4.2 Обновить docs/INDEX.md

**Добавить ссылку на новый документ:**

```markdown
### 🚀 CI/CD & Планы Оптимизации (`project/docs/ci-cd-plans/`)

#### [`ci-cd-plans/FRONTEND_OPTIMIZATION_PLAN.md`](ci-cd-plans/FRONTEND_OPTIMIZATION_PLAN.md)
План оптимизации производительности Frontend

#### [`ci-cd-plans/FRONTEND_DOCKERIZATION_PLAN.md`](ci-cd-plans/FRONTEND_DOCKERIZATION_PLAN.md) 🆕
**НОВОЕ** - План докеризации Frontend с разделением dev/prod окружений
```

---

## Структура Файлов (После Реализации)

```
test_sonnet45/
├── .env.docker.example                   # ← Обновлен (добавлены FRONTEND_*)
├── .gitignore                            # ← Обновлен (apps/frontend/.env.docker*)
│
├── apps/frontend/
│   ├── Dockerfile.dev                    # ← НОВЫЙ (Vite dev server)
│   ├── Dockerfile.prod                   # ← НОВЫЙ (Multi-stage: build + nginx)
│   ├── nginx.conf                        # ← НОВЫЙ (Nginx конфигурация для SPA)
│   ├── .env.docker                       # ← НОВЫЙ (dev env, НЕ в git)
│   ├── .env.docker.prod                  # ← НОВЫЙ (prod env, НЕ в git)
│   ├── .env.docker.example               # ← НОВЫЙ (шаблон)
│   ├── package.json
│   ├── vite.config.ts
│   └── src/
│
├── infrastructure/docker/
│   ├── docker-compose.app.yml            # ← Обновлен (добавлен frontend placeholder)
│   ├── docker-compose.frontend-dev.yml   # ← НОВЫЙ (dev overrides)
│   ├── docker-compose.frontend-prod.yml  # ← НОВЫЙ (prod overrides)
│   ├── docker-compose.dev.yml            # Существующий (backend dev)
│   ├── docker-compose-prod.yml           # Существующий (backend prod)
│   └── dev/
│       └── nginx/default.conf            # Существующий (backend nginx)
│
└── docs/
    ├── INDEX.md                          # ← Обновлен (добавлена ссылка)
    ├── guides/
    │   └── DEVELOPMENT_WORKFLOW.md       # ← Обновлен (добавлены команды)
    └── ci-cd-plans/
        ├── FRONTEND_OPTIMIZATION_PLAN.md # Существующий
        └── FRONTEND_DOCKERIZATION_PLAN.md # ← ЭТОТ ФАЙЛ
```

---

## Environment Переменные

### .env.docker (Корень проекта)

```bash
# ============================================
# Backend Configuration (существующие)
# ============================================
POSTGRES_DB=backend-app
POSTGRES_USER=user
POSTGRES_PASSWORD=password
POSTGRES_PORT=15432

RABBITMQ_USER=user
RABBITMQ_PASSWORD=password
RABBITMQ_PORT=5672
RABBITMQ_MANAGEMENT_PORT=15672

NGINX_PORT=8089
PHP_FPM_PORT=9009

# ============================================
# Frontend Configuration (НОВЫЕ)
# ============================================
FRONTEND_DEV_PORT=3000
FRONTEND_PROD_PORT=8080

VITE_API_BASE_URL=http://localhost:8089
```

### .env.docker.prod (Production)

```bash
# ============================================
# Backend Production (существующие)
# ============================================
POSTGRES_DB=backend_prod
POSTGRES_USER=prod_user
POSTGRES_PASSWORD=CHANGE_ME_IN_PRODUCTION
POSTGRES_PORT=5432

# ... остальные backend переменные

# ============================================
# Frontend Production (НОВЫЕ)
# ============================================
FRONTEND_PROD_PORT=80

# ⚠️ КРИТИЧНО - БЕЗ fallback!
VITE_API_BASE_URL=https://api.production.com
```

---

## Чек-лист Реализации

### Фаза 1: Подготовка Dockerfiles (30 мин)

- [ ] **1.1** Создать `apps/frontend/Dockerfile.dev`
  - [ ] Тестировать: `cd apps/frontend && docker build -f Dockerfile.dev -t frontend-dev .`
  - [ ] Проверить размер: `docker images frontend-dev`

- [ ] **1.2** Создать `apps/frontend/Dockerfile.prod`
  - [ ] Тестировать multi-stage build
  - [ ] Проверить что dist/ создается
  - [ ] Проверить размер финального образа (~30MB)

- [ ] **1.3** Создать `apps/frontend/nginx.conf`
  - [ ] Проверить синтаксис: `nginx -t`

### Фаза 2: Docker Compose Интеграция (20 мин)

- [ ] **2.1** Обновить `infrastructure/docker/docker-compose.app.yml`
  - [ ] Добавить frontend placeholder

- [ ] **2.2** Создать `infrastructure/docker/docker-compose.frontend-dev.yml`
  - [ ] Проверить синтаксис: `docker-compose -f docker-compose.yml -f ... config`

- [ ] **2.3** Создать `infrastructure/docker/docker-compose.frontend-prod.yml`
  - [ ] Проверить синтаксис

### Фаза 3: Environment Переменные (15 мин)

- [ ] **3.1** Обновить `.env.docker.example`
  - [ ] Добавить FRONTEND_* переменные

- [ ] **3.2** Создать `apps/frontend/.env.docker.example`

- [ ] **3.3** Обновить `.gitignore`
  - [ ] Добавить `apps/frontend/.env.docker*`

### Фаза 4: Тестирование (30 мин)

- [ ] **4.1** Тестировать Dev режим
  ```bash
  docker-compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose.dev.yml \
    -f infrastructure/docker/docker-compose.frontend-dev.yml up -d frontend
  ```
  - [ ] Проверить http://localhost:3000
  - [ ] Проверить Hot Reload (изменить файл в src/)
  - [ ] Проверить что /api проксируется на backend
  - [ ] Проверить логи: `docker logs -f frontend-dev`

- [ ] **4.2** Тестировать Prod режим
  ```bash
  docker-compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose-prod.yml \
    -f infrastructure/docker/docker-compose.frontend-prod.yml up -d frontend
  ```
  - [ ] Проверить http://localhost:8080
  - [ ] Проверить SPA routing (перейти на /tasks напрямую)
  - [ ] Проверить Network tab (gzip, кеширование)
  - [ ] Проверить размер контейнера: `docker images frontend-prod`

- [ ] **4.3** Тестировать Fail-Fast в Production
  ```bash
  # Удалить переменную и проверить что падает
  unset VITE_API_BASE_URL
  docker-compose -f docker-compose.yml -f ... up -d
  # Должна быть ОШИБКА!
  ```

- [ ] **4.4** Тестировать Полный Стек (Backend + Frontend)
  ```bash
  # Development
  docker-compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose.dev.yml \
    -f infrastructure/docker/docker-compose.frontend-dev.yml up -d
  ```
  - [ ] Проверить http://localhost:3000 (frontend)
  - [ ] Проверить http://localhost:8089/api (backend)
  - [ ] Проверить логи всех контейнеров
  - [ ] Тестировать полный user flow

### Фаза 5: Документация (20 мин)

- [ ] **5.1** Обновить `docs/guides/DEVELOPMENT_WORKFLOW.md`
  - [ ] Добавить секцию "Запуск Frontend"

- [ ] **5.2** Обновить `docs/INDEX.md`
  - [ ] Добавить ссылку на FRONTEND_DOCKERIZATION_PLAN.md

- [ ] **5.3** Создать `apps/frontend/.env.docker.example`
  - [ ] С комментариями для каждой переменной

### Финальная Фаза: Коммит (10 мин)

- [ ] **6.1** Проверить что все работает
  - [ ] Dev режим запускается
  - [ ] Prod режим запускается
  - [ ] Нет sensitive данных в git

- [ ] **6.2** Коммит изменений
  ```bash
  git add apps/frontend/Dockerfile.dev
  git add apps/frontend/Dockerfile.prod
  git add apps/frontend/nginx.conf
  git add apps/frontend/.env.docker.example
  git add infrastructure/docker/docker-compose.app.yml
  git add infrastructure/docker/docker-compose.frontend-dev.yml
  git add infrastructure/docker/docker-compose.frontend-prod.yml
  git add .env.docker.example
  git add .gitignore
  git add docs/ci-cd-plans/FRONTEND_DOCKERIZATION_PLAN.md
  git add docs/INDEX.md
  git add docs/guides/DEVELOPMENT_WORKFLOW.md

  git commit -m "feat(docker): add frontend dockerization (dev + prod)

  - Created Dockerfile.dev (Vite dev server with HMR)
  - Created Dockerfile.prod (Multi-stage: build + nginx)
  - Added nginx.conf for SPA routing and optimization
  - Integrated with existing docker-compose structure
  - Following Fail-Fast principle (prod without fallback)
  - Updated documentation (DEVELOPMENT_WORKFLOW.md, INDEX.md)

  Dev: docker-compose -f ... -f docker-compose.frontend-dev.yml up -d
  Prod: docker-compose -f ... -f docker-compose.frontend-prod.yml up -d"
  ```

---

## 📊 Ожидаемые Результаты

### Размеры Docker Images

| Image | Размер | Комментарий |
|-------|--------|-------------|
| **frontend-dev** | ~1.0 GB | Node.js + node_modules + dev tools |
| **frontend-prod** | ~30 MB | Только nginx + dist/ ✅ |
| **Builder stage** (выбрасывается) | ~1.0 GB | Не влияет на финальный размер |

### Производительность

| Метрика | Локально (npm) | Docker Dev | Docker Prod |
|---------|----------------|------------|-------------|
| **Старт** | 2-3s | 3-5s | <1s ✅ |
| **Hot Reload** | Мгновенно | Мгновенно ✅ | N/A |
| **Build Time** | 15-20s | 15-20s | 15-20s |
| **Размер** | ~1GB | ~1GB | ~30MB ✅ |

### Удобство Разработки

- ✅ Единая команда для запуска всего стека
- ✅ Hot Reload работает в Docker
- ✅ Не нужен Node.js на хост-машине
- ✅ Одинаковое окружение у всех разработчиков
- ✅ Production-ready сборка с оптимизациями

---

## 🛠 Команды для Разработки

### Запуск

```bash
# Только Frontend (Dev)
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.dev.yml \
  -f infrastructure/docker/docker-compose.frontend-dev.yml up -d frontend

# Только Frontend (Prod)
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml up -d frontend

# Полный стек (Backend + Frontend) Dev
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.dev.yml \
  -f infrastructure/docker/docker-compose.frontend-dev.yml up -d

# Полный стек Production
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml up -d
```

### Логи

```bash
# Frontend логи
docker logs -f frontend-dev    # Dev
docker logs -f frontend-prod   # Prod

# Все логи
docker-compose logs -f
```

### Rebuild

```bash
# Rebuild frontend dev
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose.dev.yml \
  -f infrastructure/docker/docker-compose.frontend-dev.yml build --no-cache frontend

# Rebuild frontend prod
docker-compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml build --no-cache frontend
```

### Остановка

```bash
# Остановить frontend
docker-compose down frontend

# Остановить все
docker-compose down
```

---

## 🔗 Полезные Ресурсы

- [Docker Multi-Stage Builds](https://docs.docker.com/build/building/multi-stage/)
- [Nginx SPA Configuration](https://www.nginx.com/blog/creating-nginx-rewrite-rules/)
- [Vite Docker Setup](https://vitejs.dev/guide/docker.html)
- [Docker Compose Include](https://docs.docker.com/compose/compose-file/14-include/)

---

## 📝 Примечания

### Важно!

1. **Volume Mount в Dev**
   - `/app/node_modules` - не перезаписываем из host!
   - Без этого node_modules исчезнут и контейнер упадет

2. **Vite Host**
   - `--host 0.0.0.0` обязателен для доступа снаружи
   - Иначе dev server будет слушать только 127.0.0.1

3. **Nginx Try Files**
   - `try_files $uri $uri/ /index.html` - критично для SPA
   - Без этого прямые переходы на routes вернут 404

4. **Fail-Fast**
   - Production БЕЗ fallback для VITE_API_BASE_URL
   - Dev С fallback для удобства

5. **Multi-Stage Размер**
   - Builder stage выбрасывается после build
   - Только nginx + dist/ попадают в финальный образ

---

## 🚀 Следующие Шаги

1. **Реализовать по плану**
   - Начать с Фазы 1 (Dockerfiles)
   - Тестировать после каждой фазы

2. **Интеграция с CI/CD**
   - Настроить GitHub Actions для автоматической сборки
   - Deploy в Docker Registry

3. **Продакшен деплой**
   - Настроить Kubernetes/Docker Swarm
   - Мониторинг и логи

---

**Последнее обновление**: 2025-11-13
**Автор**: Claude Code AI
**Версия**: 1.0

---

> 💡 **Совет**: Начните с Dev режима - это даст быстрый результат и понимание что всё работает правильно!
