#!/bin/bash
# scripts/worktree/worktree-list.sh
# Показывает все Git worktrees с их статусами и запущенными Docker контейнерами

MAIN_DIR="/Users/sollent/Desktop/Projects/CLAUDE"
cd "$MAIN_DIR"

echo "╔════════════════════════════════════════════════════════════════════╗"
echo "║             🌳 Git Worktree Status Dashboard 🌳                    ║"
echo "╚════════════════════════════════════════════════════════════════════╝"
echo ""

echo "📂 Git Worktrees:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
git worktree list
echo ""

echo "🐳 Docker Containers:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
CLAUDE_CONTAINERS=$(docker ps -a --filter "name=claude-" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" 2>/dev/null)

if [ -z "$CLAUDE_CONTAINERS" ] || [ "$CLAUDE_CONTAINERS" == "NAMES	STATUS	PORTS" ]; then
    echo "Нет запущенных контейнеров с префиксом 'claude-'"
else
    echo "$CLAUDE_CONTAINERS"
fi
echo ""

echo "🔌 Port Mappings:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Функция для извлечения портов из .env.docker
show_ports() {
    local env_file=$1
    local worktree_name=$2

    if [ -f "$env_file" ]; then
        local nginx_port=$(grep "^NGINX_PORT=" "$env_file" 2>/dev/null | cut -d'=' -f2)
        local postgres_port=$(grep "^POSTGRES_PORT=" "$env_file" 2>/dev/null | cut -d'=' -f2)
        local php_port=$(grep "^PHP_FPM_PORT=" "$env_file" 2>/dev/null | cut -d'=' -f2)
        local frontend_port=$(grep "^FRONTEND_PORT=" "$env_file" 2>/dev/null | cut -d'=' -f2)

        if [ -n "$nginx_port" ]; then
            printf "%-25s | Nginx: %-5s | PostgreSQL: %-5s | PHP-FPM: %-5s | Frontend: %-5s\n" \
                "$worktree_name" "$nginx_port" "$postgres_port" "$php_port" "${frontend_port:-N/A}"
        fi
    fi
}

# Main worktree
show_ports "$MAIN_DIR/.env.docker" "main"

# Все worktrees
if [ -d "/Users/sollent/Desktop/Projects/CLAUDE-worktrees" ]; then
    for worktree_dir in /Users/sollent/Desktop/Projects/CLAUDE-worktrees/*/; do
        if [ -d "$worktree_dir" ]; then
            worktree_name=$(basename "$worktree_dir")
            show_ports "${worktree_dir}.env.docker" "$worktree_name"
        fi
    done
fi

echo ""
echo "📊 Statistics:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

TOTAL_WORKTREES=$(git worktree list | wc -l)
RUNNING_CONTAINERS=$(docker ps --filter "name=claude-" --format "{{.Names}}" 2>/dev/null | wc -l)
STOPPED_CONTAINERS=$(docker ps -a --filter "name=claude-" --filter "status=exited" --format "{{.Names}}" 2>/dev/null | wc -l)

echo "Всего worktrees:           $TOTAL_WORKTREES"
echo "Запущенных контейнеров:    $RUNNING_CONTAINERS"
echo "Остановленных контейнеров: $STOPPED_CONTAINERS"

echo ""
echo "💡 Полезные команды:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Создать worktree:  ./scripts/worktree/worktree-create.sh <branch> <name>"
echo "  Удалить worktree:  ./scripts/worktree/worktree-remove.sh <name>"
echo "  Остановить все:    ./scripts/worktree/worktree-stop-all.sh"
echo ""
