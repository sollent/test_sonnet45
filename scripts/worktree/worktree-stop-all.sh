#!/bin/bash
# scripts/worktree/worktree-stop-all.sh
# Останавливает Docker контейнеры во всех worktrees

set -e

MAIN_DIR="/Users/sollent/Desktop/Projects/CLAUDE"

echo "🛑 Остановка всех Docker контейнеров в worktrees"
echo ""

# Функция для остановки контейнеров в директории
stop_containers() {
    local dir=$1
    local name=$2

    if [ -f "$dir/docker-compose.yml" ]; then
        echo "🐳 Останавливаю контейнеры в: $name"
        cd "$dir"
        docker-compose down 2>/dev/null || echo "   ⚠️  Контейнеры уже остановлены"
    else
        echo "⚠️  docker-compose.yml не найден в: $name"
    fi
}

# Останавливаем main
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
stop_containers "$MAIN_DIR" "main"

# Останавливаем все worktrees
if [ -d "/Users/sollent/Desktop/Projects/CLAUDE-worktrees" ]; then
    for worktree_dir in /Users/sollent/Desktop/Projects/CLAUDE-worktrees/*/; do
        if [ -d "$worktree_dir" ]; then
            worktree_name=$(basename "$worktree_dir")
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            stop_containers "$worktree_dir" "$worktree_name"
        fi
    done
fi

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "✅ Все контейнеры остановлены!"
echo ""

# Показываем статистику
RUNNING_CONTAINERS=$(docker ps --filter "name=claude-" --format "{{.Names}}" 2>/dev/null | wc -l)
echo "Запущенных контейнеров: $RUNNING_CONTAINERS"

if [ "$RUNNING_CONTAINERS" -gt 0 ]; then
    echo ""
    echo "⚠️  Некоторые контейнеры все еще запущены:"
    docker ps --filter "name=claude-" --format "table {{.Names}}\t{{.Status}}"
fi
echo ""
