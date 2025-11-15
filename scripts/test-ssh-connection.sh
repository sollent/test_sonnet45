#!/bin/bash

# ================================================================
# SSH Connection Tester для GitHub Actions CI/CD
# ================================================================
# Тестирует SSH подключение к VDS для проверки настройки
# ================================================================
# Версия: 1.0
# Дата: 2025-11-15
# ================================================================

set -e

# Цвета
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Заголовок
echo -e "${BLUE}🔐 SSH Connection Tester${NC}"
echo "=================================================================="
echo ""

# Параметры по умолчанию
DEFAULT_HOST="45.129.186.88"
DEFAULT_USER="root"
DEFAULT_KEY="$HOME/.ssh/github_actions_taskflow"
DEFAULT_PROJECT_PATH="/opt/taskflow"

# Использование параметров или значений по умолчанию
VDS_HOST="${1:-$DEFAULT_HOST}"
VDS_USER="${2:-$DEFAULT_USER}"
SSH_KEY="${3:-$DEFAULT_KEY}"
PROJECT_PATH="${4:-$DEFAULT_PROJECT_PATH}"

# Функция вывода помощи
show_help() {
    echo "Использование:"
    echo "  $0 [HOST] [USER] [SSH_KEY] [PROJECT_PATH]"
    echo ""
    echo "Параметры:"
    echo "  HOST          IP адрес VDS (по умолчанию: $DEFAULT_HOST)"
    echo "  USER          SSH username (по умолчанию: $DEFAULT_USER)"
    echo "  SSH_KEY       Путь к приватному ключу (по умолчанию: $DEFAULT_KEY)"
    echo "  PROJECT_PATH  Путь к проекту на VDS (по умолчанию: $DEFAULT_PROJECT_PATH)"
    echo ""
    echo "Примеры:"
    echo "  $0                                    # Использовать значения по умолчанию"
    echo "  $0 45.129.186.88 root                # Другой host и user"
    echo "  $0 45.129.186.88 root ~/.ssh/my_key  # Другой SSH ключ"
    echo ""
    exit 0
}

# Проверка флага --help
if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    show_help
fi

# Вывод используемых параметров
echo -e "${BLUE}📋 Параметры подключения:${NC}"
echo -e "  Host:         ${GREEN}${VDS_HOST}${NC}"
echo -e "  User:         ${GREEN}${VDS_USER}${NC}"
echo -e "  SSH Key:      ${GREEN}${SSH_KEY}${NC}"
echo -e "  Project Path: ${GREEN}${PROJECT_PATH}${NC}"
echo ""

# Проверка наличия SSH ключа
echo -e "${BLUE}🔍 Проверка SSH ключа...${NC}"
if [ ! -f "$SSH_KEY" ]; then
    echo -e "${RED}❌ SSH ключ не найден: ${SSH_KEY}${NC}"
    echo ""
    echo -e "${YELLOW}Создайте SSH ключ:${NC}"
    echo -e "  ${GREEN}ssh-keygen -t ed25519 -C \"github-actions@taskflow\" -f $SSH_KEY -N \"\"${NC}"
    echo ""
    echo -e "${YELLOW}Затем добавьте публичный ключ на VDS:${NC}"
    echo -e "  ${GREEN}ssh-copy-id -i ${SSH_KEY}.pub ${VDS_USER}@${VDS_HOST}${NC}"
    echo ""
    exit 1
else
    echo -e "${GREEN}✅ SSH ключ найден${NC}"

    # Проверка прав на ключ
    KEY_PERMS=$(stat -f %A "$SSH_KEY" 2>/dev/null || stat -c %a "$SSH_KEY" 2>/dev/null)
    if [ "$KEY_PERMS" = "600" ]; then
        echo -e "${GREEN}✅ Права на ключ корректны (600)${NC}"
    else
        echo -e "${YELLOW}⚠️  Права на ключ неправильные ($KEY_PERMS вместо 600)${NC}"
        echo -e "${YELLOW}   Исправление прав...${NC}"
        chmod 600 "$SSH_KEY"
        echo -e "${GREEN}✅ Права исправлены${NC}"
    fi
fi
echo ""

# Тест 1: Базовое подключение
echo -e "${BLUE}📡 Тест 1: Базовое SSH подключение...${NC}"
if ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no -o ConnectTimeout=10 "${VDS_USER}@${VDS_HOST}" "echo 'Connection successful'" 2>/dev/null; then
    echo -e "${GREEN}✅ SSH подключение работает${NC}"
else
    echo -e "${RED}❌ SSH подключение не удалось${NC}"
    echo ""
    echo -e "${YELLOW}Возможные причины:${NC}"
    echo "  1. Публичный ключ не добавлен на VDS"
    echo "  2. VDS недоступен"
    echo "  3. Firewall блокирует SSH"
    echo ""
    echo -e "${YELLOW}Попробуйте добавить ключ вручную:${NC}"
    echo -e "  ${GREEN}ssh-copy-id -i ${SSH_KEY}.pub ${VDS_USER}@${VDS_HOST}${NC}"
    echo ""
    echo -e "${YELLOW}Или проверьте подключение:${NC}"
    echo -e "  ${GREEN}ssh -v ${VDS_USER}@${VDS_HOST}${NC}"
    echo ""
    exit 1
fi
echo ""

# Тест 2: Проверка прав на VDS
echo -e "${BLUE}🔐 Тест 2: Проверка прав на VDS...${NC}"
VDS_KEY_PERMS=$(ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "${VDS_USER}@${VDS_HOST}" "stat -c %a ~/.ssh/authorized_keys 2>/dev/null || stat -f %A ~/.ssh/authorized_keys 2>/dev/null")

if [ -n "$VDS_KEY_PERMS" ]; then
    if [ "$VDS_KEY_PERMS" = "600" ]; then
        echo -e "${GREEN}✅ Права на authorized_keys корректны (600)${NC}"
    else
        echo -e "${YELLOW}⚠️  Права на authorized_keys неправильные ($VDS_KEY_PERMS вместо 600)${NC}"
        echo -e "${YELLOW}   Рекомендуется исправить:${NC}"
        echo -e "   ${GREEN}ssh ${VDS_USER}@${VDS_HOST} 'chmod 600 ~/.ssh/authorized_keys'${NC}"
    fi
else
    echo -e "${YELLOW}⚠️  Не удалось проверить права${NC}"
fi
echo ""

# Тест 3: Проверка наличия проекта на VDS
echo -e "${BLUE}📁 Тест 3: Проверка проекта на VDS...${NC}"
if ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "${VDS_USER}@${VDS_HOST}" "[ -d ${PROJECT_PATH} ]" 2>/dev/null; then
    echo -e "${GREEN}✅ Директория проекта существует: ${PROJECT_PATH}${NC}"

    # Проверка содержимого
    PROJECT_CONTENT=$(ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "${VDS_USER}@${VDS_HOST}" "ls -1 ${PROJECT_PATH} 2>/dev/null | head -5")
    if [ -n "$PROJECT_CONTENT" ]; then
        echo -e "${BLUE}   Содержимое (первые 5 файлов):${NC}"
        echo "$PROJECT_CONTENT" | while read line; do
            echo -e "   ${GREEN}└─${NC} $line"
        done
    fi
else
    echo -e "${RED}❌ Директория проекта НЕ существует: ${PROJECT_PATH}${NC}"
    echo ""
    echo -e "${YELLOW}Создайте директорию на VDS:${NC}"
    echo -e "  ${GREEN}ssh ${VDS_USER}@${VDS_HOST} 'mkdir -p ${PROJECT_PATH}'${NC}"
    echo ""
    echo -e "${YELLOW}Или клонируйте репозиторий:${NC}"
    echo -e "  ${GREEN}ssh ${VDS_USER}@${VDS_HOST} 'git clone https://github.com/YOUR_USERNAME/test_sonnet45.git ${PROJECT_PATH}'${NC}"
    echo ""
fi
echo ""

# Тест 4: Проверка Docker на VDS
echo -e "${BLUE}🐳 Тест 4: Проверка Docker на VDS...${NC}"
DOCKER_VERSION=$(ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "${VDS_USER}@${VDS_HOST}" "docker --version 2>/dev/null")

if [ -n "$DOCKER_VERSION" ]; then
    echo -e "${GREEN}✅ Docker установлен: ${DOCKER_VERSION}${NC}"

    # Проверка запущен ли Docker daemon
    if ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "${VDS_USER}@${VDS_HOST}" "docker ps >/dev/null 2>&1"; then
        echo -e "${GREEN}✅ Docker daemon работает${NC}"

        # Количество запущенных контейнеров
        CONTAINER_COUNT=$(ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "${VDS_USER}@${VDS_HOST}" "docker ps -q | wc -l" | tr -d ' ')
        echo -e "${BLUE}   Запущено контейнеров: ${CONTAINER_COUNT}${NC}"
    else
        echo -e "${YELLOW}⚠️  Docker daemon не запущен${NC}"
        echo -e "${YELLOW}   Запустите Docker:${NC}"
        echo -e "   ${GREEN}ssh ${VDS_USER}@${VDS_HOST} 'systemctl start docker'${NC}"
    fi
else
    echo -e "${RED}❌ Docker НЕ установлен${NC}"
    echo -e "${YELLOW}   Установите Docker:${NC}"
    echo -e "   ${GREEN}ssh ${VDS_USER}@${VDS_HOST} 'apt update && apt install -y docker.io docker-compose-v2'${NC}"
fi
echo ""

# Тест 5: Проверка Git на VDS
echo -e "${BLUE}🌿 Тест 5: Проверка Git на VDS...${NC}"
GIT_VERSION=$(ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "${VDS_USER}@${VDS_HOST}" "git --version 2>/dev/null")

if [ -n "$GIT_VERSION" ]; then
    echo -e "${GREEN}✅ Git установлен: ${GIT_VERSION}${NC}"
else
    echo -e "${RED}❌ Git НЕ установлен${NC}"
    echo -e "${YELLOW}   Установите Git:${NC}"
    echo -e "   ${GREEN}ssh ${VDS_USER}@${VDS_HOST} 'apt update && apt install -y git'${NC}"
fi
echo ""

# Тест 6: Выполнение тестовой команды
echo -e "${BLUE}🧪 Тест 6: Выполнение тестовой команды...${NC}"
TEST_RESULT=$(ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "${VDS_USER}@${VDS_HOST}" "echo 'CI/CD Test: SSH OK' && date")

if [ -n "$TEST_RESULT" ]; then
    echo -e "${GREEN}✅ Выполнение команд работает${NC}"
    echo -e "${BLUE}   Результат:${NC}"
    echo "$TEST_RESULT" | while read line; do
        echo -e "   ${GREEN}│${NC} $line"
    done
else
    echo -e "${RED}❌ Не удалось выполнить команду${NC}"
fi
echo ""

# Тест 7: Проверка latency (задержка)
echo -e "${BLUE}⏱️  Тест 7: Проверка latency...${NC}"
LATENCY_START=$(date +%s%N)
ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "${VDS_USER}@${VDS_HOST}" "exit" 2>/dev/null
LATENCY_END=$(date +%s%N)
LATENCY_MS=$(( (LATENCY_END - LATENCY_START) / 1000000 ))

if [ $LATENCY_MS -lt 100 ]; then
    echo -e "${GREEN}✅ Отличная задержка: ${LATENCY_MS}ms${NC}"
elif [ $LATENCY_MS -lt 300 ]; then
    echo -e "${GREEN}✅ Хорошая задержка: ${LATENCY_MS}ms${NC}"
elif [ $LATENCY_MS -lt 1000 ]; then
    echo -e "${YELLOW}⚠️  Средняя задержка: ${LATENCY_MS}ms${NC}"
else
    echo -e "${RED}❌ Высокая задержка: ${LATENCY_MS}ms${NC}"
fi
echo ""

# Итоговый отчет
echo "=================================================================="
echo -e "${BLUE}📊 Итоговый Отчет${NC}"
echo "=================================================================="
echo ""

TOTAL_TESTS=7
PASSED_TESTS=0

# Подсчет успешных тестов
if [ -f "$SSH_KEY" ]; then ((PASSED_TESTS++)); fi
if ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "${VDS_USER}@${VDS_HOST}" "exit" 2>/dev/null; then ((PASSED_TESTS++)); fi
if [ "$VDS_KEY_PERMS" = "600" ] || [ -n "$VDS_KEY_PERMS" ]; then ((PASSED_TESTS++)); fi
if ssh -i "$SSH_KEY" -o StrictHostKeyChecking=no "${VDS_USER}@${VDS_HOST}" "[ -d ${PROJECT_PATH} ]" 2>/dev/null; then ((PASSED_TESTS++)); fi
if [ -n "$DOCKER_VERSION" ]; then ((PASSED_TESTS++)); fi
if [ -n "$GIT_VERSION" ]; then ((PASSED_TESTS++)); fi
if [ -n "$TEST_RESULT" ]; then ((PASSED_TESTS++)); fi

PERCENTAGE=$((PASSED_TESTS * 100 / TOTAL_TESTS))

echo -e "Всего тестов:     ${TOTAL_TESTS}"
echo -e "Успешно:          ${GREEN}${PASSED_TESTS}${NC}"
echo -e "Неуспешно:        ${RED}$((TOTAL_TESTS - PASSED_TESTS))${NC}"
echo -e "Готовность:       ${GREEN}${PERCENTAGE}%${NC}"
echo ""

if [ $PERCENTAGE -eq 100 ]; then
    echo -e "${GREEN}✅ ВСЕ ТЕСТЫ ПРОЙДЕНЫ!${NC}"
    echo ""
    echo -e "${GREEN}🎉 SSH подключение полностью готово для CI/CD!${NC}"
    echo ""
    echo -e "Следующие шаги:"
    echo -e "1. Скопируйте приватный ключ для GitHub Secrets:"
    echo -e "   ${GREEN}cat ${SSH_KEY}${NC}"
    echo -e "2. GitHub → Settings → Secrets → Actions → New secret"
    echo -e "3. Name: ${GREEN}VDS_SSH_KEY${NC}"
    echo -e "4. Value: [весь вывод cat команды, включая BEGIN/END строки]"
    echo ""
elif [ $PERCENTAGE -ge 70 ]; then
    echo -e "${YELLOW}⚠️  ПОЧТИ ГОТОВО (${PERCENTAGE}%)${NC}"
    echo ""
    echo -e "Устраните оставшиеся проблемы выше."
    echo ""
else
    echo -e "${RED}❌ НЕ ГОТОВО (${PERCENTAGE}%)${NC}"
    echo ""
    echo -e "Необходимо исправить критические проблемы!"
    echo ""
fi

# Exit code
if [ $PERCENTAGE -eq 100 ]; then
    exit 0
else
    exit 1
fi
