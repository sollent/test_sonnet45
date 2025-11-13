# 📦 Scripts - Вспомогательные Скрипты

> **Коллекция bash скриптов для автоматизации deployment и управления проектом**

---

## 📋 Содержание

1. [Обзор](#обзор)
2. [Список Скриптов](#список-скриптов)
3. [Использование](#использование)
4. [Требования](#требования)

---

## Обзор

Эта директория содержит вспомогательные bash скрипты для упрощения разработки и deployment процессов TaskFlow проекта.

**Все скрипты запускаются из корня проекта:**

```bash
# Правильно ✅
./scripts/deploy-production.sh

# Неправильно ❌
cd scripts && ./deploy-production.sh
```

---

## Список Скриптов

### 🚀 `deploy-production.sh`

**Назначение**: Автоматический deployment полного production окружения на VDS

**Что делает**:
- ✅ Проверяет установку Docker и Docker Compose
- ✅ Интерактивно настраивает `.env.docker.prod`
- ✅ Останавливает старые контейнеры
- ✅ Собирает production Docker образы (backend + frontend)
- ✅ Запускает все сервисы
- ✅ Применяет миграции базы данных
- ✅ Проверяет здоровье сервисов

**Использование**:

```bash
# Первый деплой
./scripts/deploy-production.sh

# Повторный деплой (с существующим .env.docker.prod)
./scripts/deploy-production.sh
```

**Интерактивность**:
- Спрашивает путь к проекту (по умолчанию `/opt/taskflow`)
- Предлагает создать/редактировать `.env.docker.prod`
- Спрашивает о клонировании репозитория (если директория не существует)

**Требования**:
- Docker 20.10+
- Docker Compose v2+
- Bash 4.0+
- Root/sudo доступ (для Docker команд)

**Output**:
- Цветной прогресс с 8 шагами
- Финальный отчет с URL доступа
- Команды управления

**Документация**: [`docs/guides/DEPLOYMENT.md`](../docs/guides/DEPLOYMENT.md)

---

### 🔐 `generate-secrets.sh`

**Назначение**: Генерация криптографически безопасных паролей и секретов для production

**Что генерирует**:
- `POSTGRES_PASSWORD` - 32 символа (random base64)
- `RABBITMQ_PASSWORD` - 32 символа (random base64)
- `APP_SECRET` - 32 hex символа (Symfony secret)
- `JWT_PASSPHRASE` - 64 hex символа (JWT signing key)

**Использование**:

```bash
# Просто запустить и скопировать output
./scripts/generate-secrets.sh

# Сохранить в файл
./scripts/generate-secrets.sh > .env.docker.prod.generated
```

**Output**:

```
================================================
Генератор безопасных секретов
================================================

Генерируем секреты...

✓ POSTGRES_PASSWORD
  aB3dE9fG2hI5jK8lM1nO4pQ7rS0tU6vW

✓ RABBITMQ_PASSWORD
  xY1zA2bC3dE4fG5hI6jK7lM8nO9pQ0rS

✓ APP_SECRET
  a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6

✓ JWT_PASSPHRASE
  1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef

================================================
Готово! Используйте эти значения в .env.docker.prod
================================================

Пример .env.docker.prod:

# PostgreSQL Configuration
POSTGRES_DB=backend_prod
POSTGRES_USER=prod_user
POSTGRES_PASSWORD=aB3dE9fG2hI5jK8lM1nO4pQ7rS0tU6vW
...
```

**Алгоритм**:
- Использует `openssl rand` (если доступен)
- Fallback на `/dev/urandom` (если openssl не установлен)
- Base64 для паролей (без special chars для совместимости)
- Hex для Symfony secrets (максимальная энтропия)

**Безопасность**:
- ✅ Криптографически безопасная генерация (CSPRNG)
- ✅ Достаточная длина (32-64 символа)
- ✅ Уникальность (каждый запуск = новые секреты)

**Требования**:
- `openssl` (опционально, но рекомендуется)
- `/dev/urandom` (fallback, обычно есть в Linux/macOS)

---

## Использование

### Типичный Production Workflow

**Первый деплой на VDS:**

```bash
# 1. Клонирование проекта на VDS
ssh user@your-vds-ip
cd /opt
sudo git clone https://github.com/your-username/taskflow.git
cd taskflow

# 2. Генерация безопасных секретов
./scripts/generate-secrets.sh

# 3. Создание .env.docker.prod
cp .env.docker.example .env.docker.prod
nano .env.docker.prod
# Вставьте сгенерированные секреты

# 4. Автоматический деплой
./scripts/deploy-production.sh

# 5. Проверка
curl http://localhost:3001  # Frontend
curl http://localhost:80/api/tasks  # Backend API
```

**Обновление приложения (новая версия):**

```bash
# 1. Забрать изменения из Git
cd /opt/taskflow
git pull origin main

# 2. Повторный деплой (с существующим .env)
./scripts/deploy-production.sh

# Скрипт автоматически:
# - Остановит старые контейнеры
# - Соберет новые образы
# - Применит новые миграции
# - Запустит обновленные сервисы
```

---

## Требования

### Общие требования

- **OS**: Linux (Ubuntu 20.04+, Debian 11+, CentOS 8+) или macOS
- **Shell**: Bash 4.0+
- **User**: Root или sudo доступ

### Для `deploy-production.sh`

```bash
# Проверка Docker
docker --version
# Должно быть: Docker version 20.10+

# Проверка Docker Compose
docker compose version
# Должно быть: Docker Compose version v2.0+

# Проверка Git (опционально)
git --version
# Для клонирования репозитория
```

### Для `generate-secrets.sh`

```bash
# Проверка openssl (рекомендуется)
openssl version
# Если нет - скрипт использует /dev/urandom

# Проверка /dev/urandom (fallback)
test -e /dev/urandom && echo "OK" || echo "NOT FOUND"
# Должно быть: OK
```

---

## Troubleshooting

### Скрипт не запускается - "Permission denied"

**Проблема**: Файл не имеет прав на исполнение

**Решение**:

```bash
chmod +x scripts/deploy-production.sh
chmod +x scripts/generate-secrets.sh
```

### Docker команды требуют sudo

**Проблема**: Пользователь не в группе `docker`

**Решение**:

```bash
# Добавить пользователя в группу docker
sudo usermod -aG docker $USER

# Перелогиниться (или выполнить)
newgrp docker

# Проверка
docker ps  # Должно работать без sudo
```

### "Docker daemon not running"

**Проблема**: Docker daemon не запущен

**Решение**:

```bash
# Ubuntu/Debian
sudo systemctl start docker
sudo systemctl enable docker  # Автозапуск при загрузке

# CentOS/RHEL
sudo systemctl start docker
sudo systemctl enable docker
```

### Скрипт падает с ошибкой на середине

**Причина**: `set -e` останавливает скрипт при первой ошибке

**Решение**:

```bash
# Посмотреть где упало
cat /var/log/docker-deploy.log  # Если есть логирование

# Запустить скрипт с debug
bash -x ./scripts/deploy-production.sh

# Исправить проблему и запустить снова
```

---

## Документация

**Полная документация по deployment**:
- [`docs/guides/DEPLOYMENT.md`](../docs/guides/DEPLOYMENT.md) - Детальное руководство по production deployment
- [`docs/guides/DEVELOPMENT_WORKFLOW.md`](../docs/guides/DEVELOPMENT_WORKFLOW.md) - Development окружение
- [`docs/guides/ENVIRONMENT_CONFIGURATION.md`](../docs/guides/ENVIRONMENT_CONFIGURATION.md) - Управление переменными окружения

**Docker конфигурация**:
- `docker-compose.yml` - Базовая конфигурация
- `infrastructure/docker/docker-compose-prod.yml` - Production overrides для backend
- `infrastructure/docker/docker-compose.frontend-prod.yml` - Production конфигурация для frontend

---

## Планируемые Скрипты

**Будущие улучшения** (пока не реализовано):

- [ ] `backup-database.sh` - Автоматический бэкап PostgreSQL
- [ ] `restore-database.sh` - Восстановление из бэкапа
- [ ] `update-ssl.sh` - Обновление SSL сертификатов (Let's Encrypt)
- [ ] `health-check.sh` - Проверка здоровья всех сервисов
- [ ] `rollback.sh` - Откат к предыдущей версии

---

**Последнее обновление**: 2025-11-13
**Версия**: 1.0
**Автор**: Claude Code AI
