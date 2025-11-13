#!/bin/bash

# ================================================================
# Генератор безопасных секретов для production
# ================================================================
# Генерирует случайные пароли и секреты для .env.docker.prod
#
# Использование:
#   ./scripts/generate-secrets.sh
#
# ================================================================

set -e

# Цвета
GREEN='\033[0;32m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${CYAN}================================================${NC}"
echo -e "${CYAN}Генератор безопасных секретов${NC}"
echo -e "${CYAN}================================================${NC}"
echo ""

# Проверка наличия openssl
if ! command -v openssl &> /dev/null; then
    echo -e "${YELLOW}⚠ openssl не установлен. Используем /dev/urandom${NC}"
    GENERATOR="urandom"
else
    GENERATOR="openssl"
fi

# Функция генерации случайной строки
generate_secret() {
    local length=$1
    if [ "$GENERATOR" = "openssl" ]; then
        openssl rand -base64 $length | tr -d "=+/" | cut -c1-$length
    else
        cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w $length | head -n 1
    fi
}

# Функция генерации hex строки
generate_hex() {
    local length=$1
    if [ "$GENERATOR" = "openssl" ]; then
        openssl rand -hex $length
    else
        cat /dev/urandom | xxd -p | tr -d '\n' | cut -c1-$((length*2))
    fi
}

echo -e "${BLUE}Генерируем секреты...${NC}"
echo ""

# PostgreSQL
POSTGRES_PASSWORD=$(generate_secret 32)
echo -e "${GREEN}✓ POSTGRES_PASSWORD${NC}"
echo "  $POSTGRES_PASSWORD"
echo ""

# RabbitMQ
RABBITMQ_PASSWORD=$(generate_secret 32)
echo -e "${GREEN}✓ RABBITMQ_PASSWORD${NC}"
echo "  $RABBITMQ_PASSWORD"
echo ""

# Symfony APP_SECRET
APP_SECRET=$(generate_hex 16)
echo -e "${GREEN}✓ APP_SECRET${NC}"
echo "  $APP_SECRET"
echo ""

# JWT Passphrase
JWT_PASSPHRASE=$(generate_hex 32)
echo -e "${GREEN}✓ JWT_PASSPHRASE${NC}"
echo "  $JWT_PASSPHRASE"
echo ""

echo -e "${CYAN}================================================${NC}"
echo -e "${CYAN}Готово! Используйте эти значения в .env.docker.prod${NC}"
echo -e "${CYAN}================================================${NC}"
echo ""

echo -e "${YELLOW}Пример .env.docker.prod:${NC}"
echo ""
cat << EOF
# PostgreSQL Configuration
POSTGRES_DB=backend_prod
POSTGRES_USER=prod_user
POSTGRES_PASSWORD=$POSTGRES_PASSWORD
POSTGRES_PORT=5432

# RabbitMQ Configuration
RABBITMQ_USER=prod_user
RABBITMQ_PASSWORD=$RABBITMQ_PASSWORD
RABBITMQ_PORT=5672
RABBITMQ_MANAGEMENT_PORT=15672

# Nginx Configuration
NGINX_PORT=80
PHP_FPM_PORT=9000

# Symfony App Secrets
APP_SECRET=$APP_SECRET
JWT_PASSPHRASE=$JWT_PASSPHRASE

# Google OAuth (замените на свои!)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret

# Frontend Production
FRONTEND_PROD_PORT=3001
VITE_API_BASE_URL=http://your-domain.com
EOF

echo ""
echo -e "${BLUE}ℹ Сохраните эти секреты в безопасном месте!${NC}"
echo -e "${BLUE}ℹ Никогда не коммитьте .env.docker.prod в git!${NC}"
