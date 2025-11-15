#!/bin/bash
# scripts/docker-compose-wrapper.sh
# Wrapper для docker-compose который автоматически загружает переменные из .env
# Работает в main И worktree директориях

set -e

# Проверяем наличие .env файла
if [ -f .env ]; then
    # Экспортируем переменные из .env перед запуском docker-compose
    set -a
    source .env
    set +a
fi

# Запускаем docker-compose с переданными аргументами
exec docker-compose "$@"
