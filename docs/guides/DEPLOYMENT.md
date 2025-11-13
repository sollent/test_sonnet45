# 🚀 Production Deployment Guide

> **Полное руководство по развертыванию TaskFlow на production VDS**
> **Версия**: 2.0
> **Дата**: 2025-11-13

---

## 📋 Содержание

1. [Обзор](#обзор)
2. [Требования](#требования)
3. [Быстрый старт](#быстрый-старт)
4. [Пошаговое развертывание](#пошаговое-развертывание)
5. [Автоматический деплой](#автоматический-деплой)
6. [Настройка VDS](#настройка-vds)
7. [SSL/TLS](#ssltls)
8. [Мониторинг](#мониторинг)
9. [Troubleshooting](#troubleshooting)

---

## Обзор

TaskFlow использует **Docker контейнеризацию** для production развертывания:

- **Backend**: PHP 8.3 + Symfony 7.1 + PostgreSQL 16 + RabbitMQ
- **Frontend**: Vue.js 3.4 (pre-built) + Nginx
- **Infrastructure**: Docker Compose orchestration

**Два способа развертывания:**

1. ⚡ **Автоматический** - используйте `deploy-production.sh` (рекомендуется)
2. 📝 **Ручной** - пошаговые команды (для fine-tuning)

---

## Требования

### Минимальные требования VDS

| Компонент | Минимум | Рекомендуется |
|-----------|---------|---------------|
| CPU | 2 ядра | 4+ ядра |
| RAM | 2 GB | 4+ GB |
| Disk | 20 GB SSD | 50+ GB SSD |
| OS | Ubuntu 20.04+ | Ubuntu 22.04 LTS |

### Установленное ПО

✅ **Обязательно:**
- Docker Engine 24.0+
- Docker Compose v2+
- Git

✅ **Опционально:**
- Nginx (для reverse proxy)
- Certbot (для SSL)
- UFW/iptables (firewall)

### Проверка Docker

```bash
# Версия Docker
docker --version
# Должно быть: Docker version 24.0+

# Версия Docker Compose
docker compose version
# Должно быть: Docker Compose version v2.+

# Docker daemon работает
docker info
# Должно показать информацию о системе
```

---

## Быстрый старт

### Шаг 1: Клонирование проекта

```bash
# На VDS
cd /opt
sudo git clone https://github.com/your-username/taskflow.git
cd taskflow
```

### Шаг 2: Генерация секретов

```bash
./scripts/generate-secrets.sh
```

Скопируйте сгенерированные секреты.

### Шаг 3: Создание .env.docker.prod

```bash
cp .env.docker.example .env.docker.prod
nano .env.docker.prod
```

Вставьте сгенерированные секреты из шага 2.

**Критически важно изменить:**
- `POSTGRES_PASSWORD` - пароль БД
- `RABBITMQ_PASSWORD` - пароль RabbitMQ
- `APP_SECRET` - Symfony secret
- `JWT_PASSPHRASE` - JWT шифрование
- `GOOGLE_CLIENT_ID` - OAuth (если используется)
- `GOOGLE_CLIENT_SECRET` - OAuth secret
- `VITE_API_BASE_URL` - URL вашего домена

### Шаг 4: Автоматический деплой

```bash
./scripts/deploy-production.sh
```

Скрипт выполнит:
- ✅ Проверку окружения
- ✅ Сборку Docker образов
- ✅ Запуск контейнеров
- ✅ Применение миграций БД
- ✅ Проверку статуса

**Время выполнения:** ~5-10 минут

### Шаг 5: Проверка

```bash
# Проверить статус контейнеров
docker ps

# Проверить логи
docker logs -f frontend-prod
docker logs -f backend-php83

# Проверить доступность
curl http://localhost:3001       # Frontend
curl http://localhost:80/api     # Backend API
```

---

## Пошаговое развертывание

### 1. Подготовка VDS

```bash
# Обновление системы
sudo apt update && sudo apt upgrade -y

# Установка Docker (если не установлен)
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER

# Установка Docker Compose v2
sudo apt install docker-compose-plugin

# Перелогиниться для применения прав
```

### 2. Клонирование репозитория

```bash
# Создать директорию для проекта
sudo mkdir -p /opt/taskflow
sudo chown $USER:$USER /opt/taskflow

# Клонировать
cd /opt
git clone https://github.com/your-username/taskflow.git
cd taskflow
```

### 3. Настройка переменных окружения

```bash
# Сгенерировать секреты
./scripts/generate-secrets.sh > production-secrets.txt

# Создать production env
cp .env.docker.example .env.docker.prod

# Редактировать (вставить секреты из production-secrets.txt)
nano .env.docker.prod
```

**Пример .env.docker.prod:**

```bash
# PostgreSQL
POSTGRES_DB=backend_prod
POSTGRES_USER=prod_user
POSTGRES_PASSWORD=<сгенерированный_пароль>
POSTGRES_PORT=5432

# RabbitMQ
RABBITMQ_USER=prod_user
RABBITMQ_PASSWORD=<сгенерированный_пароль>
RABBITMQ_PORT=5672
RABBITMQ_MANAGEMENT_PORT=15672

# Nginx
NGINX_PORT=80
PHP_FPM_PORT=9000

# Symfony
APP_SECRET=<сгенерированный_секрет>
JWT_PASSPHRASE=<сгенерированный_passphrase>

# Google OAuth
GOOGLE_CLIENT_ID=<ваш_client_id>
GOOGLE_CLIENT_SECRET=<ваш_secret>

# Frontend
FRONTEND_PROD_PORT=3001
VITE_API_BASE_URL=https://api.yourdomain.com
```

### 4. Создание symlink для Docker Compose

```bash
ln -sf .env.docker.prod .env
```

### 5. Сборка production образов

```bash
# Сборка БЕЗ кеша (гарантирует свежую сборку)
docker compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose-prod.yml \
    -f infrastructure/docker/docker-compose.frontend-prod.yml \
    build --no-cache
```

**Время сборки:** ~3-5 минут

### 6. Запуск контейнеров

```bash
docker compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose-prod.yml \
    -f infrastructure/docker/docker-compose.frontend-prod.yml \
    up -d
```

### 7. Применение миграций

```bash
# Создать БД (если не существует)
docker exec backend-php83 php bin/console doctrine:database:create \
    --if-not-exists --env=prod

# Применить миграции
docker exec backend-php83 php bin/console doctrine:migrations:migrate \
    --no-interaction --env=prod
```

### 8. Проверка статуса

```bash
# Статус всех контейнеров
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Должны быть запущены:
# - frontend-prod (Nginx :3001)
# - backend-nginx (API :80)
# - backend-php83 (PHP-FPM :9000)
# - backend-psql16 (PostgreSQL :5432)
# - backend-rabbitmq (:5672, :15672)
# - backend-cron
```

---

## Автоматический деплой

### Использование deploy-production.sh

Скрипт `scripts/deploy-production.sh` автоматизирует весь процесс.

**Функции:**
- ✅ Проверка prerequisites (Docker, Git)
- ✅ Интерактивное создание .env.docker.prod
- ✅ Остановка старых контейнеров
- ✅ Сборка новых образов
- ✅ Запуск контейнеров
- ✅ Применение миграций
- ✅ Проверка здоровья сервисов
- ✅ Итоговый отчет с URL-ами

**Запуск:**

```bash
cd /opt/taskflow
./scripts/deploy-production.sh
```

**Интерактивные вопросы:**

1. "Введите путь к проекту" - укажите или оставьте `/opt/taskflow`
2. "Клонировать проект из Git?" - если директория пуста
3. "Пересоздать .env.docker.prod?" - если файл существует
4. "Открыть .env.docker.prod для редактирования?" - для настройки credentials

**Вывод скрипта:**

```
================================================
Шаг 1: Проверка окружения
================================================
▶ Проверка Docker...
✓ Docker установлен: Docker version 24.0.7

▶ Проверка Docker Compose...
✓ Docker Compose установлен: Docker Compose version v2.23.3

...

================================================
Развертывание завершено! 🎉
================================================

ℹ Доступ к приложению:
  • Frontend:  http://192.168.1.100:3001
  • Backend:   http://192.168.1.100:80/api

✓ Все готово! Приложение работает в production режиме! 🚀
```

### Использование generate-secrets.sh

Генерирует безопасные случайные пароли.

```bash
./scripts/generate-secrets.sh

# Вывод:
# ================================================
# Генератор безопасных секретов
# ================================================
#
# ✓ POSTGRES_PASSWORD
#   xK9mP2vL...
#
# ✓ RABBITMQ_PASSWORD
#   aB3nQ7wE...
#
# ✓ APP_SECRET
#   f8c4a2d6...
#
# ✓ JWT_PASSPHRASE
#   e79f40ab...
```

**Сохраните вывод** в безопасное место (например, password manager).

---

## Настройка VDS

### 1. Firewall (UFW)

```bash
# Установка UFW
sudo apt install ufw

# Разрешить SSH
sudo ufw allow 22/tcp

# Разрешить HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Включить firewall
sudo ufw enable

# Проверить статус
sudo ufw status
```

### 2. Nginx Reverse Proxy (опционально)

Если хотите использовать домен вместо IP:порт.

```bash
sudo apt install nginx

# Создать конфигурацию
sudo nano /etc/nginx/sites-available/taskflow
```

**Конфигурация `/etc/nginx/sites-available/taskflow`:**

```nginx
# Frontend
server {
    listen 80;
    server_name app.yourdomain.com;

    location / {
        proxy_pass http://localhost:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

# Backend API
server {
    listen 80;
    server_name api.yourdomain.com;

    location / {
        proxy_pass http://localhost:80;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # CORS headers (если нужно)
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'Authorization, Content-Type' always;
    }
}
```

```bash
# Включить конфигурацию
sudo ln -s /etc/nginx/sites-available/taskflow /etc/nginx/sites-enabled/

# Проверить конфигурацию
sudo nginx -t

# Перезагрузить Nginx
sudo systemctl reload nginx
```

### 3. Автозапуск при перезагрузке

Docker контейнеры уже настроены с `restart: unless-stopped`, но проверьте:

```bash
# Docker должен запускаться при загрузке
sudo systemctl enable docker

# Проверить
sudo systemctl is-enabled docker
# Должно быть: enabled
```

---

## SSL/TLS

### Использование Let's Encrypt (Certbot)

```bash
# Установка Certbot
sudo apt install certbot python3-certbot-nginx

# Получить сертификаты
sudo certbot --nginx -d app.yourdomain.com -d api.yourdomain.com

# Следовать инструкциям Certbot
# Email: your@email.com
# Agree: yes
# Redirect HTTP to HTTPS: 2 (yes)
```

**Certbot автоматически:**
- Получит SSL сертификаты
- Обновит Nginx конфигурацию
- Настроит автообновление (через cron)

**Проверка автообновления:**

```bash
sudo certbot renew --dry-run
```

### Обновление VITE_API_BASE_URL

После настройки SSL обновите `.env.docker.prod`:

```bash
# Было
VITE_API_BASE_URL=http://api.yourdomain.com

# Стало (с HTTPS)
VITE_API_BASE_URL=https://api.yourdomain.com
```

**Пересобрать frontend:**

```bash
docker compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose-prod.yml \
    -f infrastructure/docker/docker-compose.frontend-prod.yml \
    build --no-cache frontend

docker compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose-prod.yml \
    -f infrastructure/docker/docker-compose.frontend-prod.yml \
    up -d --force-recreate frontend
```

---

## Мониторинг

### Просмотр логов

```bash
# Все логи
docker compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose-prod.yml \
    -f infrastructure/docker/docker-compose.frontend-prod.yml \
    logs -f

# Только frontend
docker logs -f frontend-prod

# Только backend
docker logs -f backend-php83
docker logs -f backend-nginx

# БД и очередь
docker logs -f backend-psql16
docker logs -f backend-rabbitmq
```

### Статистика ресурсов

```bash
# Использование CPU/RAM/Network
docker stats

# Только production контейнеры
docker stats frontend-prod backend-php83 backend-nginx backend-psql16 backend-rabbitmq
```

### Проверка здоровья

```bash
# HTTP статус frontend
curl -I http://localhost:3001

# HTTP статус backend API
curl http://localhost:80/api/tasks
# Ожидаем: {"code":401,"message":"JWT Token not found"}

# PostgreSQL
docker exec backend-psql16 pg_isready -U prod_user -d backend_prod

# RabbitMQ
curl http://localhost:15672/api/overview
```

---

## Troubleshooting

### Контейнер не запускается

```bash
# Проверить логи
docker logs backend-php83

# Проверить конфигурацию
docker compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose-prod.yml \
    -f infrastructure/docker/docker-compose.frontend-prod.yml \
    config

# Пересоздать контейнер
docker compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose-prod.yml \
    -f infrastructure/docker/docker-compose.frontend-prod.yml \
    up -d --force-recreate backend-php83
```

### Ошибка "Port already in use"

```bash
# Найти процесс на порту
sudo lsof -i :80
sudo lsof -i :3001

# Убить процесс
sudo kill -9 <PID>

# Или остановить Nginx (если конфликтует)
sudo systemctl stop nginx
```

### Database connection refused

```bash
# Проверить что PostgreSQL запущен
docker ps | grep psql16

# Проверить credentials в .env.docker.prod
cat .env.docker.prod | grep POSTGRES

# Проверить подключение вручную
docker exec backend-php83 php bin/console doctrine:query:sql \
    "SELECT 1" --env=prod
```

### Frontend показывает ERR_CONNECTION_REFUSED

**Причина:** Неправильный `VITE_API_BASE_URL`

**Решение:**

1. Проверить `.env.docker.prod`:
   ```bash
   cat .env.docker.prod | grep VITE_API_BASE_URL
   ```

2. Должно быть:
   - Development: `http://localhost:80`
   - Production с доменом: `https://api.yourdomain.com`

3. Пересобрать frontend:
   ```bash
   docker compose -f docker-compose.yml \
       -f infrastructure/docker/docker-compose-prod.yml \
       -f infrastructure/docker/docker-compose.frontend-prod.yml \
       build --no-cache frontend

   docker compose -f docker-compose.yml \
       -f infrastructure/docker/docker-compose-prod.yml \
       -f infrastructure/docker/docker-compose.frontend-prod.yml \
       up -d --force-recreate frontend
   ```

### Миграции не применяются

```bash
# Проверить статус миграций
docker exec backend-php83 php bin/console doctrine:migrations:status --env=prod

# Применить вручную
docker exec backend-php83 php bin/console doctrine:migrations:migrate \
    --no-interaction --env=prod

# Если ошибка "table already exists"
docker exec backend-php83 php bin/console doctrine:migrations:version \
    --add --all --env=prod
```

### Нехватка памяти

```bash
# Проверить использование
free -h
docker stats

# Увеличить swap (если нужно)
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# Сделать постоянным
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

---

## Бэкапы

### Бэкап базы данных

```bash
# Создать бэкап
docker exec backend-psql16 pg_dump -U prod_user backend_prod > \
    backup_$(date +%Y%m%d_%H%M%S).sql

# Автоматический бэкап (cron)
crontab -e

# Добавить строку (бэкап каждый день в 2:00 AM)
0 2 * * * cd /opt/taskflow && docker exec backend-psql16 pg_dump -U prod_user backend_prod > /opt/backups/db_$(date +\%Y\%m\%d).sql
```

### Восстановление из бэкапа

```bash
# Восстановить БД
docker exec -i backend-psql16 psql -U prod_user backend_prod < backup_20251113.sql
```

---

## Обновление приложения

### Git Pull + Rebuild

```bash
cd /opt/taskflow

# Остановить контейнеры
docker compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose-prod.yml \
    -f infrastructure/docker/docker-compose.frontend-prod.yml \
    down

# Получить изменения
git pull origin main

# Пересобрать образы
docker compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose-prod.yml \
    -f infrastructure/docker/docker-compose.frontend-prod.yml \
    build --no-cache

# Запустить
docker compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose-prod.yml \
    -f infrastructure/docker/docker-compose.frontend-prod.yml \
    up -d

# Применить новые миграции (если есть)
docker exec backend-php83 php bin/console doctrine:migrations:migrate \
    --no-interaction --env=prod
```

---

## Полезные команды

```bash
# Просмотр всех контейнеров
docker ps -a

# Остановка всех контейнеров
docker compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose-prod.yml \
    -f infrastructure/docker/docker-compose.frontend-prod.yml \
    down

# Удаление всех контейнеров и volumes
docker compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose-prod.yml \
    -f infrastructure/docker/docker-compose.frontend-prod.yml \
    down -v

# Очистка неиспользуемых образов
docker image prune -a

# Очистка всего
docker system prune -a --volumes
```

---

**Последнее обновление:** 2025-11-13
**Версия документа:** 2.0
**Автор:** Claude Code AI

**Изменения v2.0:**
- ✅ Добавлены автоматические скрипты развертывания
- ✅ Добавлен генератор безопасных секретов
- ✅ Обновлены инструкции по SSL/TLS
- ✅ Добавлены секции мониторинга и troubleshooting
- ✅ Добавлены инструкции по бэкапам
- ✅ Проверено на реальном VDS ✅
