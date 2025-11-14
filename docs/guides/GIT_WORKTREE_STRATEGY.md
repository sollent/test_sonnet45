# 🌳 Git Worktree Стратегия для Параллельной Разработки

> **Документация по использованию Git Worktree для работы в нескольких ветках одновременно**
> **Версия**: 1.0
> **Дата**: 2025-11-14

---

## 📋 Содержание

1. [Обзор](#обзор)
2. [Зачем Нужен Git Worktree?](#зачем-нужен-git-worktree)
3. [Архитектура Решения](#архитектура-решения)
4. [Быстрый Старт](#быстрый-старт)
5. [Структура Worktree Окружения](#структура-worktree-окружения)
6. [Рабочие Процессы](#рабочие-процессы)
7. [Управление Docker в Worktree](#управление-docker-в-worktree)
8. [Bash Скрипты для Автоматизации](#bash-скрипты-для-автоматизации)
9. [Работа с Claude Code в Worktree](#работа-с-claude-code-в-worktree)
10. [Best Practices](#best-practices)
11. [Troubleshooting](#troubleshooting)

---

## Обзор

### Что Такое Git Worktree?

**Git Worktree** позволяет иметь несколько рабочих копий одного репозитория одновременно, каждая с **разными ветками**.

**Ключевое преимущество**: Не нужно переключаться между ветками (`git checkout`) - каждая ветка работает в отдельной директории!

### Наш Use Case

```
/Users/sollent/Desktop/Projects/CLAUDE/             ← main (основная ветка)
/Users/sollent/Desktop/Projects/CLAUDE-worktrees/
├── feature-caching/                                ← feature/implement-caching-functionality
├── feature-restructure/                            ← feature/project-restructarization
└── feature-new-api/                                ← новая ветка для API
```

**Каждая директория**:
- ✅ Независимый рабочий каталог
- ✅ Отдельная ветка Git
- ✅ Отдельные Docker контейнеры (опционально)
- ✅ Отдельные сессии Claude Code
- ✅ Общая история Git и .git база

---

## Зачем Нужен Git Worktree?

### ❌ Проблемы БЕЗ Worktree

**Сценарий**: Вы работаете в `feature/caching`, запущены Docker контейнеры, Claude Code делает изменения...

```bash
# Хотите переключиться на другую ветку
git checkout feature/restructure

# ❌ ПРОБЛЕМЫ:
# 1. Docker контейнеры используют файлы из feature/caching (конфликт!)
# 2. Claude Code сессия потеряла контекст
# 3. Нужно останавливать Docker, делать stash, переключаться
# 4. Теряете рабочий контекст и прогресс
```

### ✅ Решение С Worktree

```bash
# Открываем Terminal 1
cd /Users/sollent/Desktop/Projects/CLAUDE
docker-compose up -d  # Backend на порту 8089
# Claude Code сессия 1 работает с main

# Открываем Terminal 2
cd /Users/sollent/Desktop/Projects/CLAUDE-worktrees/feature-caching
docker-compose up -d  # Backend на порту 8090 (другой порт!)
# Claude Code сессия 2 работает с feature/caching

# Открываем Terminal 3
cd /Users/sollent/Desktop/Projects/CLAUDE-worktrees/feature-restructure
docker-compose up -d  # Backend на порту 8091
# Claude Code сессия 3 работает с feature/restructure
```

**Результат**: Все ветки работают **параллельно** без конфликтов!

---

## Архитектура Решения

### Структура Директорий

```
Desktop/Projects/
├── CLAUDE/                                          # ← Main worktree (основной репозиторий)
│   ├── .git/                                       # ← Единая Git база для всех worktree
│   ├── apps/
│   ├── docs/
│   ├── infrastructure/
│   ├── docker-compose.yml
│   └── .env.docker                                 # Порты: 8089, 15432, 9009
│
└── CLAUDE-worktrees/                                # ← Директория для всех worktree
    ├── feature-caching/                            # ← Worktree #1
    │   ├── apps/
    │   ├── docs/
    │   ├── infrastructure/
    │   ├── docker-compose.yml
    │   └── .env.docker                             # Порты: 8090, 15433, 9010
    │
    ├── feature-restructure/                        # ← Worktree #2
    │   ├── apps/
    │   ├── docs/
    │   ├── infrastructure/
    │   ├── docker-compose.yml
    │   └── .env.docker                             # Порты: 8091, 15434, 9011
    │
    └── feature-new-api/                            # ← Worktree #3
        ├── apps/
        ├── docs/
        ├── infrastructure/
        ├── docker-compose.yml
        └── .env.docker                             # Порты: 8092, 15435, 9012
```

### Изоляция Портов

**Ключевой момент**: Каждый worktree использует **уникальные порты** для Docker!

| Worktree | Nginx | PostgreSQL | PHP-FPM | Frontend |
|----------|-------|------------|---------|----------|
| **main** | 8089 | 15432 | 9009 | 3000 |
| **feature-caching** | 8090 | 15433 | 9010 | 3001 |
| **feature-restructure** | 8091 | 15434 | 9011 | 3002 |
| **feature-new-api** | 8092 | 15435 | 9012 | 3003 |

**Почему?**
- Избегаем конфликтов портов
- Каждый worktree имеет изолированную БД
- Можно работать со всеми ветками одновременно

---

## Быстрый Старт

### Шаг 1: Создание Структуры Директорий

```bash
# Создаем директорию для всех worktree (вне основного репозитория)
mkdir -p /Users/sollent/Desktop/Projects/CLAUDE-worktrees
```

### Шаг 2: Создание Первого Worktree

```bash
# Переходим в основной репозиторий
cd /Users/sollent/Desktop/Projects/CLAUDE

# Создаем worktree для существующей ветки
git worktree add ../CLAUDE-worktrees/feature-caching feature/implement-caching-functionality

# Создаем worktree для новой ветки
git worktree add -b feature/new-api ../CLAUDE-worktrees/feature-new-api
```

### Шаг 3: Настройка Environment для Worktree

```bash
# Переходим в worktree
cd /Users/sollent/Desktop/Projects/CLAUDE-worktrees/feature-caching

# Копируем .env файл из примера
cp .env.docker.example .env.docker

# Редактируем порты (важно!)
nano .env.docker
```

**Измените порты в `.env.docker`:**

```bash
# PostgreSQL Configuration
POSTGRES_DB=backend-app-caching
POSTGRES_USER=user
POSTGRES_PASSWORD=password
POSTGRES_PORT=15433  # ← Было 15432 (main), теперь 15433!

# Nginx Configuration
NGINX_PORT=8090  # ← Было 8089 (main), теперь 8090!

# PHP-FPM Configuration
PHP_FPM_PORT=9010  # ← Было 9009 (main), теперь 9010!
```

### Шаг 4: Запуск Docker в Worktree

```bash
# В директории worktree
docker-compose up -d

# Проверяем что контейнеры запущены
docker ps | grep caching
```

### Шаг 5: Открытие Claude Code Сессии

```bash
# Открываем новое окно терминала
cd /Users/sollent/Desktop/Projects/CLAUDE-worktrees/feature-caching

# Запускаем Claude Code
claude-code
```

**Теперь у вас**:
- ✅ Основной проект работает на портах 8089/15432/9009
- ✅ feature-caching работает на портах 8090/15433/9010
- ✅ Обе сессии Claude Code работают независимо!

---

## Структура Worktree Окружения

### Список Всех Worktree

```bash
# Посмотреть все worktree
git worktree list

# Вывод:
# /Users/sollent/Desktop/Projects/CLAUDE                           7b1b6f4 [main]
# /Users/sollent/Desktop/Projects/CLAUDE-worktrees/feature-caching abc1234 [feature/implement-caching-functionality]
# /Users/sollent/Desktop/Projects/CLAUDE-worktrees/feature-restructure def5678 [feature/project-restructarization]
```

### Git Operations в Worktree

**Важно**: Все Git операции (commit, push, pull) работают **независимо** в каждом worktree!

```bash
# В main worktree
cd /Users/sollent/Desktop/Projects/CLAUDE
git status  # Показывает изменения только в main
git commit -m "Update docs"
git push origin main

# В feature-caching worktree
cd /Users/sollent/Desktop/Projects/CLAUDE-worktrees/feature-caching
git status  # Показывает изменения только в feature/implement-caching-functionality
git commit -m "Add caching layer"
git push origin feature/implement-caching-functionality
```

**Синхронизация**:

```bash
# Получить изменения из main в worktree
cd /Users/sollent/Desktop/Projects/CLAUDE-worktrees/feature-caching
git fetch origin main
git merge origin/main  # Или git rebase origin/main
```

---

## Рабочие Процессы

### Workflow 1: Создание Нового Feature Worktree

```bash
# 1. Создаем worktree с новой веткой от main
cd /Users/sollent/Desktop/Projects/CLAUDE
git worktree add -b feature/new-feature ../CLAUDE-worktrees/feature-new-feature

# 2. Переходим в worktree
cd ../CLAUDE-worktrees/feature-new-feature

# 3. Настраиваем порты
cp .env.docker.example .env.docker
# Редактируем: NGINX_PORT=8093, POSTGRES_PORT=15436, PHP_FPM_PORT=9013

# 4. Запускаем Docker
docker-compose up -d

# 5. Запускаем миграции
docker exec backend-php83 php bin/console doctrine:migrations:migrate

# 6. Начинаем разработку с Claude Code!
```

### Workflow 2: Переключение Между Worktree

**Не нужно `git checkout`!** Просто переключайтесь между директориями.

```bash
# Terminal 1: Работа с main
cd /Users/sollent/Desktop/Projects/CLAUDE
claude-code

# Terminal 2: Работа с feature-caching (параллельно!)
cd /Users/sollent/Desktop/Projects/CLAUDE-worktrees/feature-caching
claude-code

# Terminal 3: Работа с feature-restructure (параллельно!)
cd /Users/sollent/Desktop/Projects/CLAUDE-worktrees/feature-restructure
claude-code
```

### Workflow 3: Удаление Worktree После Merge

```bash
# 1. Проверяем что ветка смержена в main
cd /Users/sollent/Desktop/Projects/CLAUDE
git branch --merged | grep feature/caching

# 2. Останавливаем Docker в worktree
cd ../CLAUDE-worktrees/feature-caching
docker-compose down -v  # -v удаляет volumes!

# 3. Возвращаемся в main и удаляем worktree
cd /Users/sollent/Desktop/Projects/CLAUDE
git worktree remove ../CLAUDE-worktrees/feature-caching

# 4. Удаляем ветку (опционально)
git branch -d feature/implement-caching-functionality

# 5. Удаляем директорию
rm -rf ../CLAUDE-worktrees/feature-caching
```

---

## Управление Docker в Worktree

### Изоляция Контейнеров

**Проблема**: Docker Compose использует имена контейнеров из `docker-compose.yml`. Если запустить два worktree с одинаковыми именами, будет конфликт!

**Решение**: Используем `COMPOSE_PROJECT_NAME` для изоляции.

### Автоматическая Изоляция через .env.docker

**Добавьте в каждый worktree `.env.docker`:**

```bash
# Main worktree (/Users/sollent/Desktop/Projects/CLAUDE/.env.docker)
COMPOSE_PROJECT_NAME=claude-main
POSTGRES_PORT=15432
NGINX_PORT=8089
PHP_FPM_PORT=9009

# Feature-caching worktree
COMPOSE_PROJECT_NAME=claude-caching
POSTGRES_PORT=15433
NGINX_PORT=8090
PHP_FPM_PORT=9010

# Feature-restructure worktree
COMPOSE_PROJECT_NAME=claude-restructure
POSTGRES_PORT=15434
NGINX_PORT=8091
PHP_FPM_PORT=9011
```

**Результат**:

```bash
# Main контейнеры:
claude-main-backend-php83
claude-main-backend-psql16
claude-main-backend-nginx

# Feature-caching контейнеры:
claude-caching-backend-php83
claude-caching-backend-psql16
claude-caching-backend-nginx
```

**Никаких конфликтов!** ✅

### Проверка Запущенных Контейнеров

```bash
# Все контейнеры
docker ps

# Только main
docker ps | grep claude-main

# Только feature-caching
docker ps | grep claude-caching
```

---

## Bash Скрипты для Автоматизации

Для удобства создадим скрипты в директории `scripts/worktree/`.

### Скрипт 1: `worktree-create.sh`

**Автоматически создает worktree с правильными портами**

```bash
#!/bin/bash
# scripts/worktree/worktree-create.sh

set -e

BRANCH_NAME=$1
WORKTREE_NAME=$2

if [ -z "$BRANCH_NAME" ] || [ -z "$WORKTREE_NAME" ]; then
    echo "Usage: ./worktree-create.sh <branch-name> <worktree-name>"
    echo "Example: ./worktree-create.sh feature/new-api feature-new-api"
    exit 1
fi

MAIN_DIR="/Users/sollent/Desktop/Projects/CLAUDE"
WORKTREE_DIR="/Users/sollent/Desktop/Projects/CLAUDE-worktrees/$WORKTREE_NAME"

echo "🌳 Creating worktree for branch: $BRANCH_NAME"
echo "📂 Worktree directory: $WORKTREE_DIR"

# Переходим в main репозиторий
cd "$MAIN_DIR"

# Проверяем существует ли ветка
if git show-ref --verify --quiet refs/heads/"$BRANCH_NAME"; then
    echo "✅ Branch exists, creating worktree..."
    git worktree add "$WORKTREE_DIR" "$BRANCH_NAME"
else
    echo "🆕 Branch doesn't exist, creating new branch and worktree..."
    git worktree add -b "$BRANCH_NAME" "$WORKTREE_DIR"
fi

# Переходим в worktree
cd "$WORKTREE_DIR"

# Определяем следующий свободный порт
NEXT_NGINX_PORT=$(docker ps --format '{{.Ports}}' | grep -o '0.0.0.0:[0-9]*->80' | cut -d':' -f2 | cut -d'-' -f1 | sort -n | tail -1)
NEXT_NGINX_PORT=$((NEXT_NGINX_PORT + 1))
if [ -z "$NEXT_NGINX_PORT" ] || [ "$NEXT_NGINX_PORT" -lt 8090 ]; then
    NEXT_NGINX_PORT=8090
fi

NEXT_POSTGRES_PORT=$((15432 + (NEXT_NGINX_PORT - 8089)))
NEXT_PHP_FPM_PORT=$((9009 + (NEXT_NGINX_PORT - 8089)))

echo "🔧 Configuring environment with ports:"
echo "   Nginx: $NEXT_NGINX_PORT"
echo "   PostgreSQL: $NEXT_POSTGRES_PORT"
echo "   PHP-FPM: $NEXT_PHP_FPM_PORT"

# Создаем .env.docker
cat > .env.docker <<EOF
# Worktree: $WORKTREE_NAME
# Branch: $BRANCH_NAME
# Auto-generated by worktree-create.sh

COMPOSE_PROJECT_NAME=claude-${WORKTREE_NAME}

# PostgreSQL Configuration
POSTGRES_DB=backend-app-${WORKTREE_NAME}
POSTGRES_USER=user
POSTGRES_PASSWORD=password
POSTGRES_PORT=${NEXT_POSTGRES_PORT}

# RabbitMQ Configuration
RABBITMQ_USER=user
RABBITMQ_PASSWORD=password
RABBITMQ_PORT=5672
RABBITMQ_MANAGEMENT_PORT=15672

# Nginx Configuration
NGINX_PORT=${NEXT_NGINX_PORT}

# PHP-FPM Configuration
PHP_FPM_PORT=${NEXT_PHP_FPM_PORT}
EOF

echo "✅ Worktree created successfully!"
echo ""
echo "📋 Next steps:"
echo "   cd $WORKTREE_DIR"
echo "   docker-compose up -d"
echo "   docker exec backend-php83 composer install"
echo "   docker exec backend-php83 php bin/console doctrine:migrations:migrate"
echo ""
echo "🌐 Access URLs:"
echo "   Backend: http://localhost:$NEXT_NGINX_PORT/api"
echo "   Frontend: http://localhost:$((3000 + (NEXT_NGINX_PORT - 8089)))"
```

### Скрипт 2: `worktree-remove.sh`

**Безопасно удаляет worktree со всеми контейнерами**

```bash
#!/bin/bash
# scripts/worktree/worktree-remove.sh

set -e

WORKTREE_NAME=$1

if [ -z "$WORKTREE_NAME" ]; then
    echo "Usage: ./worktree-remove.sh <worktree-name>"
    echo "Example: ./worktree-remove.sh feature-new-api"
    exit 1
fi

MAIN_DIR="/Users/sollent/Desktop/Projects/CLAUDE"
WORKTREE_DIR="/Users/sollent/Desktop/Projects/CLAUDE-worktrees/$WORKTREE_NAME"

if [ ! -d "$WORKTREE_DIR" ]; then
    echo "❌ Worktree directory not found: $WORKTREE_DIR"
    exit 1
fi

echo "🗑️  Removing worktree: $WORKTREE_NAME"

# Останавливаем Docker контейнеры
echo "🐳 Stopping Docker containers..."
cd "$WORKTREE_DIR"
docker-compose down -v || echo "⚠️  Docker containers already stopped"

# Удаляем worktree
echo "📂 Removing worktree directory..."
cd "$MAIN_DIR"
git worktree remove "$WORKTREE_DIR" --force

echo "✅ Worktree removed successfully!"
echo ""
echo "💡 Don't forget to delete the branch if needed:"
echo "   git branch -d <branch-name>"
```

### Скрипт 3: `worktree-list.sh`

**Показывает все worktree с их статусами**

```bash
#!/bin/bash
# scripts/worktree/worktree-list.sh

MAIN_DIR="/Users/sollent/Desktop/Projects/CLAUDE"
cd "$MAIN_DIR"

echo "🌳 Git Worktree List:"
echo ""
git worktree list

echo ""
echo "🐳 Running Docker Containers:"
echo ""
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep claude || echo "No containers running"
```

### Установка Скриптов

```bash
# Создаем директорию для скриптов
mkdir -p scripts/worktree

# Делаем скрипты исполняемыми
chmod +x scripts/worktree/*.sh

# Добавляем в PATH (опционально)
echo 'export PATH="$PATH:/Users/sollent/Desktop/Projects/CLAUDE/scripts/worktree"' >> ~/.zshrc
source ~/.zshrc
```

---

## Работа с Claude Code в Worktree

### Открытие Нескольких Сессий

**Ключевой момент**: Каждый worktree - это **отдельная директория**, поэтому Claude Code видит их как независимые проекты.

**Рекомендуемый Workflow**:

```bash
# Terminal 1: Main ветка
cd /Users/sollent/Desktop/Projects/CLAUDE
claude-code
# Работаем над документацией, bug fixes

# Terminal 2: Feature-caching
cd /Users/sollent/Desktop/Projects/CLAUDE-worktrees/feature-caching
claude-code
# Реализуем Doctrine Query Cache

# Terminal 3: Feature-restructure
cd /Users/sollent/Desktop/Projects/CLAUDE-worktrees/feature-restructure
claude-code
# Рефакторим архитектуру проекта
```

### Управление Контекстом

**Проблема**: Claude Code может запутаться, если не понимает в каком worktree находится.

**Решение**: В начале каждой сессии напоминайте Claude:

```
Привет! Мы работаем в worktree "feature-caching".
Ветка: feature/implement-caching-functionality
Цель: Реализовать Doctrine Query Cache и Result Cache
Docker порты: 8090 (nginx), 15433 (postgres), 9010 (php-fpm)
```

### .claudeignore для Worktree

**Опционально**: Создайте `.claudeignore` в корне каждого worktree для исключения ненужных файлов:

```
# .claudeignore
.git/
var/cache/
var/log/
node_modules/
vendor/
```

---

## Best Practices

### ✅ DO (Рекомендации)

1. **Используйте отдельные порты** для каждого worktree
   - Main: 8089/15432/9009
   - Feature 1: 8090/15433/9010
   - Feature 2: 8091/15434/9011

2. **Изолируйте Docker контейнеры** через `COMPOSE_PROJECT_NAME`
   ```bash
   COMPOSE_PROJECT_NAME=claude-feature-name
   ```

3. **Регулярно синхронизируйте с main**
   ```bash
   git fetch origin main
   git merge origin/main  # Или rebase
   ```

4. **Удаляйте worktree после merge**
   ```bash
   ./scripts/worktree/worktree-remove.sh feature-name
   ```

5. **Документируйте активные worktree**
   ```bash
   git worktree list > ACTIVE_WORKTREES.txt
   ```

6. **Используйте automation скрипты**
   - Меньше ручной работы
   - Меньше ошибок

### ❌ DON'T (Чего Избегать)

1. **НЕ используйте одинаковые порты** в разных worktree
   - Приведет к конфликтам Docker

2. **НЕ коммитьте .env.docker** из worktree в git
   - Уже в .gitignore

3. **НЕ удаляйте worktree вручную** (`rm -rf`)
   - Используйте `git worktree remove`

4. **НЕ создавайте worktree внутри основного репозитория**
   ```bash
   # ❌ Плохо
   git worktree add ./worktrees/feature-x feature-x

   # ✅ Хорошо
   git worktree add ../CLAUDE-worktrees/feature-x feature-x
   ```

5. **НЕ забывайте останавливать Docker** в неиспользуемых worktree
   ```bash
   docker-compose down  # Освобождаем ресурсы
   ```

---

## Troubleshooting

### Проблема 1: "Port already in use"

**Симптомы**:
```
Error: bind: address already in use
```

**Причина**: Два worktree используют одинаковые порты.

**Решение**:

```bash
# Проверяем какие порты заняты
docker ps | grep 8089

# Останавливаем конфликтующий контейнер
docker-compose down

# Редактируем .env.docker в worktree
nano .env.docker
# Меняем NGINX_PORT=8090 (или следующий свободный)

# Перезапускаем
docker-compose up -d
```

### Проблема 2: "Worktree already exists"

**Симптомы**:
```
fatal: 'path/to/worktree' already exists
```

**Причина**: Worktree был удален неправильно (через `rm -rf`).

**Решение**:

```bash
# Очищаем "мертвый" worktree
git worktree prune

# Пересоздаем worktree
git worktree add ../CLAUDE-worktrees/feature-name feature-name
```

### Проблема 3: "Database does not exist"

**Симптомы**:
```
SQLSTATE[08006]: Connection failure: database "backend-app" does not exist
```

**Причина**: База данных не создана в новом worktree.

**Решение**:

```bash
# Проверяем что PostgreSQL контейнер запущен
docker ps | grep psql

# Создаем базу данных
docker exec backend-php83 php bin/console doctrine:database:create

# Запускаем миграции
docker exec backend-php83 php bin/console doctrine:migrations:migrate
```

### Проблема 4: "Git lock file exists"

**Симптомы**:
```
fatal: Unable to create '.git/index.lock': File exists.
```

**Причина**: Git операция была прервана.

**Решение**:

```bash
# Удаляем lock файл
rm -f .git/index.lock

# Повторяем операцию
git status
```

### Проблема 5: Контейнеры с одинаковыми именами

**Симптомы**:
```
Error: Conflict. The container name "/backend-php83" is already in use
```

**Причина**: `COMPOSE_PROJECT_NAME` не установлен.

**Решение**:

```bash
# Добавьте в .env.docker
echo "COMPOSE_PROJECT_NAME=claude-$(basename $(pwd))" >> .env.docker

# Перезапустите контейнеры
docker-compose down
docker-compose up -d
```

---

## Шпаргалка Команд

### Создание Worktree

```bash
# С новой веткой
git worktree add -b feature/new-feature ../CLAUDE-worktrees/feature-new-feature

# С существующей веткой
git worktree add ../CLAUDE-worktrees/feature-caching feature/implement-caching-functionality
```

### Управление Worktree

```bash
# Список всех worktree
git worktree list

# Удалить worktree
git worktree remove ../CLAUDE-worktrees/feature-name

# Очистить "мертвые" worktree
git worktree prune
```

### Docker Operations

```bash
# В каждом worktree запускаем отдельно
docker-compose up -d
docker-compose down
docker-compose down -v  # С удалением volumes

# Проверка контейнеров
docker ps | grep claude-
```

### Git Sync

```bash
# Получить изменения из main
git fetch origin main

# Merge или Rebase
git merge origin/main
git rebase origin/main

# Push изменений
git push origin feature/current-branch
```

---

## Дополнительные Ресурсы

- [Git Worktree Documentation](https://git-scm.com/docs/git-worktree)
- [Docker Compose Environment Variables](https://docs.docker.com/compose/environment-variables/)
- [Наш Development Workflow](DEVELOPMENT_WORKFLOW.md)
- [Environment Configuration Guide](ENVIRONMENT_CONFIGURATION.md)

---

**Последнее обновление**: 2025-11-14
**Версия документа**: 1.0
**Автор**: Claude Code AI

**Изменения v1.0**:
- ✅ Полная документация по Git Worktree стратегии
- ✅ Автоматизация через bash скрипты
- ✅ Изоляция Docker контейнеров
- ✅ Интеграция с Claude Code
- ✅ Best practices и troubleshooting
