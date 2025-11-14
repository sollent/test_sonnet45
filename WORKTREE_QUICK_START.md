# 🌳 Git Worktree - Краткая Шпаргалка

> **Быстрый старт для работы с Git Worktree**

## ⚡ Make Команды (Рекомендуется!)

Все операции с worktree доступны через **короткие Make команды**:

```bash
# Создание worktree
make wt-create BRANCH=feature/name NAME=feature-name

# Список всех worktrees
make wt-list

# Удаление worktree
make wt-remove NAME=feature-name

# Остановка всех Docker контейнеров
make wt-stop

# Помощь по всем командам
make help
```

**Полные названия команд:**
- `make worktree-create` (alias: `wt-create`)
- `make worktree-list` (aliases: `wt-list`, `wt-ls`)
- `make worktree-remove` (aliases: `wt-remove`, `wt-rm`)
- `make worktree-stop-all` (alias: `wt-stop`)

---

## 🚀 Основные Команды

### Создание Worktree

```bash
# Через Make (рекомендуется) ⚡
make worktree-create BRANCH=feature/new-feature NAME=feature-new-feature
# Или короткий alias:
make wt-create BRANCH=feature/new-feature NAME=feature-new-feature

# Через скрипт напрямую
./scripts/worktree/worktree-create.sh feature/new-feature feature-new-feature

# Вручную (для продвинутых)
git worktree add -b feature/new-feature ../CLAUDE-worktrees/feature-new-feature
```

### Список Worktrees

```bash
# Через Make (рекомендуется) ⚡
make worktree-list
# Или короткие aliases:
make wt-list
make wt-ls

# Через скрипт напрямую
./scripts/worktree/worktree-list.sh

# Простой список (только Git, без Docker info)
git worktree list
```

### Удаление Worktree

```bash
# Через Make (рекомендуется) ⚡
make worktree-remove NAME=feature-new-feature
# Или короткие aliases:
make wt-remove NAME=feature-new-feature
make wt-rm NAME=feature-new-feature

# Через скрипт напрямую
./scripts/worktree/worktree-remove.sh feature-new-feature

# Вручную (для продвинутых)
cd /Users/sollent/Desktop/Projects/CLAUDE
git worktree remove ../CLAUDE-worktrees/feature-new-feature
```

### Остановка Всех Docker Контейнеров

```bash
# Через Make (рекомендуется) ⚡
make worktree-stop-all
# Или короткий alias:
make wt-stop

# Через скрипт напрямую
./scripts/worktree/worktree-stop-all.sh
```

---

## 📋 Типичный Workflow

### 1. Создать новый feature worktree

```bash
# Через Make ⚡
make wt-create BRANCH=feature/caching NAME=feature-caching

# Или через скрипт
./scripts/worktree/worktree-create.sh feature/caching feature-caching
```

### 2. Перейти в worktree и запустить Docker

```bash
cd /Users/sollent/Desktop/Projects/CLAUDE-worktrees/feature-caching
docker-compose up -d
```

### 3. Установить зависимости и запустить миграции

```bash
docker exec backend-php83 composer install
docker exec backend-php83 php bin/console doctrine:database:create
docker exec backend-php83 php bin/console doctrine:migrations:migrate
```

### 4. Открыть Claude Code сессию

```bash
claude-code
```

### 5. После завершения работы - удалить worktree

```bash
# Через Make ⚡
make wt-remove NAME=feature-caching

# Или через скрипт
./scripts/worktree/worktree-remove.sh feature-caching
```

---

## 🔌 Порты для Worktrees

| Worktree | Nginx | PostgreSQL | PHP-FPM | Frontend |
|----------|-------|------------|---------|----------|
| **main** | 8089 | 15432 | 9009 | 3000 |
| **worktree-1** | 8090 | 15433 | 9010 | 3001 |
| **worktree-2** | 8091 | 15434 | 9011 | 3002 |
| **worktree-3** | 8092 | 15435 | 9012 | 3003 |

> **Скрипт `worktree-create.sh` автоматически определяет следующий свободный порт!**

---

## 💡 Полезные Советы

### Проверить статус всех worktrees

```bash
# Через Make ⚡
make wt-list

# Или через скрипт
./scripts/worktree/worktree-list.sh
```

### Синхронизация с main

```bash
# В worktree директории
git fetch origin main
git merge origin/main  # или git rebase origin/main
```

### Проверить запущенные Docker контейнеры

```bash
docker ps | grep claude-
```

### Остановить Docker в конкретном worktree

```bash
cd /Users/sollent/Desktop/Projects/CLAUDE-worktrees/feature-caching
docker-compose down
```

---

## 📚 Полная Документация

Для подробной информации смотрите:
- [`docs/guides/GIT_WORKTREE_STRATEGY.md`](docs/guides/GIT_WORKTREE_STRATEGY.md)
- [`docs/INDEX.md`](docs/INDEX.md)

---

## ⚠️ Важно Помнить

1. **Каждый worktree** использует **уникальные порты** (автоматически назначаются скриптом)
2. **COMPOSE_PROJECT_NAME** в `.env.docker` изолирует Docker контейнеры
3. **Не удаляйте worktree вручную** через `rm -rf` - используйте скрипт или `git worktree remove`
4. **Всегда останавливайте Docker** перед удалением worktree

---

**Создано**: 2025-11-14
**Для вопросов**: см. полную документацию
