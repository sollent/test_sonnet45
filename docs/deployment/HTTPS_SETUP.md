# 🔐 Настройка HTTPS и SSL на Production VDS

> **Руководство по настройке Let's Encrypt SSL сертификатов и Nginx для production deployment**
> **Версия**: 1.2
> **Дата**: 2025-11-14

---

## 📋 Содержание

1. [Обзор](#обзор)
2. [Требования](#требования)
3. [Архитектура](#архитектура)
4. [Настройка DNS](#настройка-dns)
5. [Генерация SSL Сертификатов](#генерация-ssl-сертификатов)
6. [Конфигурация Nginx](#конфигурация-nginx)
7. [Обновление Docker Контейнеров](#обновление-docker-контейнеров)
8. [Проверка Работоспособности](#проверка-работоспособности)
9. [Автоматическое Обновление Сертификатов](#автоматическое-обновление-сертификатов)
10. [Troubleshooting](#troubleshooting)

---

## Обзор

После развертывания приложения на VDS с помощью `scripts/deploy-production.sh`, необходимо настроить HTTPS для:

- ✅ Безопасного соединения (шифрование трафика)
- ✅ Работы PWA Service Worker (требует HTTPS)
- ✅ SEO и доверия пользователей
- ✅ Защиты от MITM атак

### Что Будет Настроено

**Домены:**
- `https://task.nesty.by` - Frontend приложение
- `https://api.task.nesty.by` - Backend API

**Функции:**
- HTTP → HTTPS автоматический редирект
- www → основной домен редирект
- CORS для кросс-доменных запросов
- Security headers (HSTS, X-Frame-Options, etc.)
- Автоматическое обновление SSL сертификатов

---

## Требования

### Предварительные Условия

**1. VDS Сервер:**
- Ubuntu 20.04+ / Debian 11+
- Root доступ
- Минимум 1GB RAM

**2. Домен:**
- Зарегистрированный домен (например: `nesty.by`)
- Доступ к DNS настройкам

**3. Установленное ПО:**
```bash
# Nginx
nginx -v
# nginx version: nginx/1.18.0 или выше

# Certbot
certbot --version
# certbot 1.21.0 или выше

# Docker
docker --version
# Docker version 20.10+ или выше
```

**4. Открытые Порты:**
```bash
# Проверка портов
sudo netstat -tlnp | grep -E ':(80|443)'

# Должны быть открыты:
# - Port 80 (HTTP)
# - Port 443 (HTTPS)
```

### Установка Certbot (Если Не Установлен)

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install certbot python3-certbot-nginx -y

# Проверка установки
certbot --version
```

---

## Архитектура

### До Настройки HTTPS

```
HTTP Request (port 80)
    ↓
VDS IP: 45.129.186.88
    ↓
Docker Containers:
    - frontend-prod (localhost:8000)
    - backend-nginx (localhost:8089)
```

**Проблемы:**
- ❌ Незащищенное соединение
- ❌ PWA не работает (требует HTTPS)
- ❌ Браузеры показывают "Not Secure"
- ❌ Доступ только по IP адресу (http://45.129.186.88)

### После Настройки HTTPS

```
HTTPS Request (port 443)
    ↓
Nginx (VDS) + SSL Termination
    ↓
Proxy Pass:
    - task.nesty.by → localhost:8000 (frontend-prod)
    - api.task.nesty.by → localhost:8089 (backend-nginx)
    ↓
Docker Containers (внутренняя сеть)
```

**Результат:**
- ✅ Шифрованное соединение
- ✅ PWA Service Worker работает
- ✅ Зеленый замочек в браузере
- ✅ Автоматический HTTP → HTTPS редирект

---

## Настройка DNS

### Шаг 1: Добавьте A-Записи

**В панели управления DNS вашего регистратора добавьте:**

| Тип | Имя | Значение | TTL |
|-----|-----|----------|-----|
| A | `task.nesty.by` | `45.129.186.88` | 3600 |
| A | `www.task.nesty.by` | `45.129.186.88` | 3600 |
| A | `api.task.nesty.by` | `45.129.186.88` | 3600 |
| A | `www.api.task.nesty.by` | `45.129.186.88` | 3600 |

**Замените:**
- `45.129.186.88` → IP адрес вашего VDS
- `nesty.by` → ваш домен

### Шаг 2: Проверьте Распространение DNS

**Подождите 5-30 минут**, затем проверьте:

```bash
# Проверка DNS
dig task.nesty.by +short
# Должно вернуть: 45.129.186.88

dig api.task.nesty.by +short
# Должно вернуть: 45.129.186.88

# Альтернативная проверка
nslookup task.nesty.by
nslookup api.task.nesty.by
```

**Глобальная проверка:**
- https://dnschecker.org/#A/task.nesty.by
- https://dnschecker.org/#A/api.task.nesty.by

---

## Генерация SSL Сертификатов

### Подготовка Nginx для Let's Encrypt

**Создайте snippet для webroot:**

```bash
sudo mkdir -p /var/lib/letsencrypt/.well-known/acme-challenge
sudo chown -R www-data:www-data /var/lib/letsencrypt
sudo chmod -R 755 /var/lib/letsencrypt
```

**Создайте `/etc/nginx/snippets/letsencrypt.conf`:**

```bash
sudo nano /etc/nginx/snippets/letsencrypt.conf
```

**Содержимое:**

```nginx
location ^~ /.well-known/acme-challenge/ {
    allow all;
    root /var/lib/letsencrypt/;
    default_type "text/plain";
    try_files $uri =404;
}
```

**Создайте `/etc/nginx/snippets/ssl.conf`:**

```bash
sudo nano /etc/nginx/snippets/ssl.conf
```

**Содержимое:**

```nginx
ssl_dhparam /etc/ssl/certs/dhparam.pem;

ssl_session_timeout 1d;
ssl_session_cache shared:SSL:10m;
ssl_session_tickets off;

ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384;
ssl_prefer_server_ciphers off;

ssl_stapling on;
ssl_stapling_verify on;
resolver 8.8.8.8 8.8.4.4 valid=300s;
resolver_timeout 30s;

add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Frame-Options SAMEORIGIN always;
add_header X-Content-Type-Options nosniff always;
```

**Сгенерируйте Diffie-Hellman параметры:**

```bash
sudo openssl dhparam -out /etc/ssl/certs/dhparam.pem 2048
```

### Получение Сертификатов

**1. Frontend (`task.nesty.by`):**

```bash
sudo certbot certonly --agree-tos \
  --email your-email@example.com \
  --webroot -w /var/lib/letsencrypt/ \
  -d task.nesty.by \
  -d www.task.nesty.by
```

**2. Backend API (`api.task.nesty.by`):**

```bash
sudo certbot certonly --agree-tos \
  --email your-email@example.com \
  --webroot -w /var/lib/letsencrypt/ \
  -d api.task.nesty.by \
  -d www.api.task.nesty.by
```

**Успешный вывод:**

```
Successfully received certificate.
Certificate is saved at: /etc/letsencrypt/live/task.nesty.by/fullchain.pem
Key is saved at:         /etc/letsencrypt/live/task.nesty.by/privkey.pem
This certificate expires on 2026-02-12.
```

**Проверка сертификатов:**

```bash
sudo certbot certificates

# Должно показать:
# Certificate Name: task.nesty.by
#   Domains: task.nesty.by www.task.nesty.by
#   Expiry Date: 2026-02-12 ...
#
# Certificate Name: api.task.nesty.by
#   Domains: api.task.nesty.by www.api.task.nesty.by
#   Expiry Date: 2026-02-12 ...
```

---

## Конфигурация Nginx

### Frontend Конфигурация (`task.nesty.by`)

**Создайте файл:**

```bash
sudo nano /etc/nginx/sites-available/task.nesty.by.conf
```

**Содержимое:**

```nginx
# HTTP → HTTPS редирект для task.nesty.by
server {
    listen 80;
    listen [::]:80;
    server_name task.nesty.by www.task.nesty.by;

    # Let's Encrypt webroot для обновлений сертификатов
    include snippets/letsencrypt.conf;

    # Редирект всего остального на HTTPS
    location / {
        return 301 https://task.nesty.by$request_uri;
    }
}

# HTTPS сервер для Frontend
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name task.nesty.by www.task.nesty.by;

    # SSL сертификаты
    ssl_certificate /etc/letsencrypt/live/task.nesty.by/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/task.nesty.by/privkey.pem;
    ssl_trusted_certificate /etc/letsencrypt/live/task.nesty.by/chain.pem;

    # SSL конфигурация из snippets
    include snippets/ssl.conf;

    # Логи
    access_log /var/log/nginx/task.nesty.by.access.log;
    error_log /var/log/nginx/task.nesty.by.error.log;

    # Редирект www на основной домен
    if ($host = www.task.nesty.by) {
        return 301 https://task.nesty.by$request_uri;
    }

    # Proxy к Docker Frontend контейнеру (frontend-prod на порту 8000)
    location / {
        proxy_pass http://localhost:8000;
        proxy_http_version 1.1;

        # Заголовки для корректной работы приложения
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;

        # WebSocket support (если понадобится в будущем)
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";

        # Timeouts
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;

        # Buffering
        proxy_buffering off;
        proxy_request_buffering off;
    }

    # Дополнительные security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
}
```

### Backend API Конфигурация (`api.task.nesty.by`)

**Создайте файл:**

```bash
sudo nano /etc/nginx/sites-available/api.task.nesty.by.conf
```

**⚠️ ВАЖНО: CORS Headers**

**НЕ добавляйте CORS headers в nginx!** Они должны управляться **только** в Symfony через `nelmio_cors` bundle. Добавление CORS headers в оба места (nginx + Symfony) приведет к дублированию и ошибке:

```
The 'Access-Control-Allow-Origin' header contains multiple values
'https://task.nesty.by, https://task.nesty.by', but only one is allowed.
```

**Рекомендуемая конфигурация (БЕЗ CORS headers):**

```nginx
# HTTP → HTTPS редирект для api.task.nesty.by
server {
    listen 80;
    listen [::]:80;
    server_name api.task.nesty.by www.api.task.nesty.by;

    # Let's Encrypt webroot для обновлений сертификатов
    include snippets/letsencrypt.conf;

    # Редирект всего остального на HTTPS
    location / {
        return 301 https://api.task.nesty.by$request_uri;
    }
}

# HTTPS сервер для Backend API
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.task.nesty.by www.api.task.nesty.by;

    # SSL сертификаты
    ssl_certificate /etc/letsencrypt/live/api.task.nesty.by/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.task.nesty.by/privkey.pem;
    ssl_trusted_certificate /etc/letsencrypt/live/api.task.nesty.by/chain.pem;

    # SSL конфигурация из snippets
    include snippets/ssl.conf;

    # Логи
    access_log /var/log/nginx/api.task.nesty.by.access.log;
    error_log /var/log/nginx/api.task.nesty.by.error.log;

    # Редирект www на основной домен
    if ($host = www.api.task.nesty.by) {
        return 301 https://api.task.nesty.by$request_uri;
    }

    # Security headers (НЕ CORS!)
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Proxy к Docker Backend контейнеру (backend-nginx на порту 8089)
    location / {
        proxy_pass http://localhost:8089;
        proxy_http_version 1.1;

        # Заголовки для корректной работы Symfony
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;

        # Timeouts для API (увеличены для долгих запросов)
        proxy_connect_timeout 120s;
        proxy_send_timeout 120s;
        proxy_read_timeout 120s;

        # Buffering для больших JSON ответов
        proxy_buffering on;
        proxy_buffer_size 4k;
        proxy_buffers 8 4k;
        proxy_busy_buffers_size 8k;
    }
}
```

**Почему CORS в Symfony, а не в Nginx?**

1. **Symfony nelmio_cors** правильно обрабатывает preflight OPTIONS запросы
2. **Избегается дублирование** headers (nginx + Symfony = ошибка браузера)
3. **Централизованное управление** CORS политикой в коде приложения
4. **Меньше конфигурации** в nginx (проще поддерживать)

### Активация Конфигураций

```bash
# Создайте symlinks в sites-enabled
sudo ln -sf /etc/nginx/sites-available/task.nesty.by.conf /etc/nginx/sites-enabled/
sudo ln -sf /etc/nginx/sites-available/api.task.nesty.by.conf /etc/nginx/sites-enabled/

# Удалите дефолтный конфиг (опционально)
sudo rm -f /etc/nginx/sites-enabled/default

# Проверьте синтаксис
sudo nginx -t

# Перезагрузите nginx
sudo systemctl reload nginx
```

---

## Полная Пересборка Проекта

После настройки HTTPS и обновления доменов необходимо пересобрать проект с новыми переменными окружения.

### Подготовка: Обновление Переменных Окружения

**На VDS в директории проекта:**

```bash
cd /var/www/test_sonnet45  # или ваша директория проекта

# Отредактируйте .env.docker.prod
nano .env.docker.prod
```

**Обновите VITE_API_BASE_URL и порты:**

```bash
# PostgreSQL Configuration
POSTGRES_DB=backend_prod
POSTGRES_USER=prod_user
POSTGRES_PASSWORD=your_secure_password  # Из generate-secrets.sh

# RabbitMQ Configuration
RABBITMQ_USER=prod_user
RABBITMQ_PASSWORD=your_secure_password  # Из generate-secrets.sh

# Nginx Configuration (Docker internal ports exposed to host)
NGINX_PORT=8089
PHP_FPM_PORT=9009

# Frontend Production Configuration (Docker internal port exposed to host)
FRONTEND_PROD_PORT=8000

# ВАЖНО! Новый API URL с HTTPS
VITE_API_BASE_URL=https://api.task.nesty.by
```

**Создайте symlink для Docker Compose:**

```bash
# Docker Compose будет использовать .env.docker.prod
ln -sf .env.docker.prod .env.docker
```

### Вариант 1: Автоматическая Пересборка (Рекомендуется)

**Используйте готовый скрипт `deploy-production.sh`:**

```bash
# Запустите deployment скрипт
./scripts/deploy-production.sh
```

**Скрипт автоматически выполнит:**

1. ✅ Проверку окружения (Docker, Docker Compose)
2. ✅ Создание/проверку `.env.docker.prod`
3. ✅ Остановку старых контейнеров
4. ✅ Сборку production образов (backend + frontend)
5. ✅ Запуск всех сервисов
6. ✅ Установку зависимостей (`composer install --no-dev`)
7. ✅ Применение миграций базы данных
8. ✅ Генерацию JWT ключей (если отсутствуют)
9. ✅ Проверку здоровья сервисов

**Ожидаемый вывод:**

```
================================================
Развертывание завершено! 🎉
================================================

✓ Frontend:  https://task.nesty.by
✓ Backend:   https://api.task.nesty.by
```

### Вариант 2: Пошаговая Пересборка (Ручной Контроль)

**Если нужен больший контроль над процессом:**

#### Шаг 1: Остановка Всех Контейнеров

```bash
# Остановить весь стек
docker compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml \
  down
```

#### Шаг 2: Пересборка Backend

```bash
# Собрать backend образ
docker compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  build --no-cache php83-fpm nginx
```

#### Шаг 3: Пересборка Frontend

```bash
# Собрать frontend образ с новым VITE_API_BASE_URL
docker compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml \
  build --no-cache frontend
```

**⚠️ Важно:** Frontend использует `VITE_API_BASE_URL` **во время сборки** (build-time variable). Vite встраивает его в JavaScript bundle.

#### Шаг 4: Запуск Всех Сервисов

```bash
# Запустить весь стек
docker compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml \
  up -d

# Подождать инициализации
sleep 15
```

#### Шаг 5: Установка Зависимостей

```bash
# Composer для backend
docker exec backend-php83 composer install \
  --optimize-autoloader \
  --no-dev \
  --no-interaction
```

#### Шаг 6: Миграции Базы Данных

```bash
# Создать базу данных (если не существует)
docker exec backend-php83 php bin/console \
  doctrine:database:create \
  --if-not-exists \
  --env=prod

# Применить миграции
docker exec backend-php83 php bin/console \
  doctrine:migrations:migrate \
  --no-interaction \
  --env=prod
```

#### Шаг 7: Генерация JWT Ключей

```bash
# Проверка существующих ключей
docker exec backend-php83 test -f config/jwt/private.pem && \
docker exec backend-php83 test -f config/jwt/public.pem && \
echo "JWT ключи уже существуют" || \
echo "JWT ключи отсутствуют - нужна генерация"

# Если отсутствуют - сгенерировать
docker exec backend-php83 php bin/console \
  lexik:jwt:generate-keypair \
  --skip-if-exists
```

#### Шаг 8: Проверка Статуса

```bash
# Статус всех контейнеров
docker ps --filter "name=backend-" --filter "name=frontend-"

# Должны увидеть:
# frontend-prod   Up X seconds (healthy)
# backend-php83   Up X seconds (healthy)
# backend-nginx   Up X seconds (healthy)
# backend-psql16  Up X seconds (healthy)
# backend-rabbitmq Up X seconds (healthy)
```

### Вариант 3: Пересборка Только Frontend (Быстрое Обновление)

**Если изменился только API URL или frontend код:**

```bash
# Остановить frontend
docker compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml \
  down frontend

# Пересобрать с новым API URL (БЕЗ кеша!)
docker compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml \
  build --no-cache frontend

# Запустить
docker compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  -f infrastructure/docker/docker-compose.frontend-prod.yml \
  up -d frontend

# Проверить статус
docker ps | grep frontend-prod
```

### Обновление CORS в Backend

**ВАЖНО:** CORS должен управляться **ТОЛЬКО** в Symfony через `nelmio_cors` bundle. **НЕ добавляйте** CORS headers в nginx конфигурацию!

**Отредактируйте конфигурацию:**

```bash
nano apps/backend/config/packages/nelmio_cors.yaml
```

**Обновите конфигурацию:**

```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        # Production: Только разрешенные домены (НЕ *)
        # Development: localhost:3000 для локальной разработки
        # Production: task.nesty.by для боевого сервера
        allow_origin:
            - '^https?://localhost(:[0-9]+)?$'  # Local dev
            - '^https://task\.nesty\.by$'        # Production frontend
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization', 'X-Requested-With', 'Cache-Control']
        expose_headers: ['Link', 'Content-Length', 'Content-Range']
        allow_credentials: true
        max_age: 3600
    paths:
        '^/api':
            allow_origin:
                - '^https?://localhost(:[0-9]+)?$'
                - '^https://task\.nesty\.by$'
            allow_headers: ['*']
            allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
            allow_credentials: true
            max_age: 3600
```

**Ключевые изменения:**

- ✅ `allow_origin` использует regex паттерны для localhost и production
- ✅ `allow_credentials: true` для поддержки cookies/JWT
- ✅ `expose_headers` расширен для корректной работы API
- ✅ Отдельная секция `paths` для `/api` с дополнительными правилами

**Перезапустите backend контейнеры:**

```bash
# Если использовали Вариант 1 (скрипт) - не нужно, уже перезапущено

# Если использовали Вариант 2/3 - перезапустите
docker compose -f docker-compose.yml \
  -f infrastructure/docker/docker-compose-prod.yml \
  restart php83-fpm nginx

# Очистите cache
docker exec backend-php83 php bin/console cache:clear --env=prod
```

### Проверка Успешной Пересборки

**1. Проверьте что контейнеры запущены:**

```bash
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
```

**2. Проверьте переменные окружения frontend:**

```bash
docker exec frontend-prod env | grep VITE_API_BASE_URL
# Должно вернуть: VITE_API_BASE_URL=https://api.task.nesty.by
```

**3. Проверьте CORS настройки backend:**

```bash
docker exec backend-php83 cat config/packages/nelmio_cors.yaml | grep allow_origin
# Должно содержать: https://task.nesty.by
```

**4. Проверьте логи контейнеров:**

```bash
# Frontend логи
docker logs frontend-prod --tail 20

# Backend логи
docker logs backend-php83 --tail 20
docker logs backend-nginx --tail 20
```

**5. Проверьте доступность приложения:**

```bash
# Frontend должен вернуть HTML
curl -I https://task.nesty.by

# Backend API должен вернуть JSON
curl https://api.task.nesty.by/api/tasks
# Ожидаемо: 401 Unauthorized (это нормально - требуется авторизация)
```

### Troubleshooting Пересборки

**❌ Frontend показывает старый API URL:**

```bash
# Проблема: frontend не пересобрался с новым .env
# Решение: принудительная пересборка БЕЗ кеша
docker compose build --no-cache frontend
```

**❌ CORS ошибки в браузере:**

```bash
# Проблема: backend не обновил CORS конфигурацию
# Решение: проверьте nelmio_cors.yaml и перезапустите
docker compose restart php83-fpm nginx
```

**❌ JWT ошибки при авторизации:**

```bash
# Проблема: JWT ключи не сгенерированы
# Решение: сгенерируйте ключи вручную
docker exec backend-php83 php bin/console lexik:jwt:generate-keypair
```

**❌ Контейнеры не запускаются:**

```bash
# Проверьте логи конкретного контейнера
docker logs frontend-prod
docker logs backend-php83

# Проверьте что порты не заняты
sudo netstat -tlnp | grep -E ':(80|3001|5432)'
```

---

## Проверка Работоспособности

### 1. Проверка DNS

```bash
dig task.nesty.by +short
# Должно вернуть: 45.129.186.88

dig api.task.nesty.by +short
# Должно вернуть: 45.129.186.88
```

### 2. Проверка HTTP → HTTPS Редиректа

```bash
# Frontend
curl -I http://task.nesty.by
# Должно вернуть: HTTP/1.1 301 ... Location: https://task.nesty.by/

# Backend API
curl -I http://api.task.nesty.by
# Должно вернуть: HTTP/1.1 301 ... Location: https://api.task.nesty.by/
```

### 3. Проверка HTTPS Доступности

```bash
# Frontend
curl -I https://task.nesty.by
# Должно вернуть: HTTP/2 200

# Backend API
curl -I https://api.task.nesty.by/api/tasks
# Должно вернуть: HTTP/2 401 (требует авторизации - это нормально)
```

### 4. Проверка SSL Сертификатов

```bash
# Информация о сертификате
openssl s_client -connect task.nesty.by:443 -servername task.nesty.by < /dev/null

# Должно показать:
# subject=CN = task.nesty.by
# issuer=C = US, O = Let's Encrypt, CN = R3
```

### 5. Проверка в Браузере

**Откройте:**

```
https://task.nesty.by
```

**Проверьте:**
- ✅ Зелёный замочек в адресной строке
- ✅ Приложение загружается
- ✅ DevTools → Network: все запросы на `https://api.task.nesty.by`
- ✅ Console: нет CORS ошибок

### 6. Проверка PWA Service Worker

**Chrome DevTools (`F12`) → Application → Service Workers:**

```
Service Workers
  https://task.nesty.by
    ● sw.js
      Status: activated and is running ✅
      Source: https://task.nesty.by/sw.js
```

**Console должна показать:**

```
[PWA] Service Worker registered successfully
[PWA] App ready to work offline
```

### 7. SSL Labs Test (Рекомендуется)

**Проверьте качество SSL конфигурации:**

```
https://www.ssllabs.com/ssltest/analyze.html?d=task.nesty.by
```

**Ожидаемый результат:**
- ✅ Overall Rating: **A или A+**
- ✅ Certificate: Valid (Let's Encrypt)
- ✅ Protocol Support: TLS 1.2, TLS 1.3

---

## Автоматическое Обновление Сертификатов

Let's Encrypt сертификаты действительны **90 дней**. Certbot автоматически настраивает обновление.

### Проверка Таймера Certbot

```bash
# Проверка статуса таймера
sudo systemctl status certbot.timer

# Должно показать:
# Active: active (waiting)
```

### Тест Обновления (Dry Run)

```bash
# Симуляция обновления (не обновляет реально)
sudo certbot renew --dry-run

# Должно вернуть:
# Congratulations, all simulated renewals succeeded
```

### Ручное Обновление (Если Нужно)

```bash
# Обновить все сертификаты
sudo certbot renew

# Перезагрузить nginx после обновления
sudo systemctl reload nginx
```

### Настройка Post-Renewal Hook

**Автоматическая перезагрузка nginx после обновления сертификатов:**

```bash
sudo nano /etc/letsencrypt/renewal-hooks/post/reload-nginx.sh
```

**Содержимое:**

```bash
#!/bin/bash
systemctl reload nginx
```

**Сделайте исполняемым:**

```bash
sudo chmod +x /etc/letsencrypt/renewal-hooks/post/reload-nginx.sh
```

---

## Troubleshooting

### ❌ Certbot: "DNS problem: NXDOMAIN"

**Проблема:**
```
DNS problem: NXDOMAIN looking up A for www.task.nesty.by
```

**Причина:** Отсутствует A-запись для `www.task.nesty.by` в DNS

**Решение:**

1. Добавьте A-запись в DNS панели:
   ```
   A    www.task.nesty.by    45.129.186.88    3600
   ```

2. Подождите 5-30 минут

3. Проверьте DNS:
   ```bash
   dig www.task.nesty.by +short
   ```

4. Повторите certbot команду

### ❌ Nginx: "add_header directive is not allowed here"

**Проблема:**
```
nginx: [emerg] "add_header" directive is not allowed here in /etc/nginx/sites-enabled/api.task.nesty.by.conf:48
nginx: configuration file /etc/nginx/nginx.conf test failed
```

**Причина:** Нельзя использовать `add_header` внутри блока `if` на уровне сервера (только внутри location)

**Решение:**

Используйте **упрощённую конфигурацию** с CORS headers на уровне сервера:

```nginx
server {
    # ... ssl конфигурация ...

    # CORS на уровне сервера (ПРАВИЛЬНО!)
    add_header 'Access-Control-Allow-Origin' 'https://task.nesty.by' always;
    add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, PATCH, OPTIONS' always;
    # ... остальные headers ...

    location / {
        # НЕ используйте if внутри location для CORS!
        proxy_pass http://localhost:80;
    }
}
```

**Альтернатива:** Если нужна обработка OPTIONS, переместите `if` блок **внутрь location**:

```nginx
location / {
    # CORS preflight внутри location (РАБОТАЕТ!)
    if ($request_method = 'OPTIONS') {
        add_header 'Access-Control-Allow-Origin' 'https://task.nesty.by' always;
        # ... остальные headers ...
        return 204;
    }

    proxy_pass http://localhost:80;

    # CORS для обычных запросов
    add_header 'Access-Control-Allow-Origin' 'https://task.nesty.by' always;
}
```

### ❌ Nginx: "nginx: [emerg] SSL error"

**Проблема:**
```
nginx: [emerg] SSL: error:02001002:system library:fopen:No such file or directory
```

**Причина:** Неправильный путь к SSL сертификатам

**Решение:**

1. Проверьте что сертификаты существуют:
   ```bash
   sudo ls -la /etc/letsencrypt/live/task.nesty.by/
   ```

2. Проверьте пути в nginx конфиге:
   ```bash
   grep -r "ssl_certificate" /etc/nginx/sites-enabled/
   ```

3. Убедитесь что пути совпадают

### ❌ CORS Error: Duplicate Headers

**Проблема в Console:**
```
Access to XMLHttpRequest at 'https://api.task.nesty.by/api/auth/google'
from origin 'https://task.nesty.by' has been blocked by CORS policy:
The 'Access-Control-Allow-Origin' header contains multiple values
'https://task.nesty.by, https://task.nesty.by', but only one is allowed.
```

**Причина:** CORS headers добавляются **дважды** - в nginx И в Symfony (nelmio_cors).

**Решение:**

1. **Удалите CORS headers из nginx конфигурации:**
   ```bash
   sudo nano /etc/nginx/sites-available/api.task.nesty.by.conf
   ```

   **УДАЛИТЕ все строки** с `Access-Control-*`:
   ```nginx
   # УДАЛИТЬ ЭТИ СТРОКИ! ↓
   add_header 'Access-Control-Allow-Origin' 'https://task.nesty.by' always;
   add_header 'Access-Control-Allow-Methods' ...
   add_header 'Access-Control-Allow-Headers' ...
   # И т.д.
   ```

   **Оставьте только Security headers** (НЕ CORS):
   ```nginx
   add_header X-Frame-Options "SAMEORIGIN" always;
   add_header X-Content-Type-Options "nosniff" always;
   add_header X-XSS-Protection "1; mode=block" always;
   ```

2. **Проверьте CORS в Symfony:**
   ```bash
   nano apps/backend/config/packages/nelmio_cors.yaml
   ```

   Убедитесь что конфигурация правильная:
   ```yaml
   nelmio_cors:
       defaults:
           origin_regex: true
           allow_origin:
               - '^https?://localhost(:[0-9]+)?$'
               - '^https://task\.nesty\.by$'
           allow_credentials: true
   ```

3. **Перезагрузите nginx и backend:**
   ```bash
   sudo systemctl reload nginx
   docker compose -f docker-compose.yml \
     -f infrastructure/docker/docker-compose-prod.yml \
     restart php83-fpm nginx
   docker exec backend-php83 php bin/console cache:clear --env=prod
   ```

4. **Проверьте что headers идут только один раз:**
   ```bash
   curl -I -X OPTIONS https://api.task.nesty.by/api/auth/google \
     -H "Origin: https://task.nesty.by" \
     -H "Access-Control-Request-Method: POST"

   # Должен быть ОДИН access-control-allow-origin header!
   ```

### ❌ CORS Error: No Access-Control-Allow-Origin

**Проблема в Console:**
```
Access to XMLHttpRequest at 'https://api.task.nesty.by/api/tasks'
from origin 'https://task.nesty.by' has been blocked by CORS policy:
No 'Access-Control-Allow-Origin' header is present
```

**Причина:** CORS не настроен в Symfony или неправильный домен.

**Решение:**

1. **Обновите CORS в Symfony:**
   ```bash
   nano apps/backend/config/packages/nelmio_cors.yaml
   ```

   ```yaml
   nelmio_cors:
       defaults:
           origin_regex: true
           allow_origin:
               - '^https?://localhost(:[0-9]+)?$'
               - '^https://task\.nesty\.by$'  # ← Ваш домен с HTTPS!
           allow_credentials: true
   ```

2. **Перезапустите backend:**
   ```bash
   docker compose restart php83-fpm nginx
   docker exec backend-php83 php bin/console cache:clear --env=prod
   ```

### ❌ PWA Service Worker Не Регистрируется

**Проблема:** Service Worker не работает даже на HTTPS

**Решение:**

1. **Очистите кеш браузера полностью:**
   ```
   DevTools → Application → Storage → Clear site data
   ```

2. **Проверьте что frontend пересобран с новым API URL:**
   ```bash
   docker exec frontend-prod env | grep VITE_API_BASE_URL
   # Должно быть: VITE_API_BASE_URL=https://api.task.nesty.by
   ```

3. **Пересоберите frontend если нужно:**
   ```bash
   docker compose build --no-cache frontend
   docker compose up -d frontend
   ```

4. **Hard refresh в браузере:**
   ```
   Ctrl+Shift+R (Windows) или Cmd+Shift+R (Mac)
   ```

### ❌ Frontend Показывает Старый API URL

**Проблема:** Запросы идут на старый `http://45.129.186.88` вместо `https://api.task.nesty.by`

**Решение:**

1. **Проверьте .env.docker.prod:**
   ```bash
   cat .env.docker.prod | grep VITE_API_BASE_URL
   ```

2. **Убедитесь что пересобрали frontend БЕЗ кеша:**
   ```bash
   docker compose build --no-cache frontend
   ```

3. **Проверьте что контейнер использует новый .env:**
   ```bash
   docker exec frontend-prod cat /etc/nginx/conf.d/default.conf
   ```

### ❌ Port 443 Already in Use

**Проблема:**
```
nginx: [emerg] bind() to 0.0.0.0:443 failed (98: Address already in use)
```

**Причина:** Другой процесс использует порт 443

**Решение:**

1. **Найдите процесс:**
   ```bash
   sudo netstat -tlnp | grep :443
   ```

2. **Если это старый nginx:**
   ```bash
   sudo systemctl stop nginx
   sudo systemctl start nginx
   ```

3. **Если другой процесс:**
   ```bash
   sudo kill <PID>
   ```

### ❌ Certificate Expired

**Проблема:** Сертификат истёк (через 90 дней)

**Решение:**

1. **Обновите сертификаты:**
   ```bash
   sudo certbot renew
   ```

2. **Перезагрузите nginx:**
   ```bash
   sudo systemctl reload nginx
   ```

3. **Проверьте таймер certbot:**
   ```bash
   sudo systemctl status certbot.timer
   ```

---

## Дополнительные Ресурсы

- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)
- [Certbot User Guide](https://eff-certbot.readthedocs.io/)
- [Nginx SSL Configuration](https://nginx.org/en/docs/http/configuring_https_servers.html)
- [Mozilla SSL Configuration Generator](https://ssl-config.mozilla.org/)
- [SSL Labs Server Test](https://www.ssllabs.com/ssltest/)

---

## Checklist После Установки

**Базовая настройка:**
- [ ] DNS A-записи добавлены для всех доменов
- [ ] Certbot установлен (`certbot --version`)
- [ ] SSL сертификаты получены для всех доменов (`sudo certbot certificates`)
- [ ] Nginx snippets созданы (`letsencrypt.conf`, `ssl.conf`)
- [ ] DH параметры сгенерированы (`/etc/ssl/certs/dhparam.pem`)

**Nginx конфигурация:**
- [ ] Frontend конфиг создан (`task.nesty.by.conf`)
- [ ] Backend API конфиг создан (`api.task.nesty.by.conf`)
- [ ] Symlinks в sites-enabled активированы
- [ ] Nginx синтаксис валидный (`sudo nginx -t`)
- [ ] Nginx перезагружен (`sudo systemctl reload nginx`)

**Docker контейнеры:**
- [ ] `.env.docker.prod` обновлён с новым `VITE_API_BASE_URL`
- [ ] Frontend пересобран БЕЗ кеша
- [ ] Backend CORS конфигурация обновлена
- [ ] Все контейнеры запущены (`docker ps`)

**Проверка работоспособности:**
- [ ] DNS резолвится корректно (`dig task.nesty.by +short`)
- [ ] HTTP редиректит на HTTPS (`curl -I http://task.nesty.by`)
- [ ] HTTPS работает (`curl -I https://task.nesty.by`)
- [ ] Frontend открывается в браузере (зелёный замочек)
- [ ] Backend API доступен (`curl https://api.task.nesty.by/api/tasks`)
- [ ] CORS работает (нет ошибок в Console)
- [ ] PWA Service Worker активен (DevTools → Application)
- [ ] SSL Labs тест пройден (A или A+)

**Автоматизация:**
- [ ] Certbot таймер активен (`sudo systemctl status certbot.timer`)
- [ ] Dry-run обновления успешен (`sudo certbot renew --dry-run`)
- [ ] Post-renewal hook настроен

---

**Последнее обновление:** 2025-11-14
**Версия:** 1.2
**Автор:** Claude Code AI

**Изменения v1.2:**
- ✅ **КРИТИЧНО**: Удалены CORS headers из nginx конфигурации (дублирование с Symfony)
- ✅ Обновлена Backend API конфигурация - БЕЗ CORS headers в nginx
- ✅ Добавлено предупреждение: CORS только в Symfony (nelmio_cors bundle)
- ✅ Обновлена секция "Обновление CORS в Backend" с правильной конфигурацией
- ✅ Исправлены порты: `proxy_pass http://localhost:8089` (было 80)
- ✅ Добавлен новый Troubleshooting: "CORS Error: Duplicate Headers"
- ✅ Обновлен Troubleshooting: "CORS Error: No Access-Control-Allow-Origin"
- ✅ Удалена устаревшая "альтернативная версия" с CORS в nginx

**Изменения v1.1:**
- ✅ Исправлена конфигурация API с CORS (убрана проблема с add_header в if блоке)
- ✅ Добавлена упрощённая версия конфигурации API (рекомендуемая)
- ✅ Добавлена альтернативная версия с явной обработкой OPTIONS
- ✅ Обновлён Troubleshooting: добавлена проблема "add_header directive is not allowed"

**Связанные документы:**
- [DEPLOYMENT.md](DEPLOYMENT.md) - Общее руководство по deployment
- [PWA_TESTING_GUIDE.md](../guides/PWA_TESTING_GUIDE.md) - Тестирование PWA функций
- [ENVIRONMENT_CONFIGURATION.md](../guides/ENVIRONMENT_CONFIGURATION.md) - Управление переменными окружения
