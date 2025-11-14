#!/bin/bash
# scripts/worktree/worktree-remove.sh
# Безопасно удаляет Git worktree со всеми Docker контейнерами

set -e

WORKTREE_NAME=$1
FORCE_REMOVE=${2:-false}

if [ -z "$WORKTREE_NAME" ]; then
    echo "❌ Использование: ./worktree-remove.sh <worktree-name> [--force]"
    echo ""
    echo "Примеры:"
    echo "  ./worktree-remove.sh feature-new-api"
    echo "  ./worktree-remove.sh feature-caching --force"
    echo ""
    echo "Доступные worktrees:"
    git worktree list
    exit 1
fi

MAIN_DIR="/Users/sollent/Desktop/Projects/CLAUDE"
WORKTREE_DIR="/Users/sollent/Desktop/Projects/CLAUDE-worktrees/$WORKTREE_NAME"

if [ ! -d "$WORKTREE_DIR" ]; then
    echo "❌ Директория worktree не найдена: $WORKTREE_DIR"
    echo ""
    echo "Доступные worktrees:"
    cd "$MAIN_DIR"
    git worktree list
    exit 1
fi

echo "🗑️  Удаление worktree: $WORKTREE_NAME"
echo "📂 Директория: $WORKTREE_DIR"
echo ""

# Проверяем есть ли незакоммиченные изменения
cd "$WORKTREE_DIR"
if [ -n "$(git status --porcelain)" ] && [ "$FORCE_REMOVE" != "--force" ]; then
    echo "⚠️  ВНИМАНИЕ: Обнаружены незакоммиченные изменения!"
    echo ""
    git status --short
    echo ""
    echo "Варианты:"
    echo "  1. Закоммитить изменения: git add . && git commit -m 'message'"
    echo "  2. Удалить принудительно: ./worktree-remove.sh $WORKTREE_NAME --force"
    echo ""
    read -p "Продолжить удаление? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "❌ Удаление отменено"
        exit 1
    fi
fi

# Останавливаем и удаляем Docker контейнеры
echo "🐳 Остановка Docker контейнеров..."
if [ -f docker-compose.yml ]; then
    docker-compose down -v 2>/dev/null || echo "⚠️  Docker контейнеры уже остановлены"
else
    echo "⚠️  docker-compose.yml не найден, пропускаем остановку контейнеров"
fi

# Удаляем worktree через Git
echo "📂 Удаление worktree директории..."
cd "$MAIN_DIR"

if [ "$FORCE_REMOVE" == "--force" ]; then
    git worktree remove "$WORKTREE_DIR" --force
else
    git worktree remove "$WORKTREE_DIR"
fi

echo ""
echo "✅ Worktree успешно удален!"
echo ""
echo "💡 Дополнительные действия:"
echo ""
echo "1. Если ветка больше не нужна, удалите её:"
echo "   git branch -d <branch-name>"
echo ""
echo "2. Если ветка была запушена на remote:"
echo "   git push origin --delete <branch-name>"
echo ""
echo "3. Проверить оставшиеся worktrees:"
echo "   git worktree list"
echo ""
