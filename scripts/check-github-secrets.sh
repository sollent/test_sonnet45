#!/bin/bash

# ================================================================
# GitHub Secrets Checker for CI/CD
# ================================================================
# Проверяет что все необходимые секреты заданы локально
# перед настройкой в GitHub
# ================================================================
# Версия: 1.0
# Дата: 2025-11-15
# ================================================================

set -e

# Цвета для вывода
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Заголовок
echo -e "${BLUE}🔐 GitHub Secrets Checker${NC}"
echo "=================================================================="
echo ""

# Путь к файлу с секретами (если существует)
SECRETS_FILE="$HOME/taskflow-production-secrets.txt"

# Проверка наличия файла
if [ ! -f "$SECRETS_FILE" ]; then
    echo -e "${YELLOW}⚠️  Файл секретов не найден: $SECRETS_FILE${NC}"
    echo -e "${YELLOW}   Создайте файл командой:${NC}"
    echo -e "   ${GREEN}nano $SECRETS_FILE${NC}"
    echo ""
    echo -e "${YELLOW}   Или используйте другой путь:${NC}"
    echo -e "   ${GREEN}./scripts/check-github-secrets.sh /path/to/your/secrets.txt${NC}"
    echo ""
    exit 1
fi

# Использовать переданный путь если есть
if [ -n "$1" ]; then
    SECRETS_FILE="$1"
fi

echo -e "📋 Проверка файла: ${GREEN}$SECRETS_FILE${NC}"
echo ""

# Счетчики
TOTAL_SECRETS=12
FOUND_SECRETS=0
MISSING_SECRETS=0

# Список необходимых секретов
declare -A REQUIRED_SECRETS=(
    ["PROD_POSTGRES_PASSWORD"]="PostgreSQL password для production"
    ["PROD_RABBITMQ_PASSWORD"]="RabbitMQ password для production"
    ["PROD_APP_SECRET"]="Symfony APP_SECRET (32 hex символа)"
    ["PROD_JWT_PASSPHRASE"]="JWT Passphrase (64 hex символа)"
    ["PROD_GOOGLE_CLIENT_ID"]="Google OAuth Client ID"
    ["PROD_GOOGLE_CLIENT_SECRET"]="Google OAuth Client Secret"
    ["VDS_HOST"]="IP адрес VDS (например: 45.129.186.88)"
    ["VDS_USER"]="SSH username (например: root)"
    ["VDS_SSH_KEY"]="Private SSH key для GitHub Actions"
    ["VDS_PROJECT_PATH"]="Путь к проекту на VDS (например: /opt/taskflow)"
    ["TELEGRAM_BOT_TOKEN"]="Telegram Bot Token (формат: 7000000000:AAH...)"
    ["TELEGRAM_CHAT_ID"]="Telegram Chat ID (числовой ID)"
)

# Функция проверки секрета в файле
check_secret() {
    local secret_name=$1
    local secret_description=$2

    # Поиск секрета в файле (игнорируем комментарии)
    local found=$(grep -E "^${secret_name}=" "$SECRETS_FILE" 2>/dev/null | grep -v "^#" | cut -d= -f2- | tr -d '"' | tr -d "'")

    if [ -n "$found" ] && [ "$found" != "" ] && [ "$found" != "CHANGE_ME" ]; then
        echo -e "  ${GREEN}✅${NC} ${secret_name}"
        echo -e "     ${BLUE}ℹ${NC}  ${secret_description}"

        # Показать часть значения (первые 10 символов)
        local preview="${found:0:10}..."
        echo -e "     ${BLUE}📋${NC} Значение: ${preview}"

        ((FOUND_SECRETS++))
    else
        echo -e "  ${RED}❌${NC} ${secret_name} ${YELLOW}(НЕ НАЙДЕН или пустой)${NC}"
        echo -e "     ${BLUE}ℹ${NC}  ${secret_description}"
        ((MISSING_SECRETS++))
    fi
    echo ""
}

# Группа 1: Production Credentials
echo -e "${BLUE}📦 Группа 1: Production Credentials (6 secrets)${NC}"
echo "=================================================================="
check_secret "PROD_POSTGRES_PASSWORD" "${REQUIRED_SECRETS[PROD_POSTGRES_PASSWORD]}"
check_secret "PROD_RABBITMQ_PASSWORD" "${REQUIRED_SECRETS[PROD_RABBITMQ_PASSWORD]}"
check_secret "PROD_APP_SECRET" "${REQUIRED_SECRETS[PROD_APP_SECRET]}"
check_secret "PROD_JWT_PASSPHRASE" "${REQUIRED_SECRETS[PROD_JWT_PASSPHRASE]}"
check_secret "PROD_GOOGLE_CLIENT_ID" "${REQUIRED_SECRETS[PROD_GOOGLE_CLIENT_ID]}"
check_secret "PROD_GOOGLE_CLIENT_SECRET" "${REQUIRED_SECRETS[PROD_GOOGLE_CLIENT_SECRET]}"
echo ""

# Группа 2: VDS Server Access
echo -e "${BLUE}🌐 Группа 2: VDS Server Access (4 secrets)${NC}"
echo "=================================================================="
check_secret "VDS_HOST" "${REQUIRED_SECRETS[VDS_HOST]}"
check_secret "VDS_USER" "${REQUIRED_SECRETS[VDS_USER]}"

# VDS_SSH_KEY - особая проверка
echo -n "  "
if grep -q "BEGIN OPENSSH PRIVATE KEY" "$SECRETS_FILE" 2>/dev/null; then
    echo -e "${GREEN}✅${NC} VDS_SSH_KEY"
    echo -e "     ${BLUE}ℹ${NC}  ${REQUIRED_SECRETS[VDS_SSH_KEY]}"
    echo -e "     ${BLUE}📋${NC} Значение: [Private key найден (BEGIN OPENSSH...)]"
    ((FOUND_SECRETS++))
else
    echo -e "${RED}❌${NC} VDS_SSH_KEY ${YELLOW}(НЕ НАЙДЕН или неправильный формат)${NC}"
    echo -e "     ${BLUE}ℹ${NC}  ${REQUIRED_SECRETS[VDS_SSH_KEY]}"
    echo -e "     ${YELLOW}⚠${NC}  Убедитесь что файл содержит строки:"
    echo -e "        ${GREEN}-----BEGIN OPENSSH PRIVATE KEY-----${NC}"
    echo -e "        ${GREEN}...${NC}"
    echo -e "        ${GREEN}-----END OPENSSH PRIVATE KEY-----${NC}"
    ((MISSING_SECRETS++))
fi
echo ""

check_secret "VDS_PROJECT_PATH" "${REQUIRED_SECRETS[VDS_PROJECT_PATH]}"
echo ""

# Группа 3: Telegram Notifications
echo -e "${BLUE}📱 Группа 3: Telegram Notifications (2 secrets)${NC}"
echo "=================================================================="
check_secret "TELEGRAM_BOT_TOKEN" "${REQUIRED_SECRETS[TELEGRAM_BOT_TOKEN]}"
check_secret "TELEGRAM_CHAT_ID" "${REQUIRED_SECRETS[TELEGRAM_CHAT_ID]}"
echo ""

# Итоговый отчет
echo "=================================================================="
echo -e "${BLUE}📊 Итоговый Отчет${NC}"
echo "=================================================================="
echo -e "Всего необходимых secrets:  ${TOTAL_SECRETS}"
echo -e "Найдено:                    ${GREEN}${FOUND_SECRETS}${NC}"
echo -e "Отсутствует:                ${RED}${MISSING_SECRETS}${NC}"
echo ""

# Процент готовности
PERCENTAGE=$((FOUND_SECRETS * 100 / TOTAL_SECRETS))

if [ $PERCENTAGE -eq 100 ]; then
    echo -e "${GREEN}✅ ВСЕ СЕКРЕТЫ ГОТОВЫ! (100%)${NC}"
    echo ""
    echo -e "${GREEN}🎉 Вы можете переходить к настройке GitHub Secrets!${NC}"
    echo ""
    echo -e "Следующие шаги:"
    echo -e "1. GitHub → Settings → Secrets and variables → Actions"
    echo -e "2. Добавить все 12 secrets из файла: ${GREEN}$SECRETS_FILE${NC}"
    echo -e "3. См. детальную инструкцию: ${GREEN}docs/CI_CD_SETUP_GUIDE.md${NC}"
    echo ""
elif [ $PERCENTAGE -ge 80 ]; then
    echo -e "${YELLOW}⚠️  ПОЧТИ ГОТОВО ($PERCENTAGE%)${NC}"
    echo ""
    echo -e "${YELLOW}Отсутствующие secrets:${NC}"
    echo ""

    # Показать какие именно отсутствуют
    for secret in "${!REQUIRED_SECRETS[@]}"; do
        local found=$(grep -E "^${secret}=" "$SECRETS_FILE" 2>/dev/null | grep -v "^#" | cut -d= -f2- | tr -d '"' | tr -d "'")
        if [ -z "$found" ] || [ "$found" = "" ] || [ "$found" = "CHANGE_ME" ]; then
            echo -e "  ${RED}❌${NC} ${secret}"
        fi
    done
    echo ""
    echo -e "Заполните недостающие значения в: ${GREEN}$SECRETS_FILE${NC}"
    echo ""
else
    echo -e "${RED}❌ НЕ ГОТОВО ($PERCENTAGE%)${NC}"
    echo ""
    echo -e "${RED}Необходимо заполнить еще ${MISSING_SECRETS} secret(s)!${NC}"
    echo ""
    echo -e "Создайте файл секретов:"
    echo -e "  ${GREEN}nano $SECRETS_FILE${NC}"
    echo ""
    echo -e "Используйте шаблон из: ${GREEN}docs/CI_CD_SETUP_GUIDE.md${NC}"
    echo ""
fi

# Дополнительные проверки
echo "=================================================================="
echo -e "${BLUE}🔍 Дополнительные Проверки${NC}"
echo "=================================================================="

# Проверка формата APP_SECRET (должен быть 32 hex символа)
APP_SECRET=$(grep -E "^PROD_APP_SECRET=" "$SECRETS_FILE" 2>/dev/null | grep -v "^#" | cut -d= -f2- | tr -d '"' | tr -d "'")
if [ -n "$APP_SECRET" ]; then
    APP_SECRET_LENGTH=${#APP_SECRET}
    if [ $APP_SECRET_LENGTH -eq 32 ]; then
        echo -e "  ${GREEN}✅${NC} PROD_APP_SECRET: Длина корректна (32 символа)"
    else
        echo -e "  ${YELLOW}⚠${NC}  PROD_APP_SECRET: Неправильная длина (${APP_SECRET_LENGTH} вместо 32)"
        echo -e "     Сгенерируйте новый: ${GREEN}php -r \"echo bin2hex(random_bytes(16));\"${NC}"
    fi
fi

# Проверка формата JWT_PASSPHRASE (должен быть 64 hex символа)
JWT_PASS=$(grep -E "^PROD_JWT_PASSPHRASE=" "$SECRETS_FILE" 2>/dev/null | grep -v "^#" | cut -d= -f2- | tr -d '"' | tr -d "'")
if [ -n "$JWT_PASS" ]; then
    JWT_PASS_LENGTH=${#JWT_PASS}
    if [ $JWT_PASS_LENGTH -eq 64 ]; then
        echo -e "  ${GREEN}✅${NC} PROD_JWT_PASSPHRASE: Длина корректна (64 символа)"
    else
        echo -e "  ${YELLOW}⚠${NC}  PROD_JWT_PASSPHRASE: Неправильная длина (${JWT_PASS_LENGTH} вместо 64)"
        echo -e "     Сгенерируйте новый: ${GREEN}openssl rand -hex 32${NC}"
    fi
fi

# Проверка формата Telegram Bot Token
TG_TOKEN=$(grep -E "^TELEGRAM_BOT_TOKEN=" "$SECRETS_FILE" 2>/dev/null | grep -v "^#" | cut -d= -f2- | tr -d '"' | tr -d "'")
if [ -n "$TG_TOKEN" ]; then
    if [[ $TG_TOKEN =~ ^[0-9]+:[A-Za-z0-9_-]+$ ]]; then
        echo -e "  ${GREEN}✅${NC} TELEGRAM_BOT_TOKEN: Формат корректен"
    else
        echo -e "  ${YELLOW}⚠${NC}  TELEGRAM_BOT_TOKEN: Неправильный формат"
        echo -e "     Ожидается: ${GREEN}7000000000:AAHdqTcvCH1...${NC}"
    fi
fi

# Проверка формата Telegram Chat ID (должен быть числом)
TG_CHAT=$(grep -E "^TELEGRAM_CHAT_ID=" "$SECRETS_FILE" 2>/dev/null | grep -v "^#" | cut -d= -f2- | tr -d '"' | tr -d "'")
if [ -n "$TG_CHAT" ]; then
    if [[ $TG_CHAT =~ ^[0-9]+$ ]]; then
        echo -e "  ${GREEN}✅${NC} TELEGRAM_CHAT_ID: Формат корректен"
    else
        echo -e "  ${YELLOW}⚠${NC}  TELEGRAM_CHAT_ID: Должен быть числом (например: 987654321)"
    fi
fi

echo ""

# Exit code
if [ $PERCENTAGE -eq 100 ]; then
    exit 0
else
    exit 1
fi
