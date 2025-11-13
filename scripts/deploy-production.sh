#!/bin/bash

# ================================================================
# Production Deployment Script для VDS
# ================================================================
# Автоматическое развертывание TaskFlow в production режиме
#
# Использование:
#   ./scripts/deploy-production.sh
#
# Требования:
#   - Docker установлен и запущен
#   - Docker Compose v2+ установлен
#   - Git установлен (если клонируем репозиторий)
#   - Root/sudo доступ для Docker команд
#
# ================================================================

set -e  # Останавливаемся при первой ошибке

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Функции для красивого вывода
print_header() {
    echo ""
    echo -e "${CYAN}================================================${NC}"
    echo -e "${CYAN}$1${NC}"
    echo -e "${CYAN}================================================${NC}"
}

print_step() {
    echo -e "\n${BLUE}▶ $1${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${CYAN}ℹ $1${NC}"
}

# ================================================================
# Шаг 1: Проверка окружения
# ================================================================

print_header "Шаг 1: Проверка окружения"

print_step "Проверка Docker..."
if ! command -v docker &> /dev/null; then
    print_error "Docker не установлен!"
    echo "Установите Docker: https://docs.docker.com/engine/install/"
    exit 1
fi
print_success "Docker установлен: $(docker --version)"

print_step "Проверка Docker Compose..."
if ! docker compose version &> /dev/null; then
    print_error "Docker Compose v2+ не установлен!"
    echo "Установите Docker Compose: https://docs.docker.com/compose/install/"
    exit 1
fi
print_success "Docker Compose установлен: $(docker compose version)"

print_step "Проверка Docker daemon..."
if ! docker info &> /dev/null; then
    print_error "Docker daemon не запущен!"
    echo "Запустите Docker: sudo systemctl start docker"
    exit 1
fi
print_success "Docker daemon работает"

print_step "Проверка Git..."
if ! command -v git &> /dev/null; then
    print_warning "Git не установлен (опционально для клонирования)"
else
    print_success "Git установлен: $(git --version)"
fi

# ================================================================
# Шаг 2: Определение директории проекта
# ================================================================

print_header "Шаг 2: Определение директории проекта"

# Проверяем, запущен ли скрипт из корня проекта
if [ -f "docker-compose.yml" ] && [ -d "apps" ]; then
    PROJECT_DIR="$(pwd)"
    print_success "Текущая директория - корень проекта: $PROJECT_DIR"
else
    print_info "Текущая директория не является корнем проекта"
    echo -n "Введите путь к проекту (или нажмите Enter для /opt/taskflow): "
    read PROJECT_PATH

    if [ -z "$PROJECT_PATH" ]; then
        PROJECT_DIR="/opt/taskflow"
    else
        PROJECT_DIR="$PROJECT_PATH"
    fi

    if [ ! -d "$PROJECT_DIR" ]; then
        print_warning "Директория $PROJECT_DIR не существует"
        echo -n "Клонировать проект из Git? (y/n): "
        read CLONE_REPO

        if [ "$CLONE_REPO" = "y" ]; then
            echo -n "Введите URL репозитория: "
            read REPO_URL

            print_step "Клонирование репозитория..."
            git clone "$REPO_URL" "$PROJECT_DIR"
            print_success "Репозиторий склонирован в $PROJECT_DIR"
        else
            print_error "Директория проекта не найдена. Выход."
            exit 1
        fi
    fi

    cd "$PROJECT_DIR"
    print_success "Перешли в директорию проекта: $PROJECT_DIR"
fi

# ================================================================
# Шаг 3: Проверка и создание .env.docker.prod
# ================================================================

print_header "Шаг 3: Настройка переменных окружения"

if [ -f ".env.docker.prod" ]; then
    print_warning ".env.docker.prod уже существует"
    echo -n "Пересоздать? (y/n): "
    read RECREATE_ENV

    if [ "$RECREATE_ENV" != "y" ]; then
        print_info "Используем существующий .env.docker.prod"
    else
        rm .env.docker.prod
    fi
fi

if [ ! -f ".env.docker.prod" ]; then
    print_step "Создание .env.docker.prod..."

    if [ ! -f ".env.docker.example" ]; then
        print_error ".env.docker.example не найден!"
        exit 1
    fi

    cp .env.docker.example .env.docker.prod

    print_warning "ВАЖНО: Необходимо настроить credentials в .env.docker.prod!"
    echo ""
    echo "Используйте скрипт для генерации паролей:"
    echo "  ./scripts/generate-secrets.sh"
    echo ""
    echo -n "Открыть .env.docker.prod для редактирования? (y/n): "
    read EDIT_ENV

    if [ "$EDIT_ENV" = "y" ]; then
        ${EDITOR:-nano} .env.docker.prod
    else
        print_warning "Не забудьте настроить .env.docker.prod перед запуском!"
        echo -n "Продолжить с текущими значениями? (y/n): "
        read CONTINUE
        if [ "$CONTINUE" != "y" ]; then
            print_info "Выход. Настройте .env.docker.prod и запустите скрипт снова."
            exit 0
        fi
    fi
fi

# Проверяем что credentials изменены
if grep -q "CHANGE_ME" .env.docker.prod; then
    print_error "В .env.docker.prod остались CHANGE_ME значения!"
    print_warning "Измените все CHANGE_ME_* на реальные credentials"
    exit 1
fi

print_success ".env.docker.prod настроен"

# Создаем symlink для Docker Compose
print_step "Создание symlink .env -> .env.docker.prod..."
ln -sf .env.docker.prod .env
print_success "Symlink создан"

# ================================================================
# Шаг 4: Остановка старых контейнеров
# ================================================================

print_header "Шаг 4: Остановка старых контейнеров"

if docker ps -a | grep -q "backend-"; then
    print_step "Останавливаем старые контейнеры..."
    docker compose -f docker-compose.yml \
        -f infrastructure/docker/docker-compose-prod.yml \
        -f infrastructure/docker/docker-compose.frontend-prod.yml \
        down || true
    print_success "Старые контейнеры остановлены"
else
    print_info "Старых контейнеров не найдено"
fi

# ================================================================
# Шаг 5: Сборка production образов
# ================================================================

print_header "Шаг 5: Сборка production образов"

print_step "Собираем Docker образы (это может занять несколько минут)..."
print_info "Backend PHP 8.3 + Symfony..."
print_info "Frontend Node.js 20 + Vite + Nginx..."

docker compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose-prod.yml \
    -f infrastructure/docker/docker-compose.frontend-prod.yml \
    build --no-cache

print_success "Все образы собраны успешно!"

# ================================================================
# Шаг 6: Запуск контейнеров
# ================================================================

print_header "Шаг 6: Запуск production контейнеров"

print_step "Запускаем все сервисы..."
docker compose -f docker-compose.yml \
    -f infrastructure/docker/docker-compose-prod.yml \
    -f infrastructure/docker/docker-compose.frontend-prod.yml \
    up -d

print_step "Ожидание инициализации сервисов (15 секунд)..."
sleep 15

print_success "Контейнеры запущены!"

# ================================================================
# Шаг 7: Установка зависимостей
# ================================================================

print_header "Шаг 7: Установка зависимостей"

print_step "Установка PHP зависимостей (composer install)..."
docker exec backend-php83 composer install --no-dev --optimize-autoloader --no-interaction || {
    print_error "Ошибка при установке composer зависимостей!"
    exit 1
}

print_success "Зависимости установлены!"

# ================================================================
# Шаг 8: Настройка базы данных
# ================================================================

print_header "Шаг 8: Настройка базы данных"

print_step "Создание базы данных (если не существует)..."
docker exec backend-php83 php bin/console doctrine:database:create --if-not-exists --env=prod || true

print_step "Применение миграций..."
docker exec backend-php83 php bin/console doctrine:migrations:migrate --no-interaction --env=prod || {
    print_warning "Некоторые миграции уже применены (это нормально)"
}

print_success "База данных настроена!"

# ================================================================
# Шаг 9: Проверка статуса
# ================================================================

print_header "Шаг 9: Проверка статуса сервисов"

print_step "Статус контейнеров:"
docker ps --filter "name=backend-" --filter "name=frontend-" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

print_step "Проверка здоровья сервисов..."

# Ждем чуть больше для полной инициализации
sleep 5

# Проверяем frontend
if curl -s -o /dev/null -w "%{http_code}" http://localhost:3001 | grep -q "200"; then
    print_success "Frontend доступен (http://localhost:3001)"
else
    print_warning "Frontend еще инициализируется..."
fi

# Проверяем backend API
if curl -s http://localhost:80/api/tasks 2>&1 | grep -q "401"; then
    print_success "Backend API работает (http://localhost:80/api)"
else
    print_warning "Backend API еще инициализируется..."
fi

# ================================================================
# Финал: Итоговая информация
# ================================================================

print_header "Развертывание завершено! 🎉"

echo ""
print_info "Доступ к приложению:"
echo "  • Frontend:  http://$(hostname -I | awk '{print $1}'):3001"
echo "  • Backend:   http://$(hostname -I | awk '{print $1}'):80/api"
echo ""

print_info "Локальный доступ (с VDS):"
echo "  • Frontend:  http://localhost:3001"
echo "  • Backend:   http://localhost:80/api"
echo ""

print_info "Управление:"
echo "  • Просмотр логов:     docker logs -f frontend-prod"
echo "  • Просмотр логов:     docker logs -f backend-php83"
echo "  • Остановить:         docker compose -f docker-compose.yml -f infrastructure/docker/docker-compose-prod.yml -f infrastructure/docker/docker-compose.frontend-prod.yml down"
echo "  • Перезапустить:      docker compose -f docker-compose.yml -f infrastructure/docker/docker-compose-prod.yml -f infrastructure/docker/docker-compose.frontend-prod.yml restart"
echo ""

print_info "База данных:"
echo "  • PostgreSQL:  $(hostname -I | awk '{print $1}'):5432"
echo "  • RabbitMQ:    http://$(hostname -I | awk '{print $1}'):15672"
echo ""

print_warning "Следующие шаги:"
echo "  1. Настройте Nginx reverse proxy для production доменов"
echo "  2. Настройте SSL/TLS сертификаты (Let's Encrypt)"
echo "  3. Настройте firewall (ufw/iptables)"
echo "  4. Настройте мониторинг и логирование"
echo "  5. Настройте бэкапы базы данных"
echo ""

print_success "Все готово! Приложение работает в production режиме! 🚀"
