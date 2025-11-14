# 🌳 Git Worktree - Краткая Шпаргалка

> **Быстрый старт для работы с Git Worktree**

## 🚀 Основные Команды

### Создание Worktree

```bash
# Автоматически (рекомендуется) - использует скрипт для настройки портов
./scripts/worktree/worktree-create.sh feature/new-feature feature-new-feature

# Вручную
git worktree add -b feature/new-feature ../CLAUDE-worktrees/feature-new-feature
```

### Список Worktrees

```bash
# Показать все worktrees с Docker контейнерами и портами
./scripts/worktree/worktree-list.sh

# Простой список
git worktree list
```

### Удаление Worktree

```bash
# Автоматически (рекомендуется) - останавливает Docker контейнеры
./scripts/worktree/worktree-remove.sh feature-new-feature

# Вручную
cd /Users/sollent/Desktop/Projects/CLAUDE
git worktree remove ../CLAUDE-worktrees/feature-new-feature
```

### Остановка Всех Docker Контейнеров

```bash
# Останавливает Docker во всех worktrees
./scripts/worktree/worktree-stop-all.sh
```

---

## 📋 Типичный Workflow

### 1. Создать новый feature worktree

```bash
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
