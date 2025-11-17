#!/bin/bash

# ================================================================
# SSH Key Validator for GitHub Actions
# ================================================================
# Проверяет что SSH ключ правильного формата для GitHub Secrets
# ================================================================

set -e

# Цвета
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "🔐 SSH Key Format Validator"
echo "================================"
echo ""

if [ -z "$1" ]; then
    echo -e "${RED}❌ Ошибка: Путь к ключу не указан${NC}"
    echo ""
    echo "Использование:"
    echo "  $0 /path/to/private_key"
    echo ""
    echo "Пример:"
    echo "  $0 ~/.ssh/github_actions_taskflow"
    exit 1
fi

KEY_FILE="$1"

if [ ! -f "$KEY_FILE" ]; then
    echo -e "${RED}❌ Файл не найден: $KEY_FILE${NC}"
    exit 1
fi

echo "📂 Проверка файла: $KEY_FILE"
echo ""

# Проверка 1: Файл не пустой
echo "1. Проверка: Файл не пустой..."
if [ ! -s "$KEY_FILE" ]; then
    echo -e "${RED}❌ Файл пустой!${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Файл содержит данные${NC}"

# Проверка 2: Начинается с BEGIN OPENSSH PRIVATE KEY
echo "2. Проверка: Формат OpenSSH..."
if ! head -n 1 "$KEY_FILE" | grep -q "BEGIN OPENSSH PRIVATE KEY"; then
    echo -e "${RED}❌ Неправильный формат! Должен начинаться с '-----BEGIN OPENSSH PRIVATE KEY-----'${NC}"
    echo ""
    echo "Первая строка файла:"
    head -n 1 "$KEY_FILE"
    exit 1
fi
echo -e "${GREEN}✅ Корректный OpenSSH формат${NC}"

# Проверка 3: Заканчивается на END OPENSSH PRIVATE KEY
echo "3. Проверка: Корректное окончание..."
if ! tail -n 1 "$KEY_FILE" | grep -q "END OPENSSH PRIVATE KEY"; then
    echo -e "${RED}❌ Неправильный формат! Должен заканчиваться на '-----END OPENSSH PRIVATE KEY-----'${NC}"
    echo ""
    echo "Последняя строка файла:"
    tail -n 1 "$KEY_FILE"
    exit 1
fi
echo -e "${GREEN}✅ Корректное окончание${NC}"

# Проверка 4: Права доступа
echo "4. Проверка: Права доступа..."
PERMS=$(stat -f "%Lp" "$KEY_FILE" 2>/dev/null || stat -c "%a" "$KEY_FILE" 2>/dev/null)
if [ "$PERMS" != "600" ]; then
    echo -e "${YELLOW}⚠️  Права доступа: $PERMS (рекомендуется 600)${NC}"
    echo "   Исправить: chmod 600 $KEY_FILE"
else
    echo -e "${GREEN}✅ Права доступа корректные (600)${NC}"
fi

# Проверка 5: Нет лишних пробелов/переносов
echo "5. Проверка: Нет trailing пробелов..."
if grep -q '[[:space:]]$' "$KEY_FILE"; then
    echo -e "${YELLOW}⚠️  Обнаружены trailing пробелы в конце строк${NC}"
    echo "   Это может вызвать проблемы в GitHub Actions"
else
    echo -e "${GREEN}✅ Нет trailing пробелов${NC}"
fi

# Проверка 6: Проверка типа ключа
echo "6. Проверка: Тип ключа..."
if ssh-keygen -l -f "$KEY_FILE" &>/dev/null; then
    KEY_INFO=$(ssh-keygen -l -f "$KEY_FILE")
    echo -e "${GREEN}✅ Ключ валидный${NC}"
    echo "   Информация: $KEY_INFO"
else
    echo -e "${RED}❌ Ключ поврежден или неправильного формата!${NC}"
    exit 1
fi

# Проверка 7: Показать содержимое для копирования
echo ""
echo "7. Готовность для GitHub Secrets..."
echo ""
echo -e "${GREEN}✅ Ключ готов для использования в GitHub Secrets!${NC}"
echo ""
echo "================================================"
echo "📋 Инструкция по добавлению в GitHub Secrets:"
echo "================================================"
echo ""
echo "1. Скопировать ВЕСЬ ключ (включая BEGIN/END строки):"
echo ""
echo -e "${YELLOW}   cat $KEY_FILE${NC}"
echo ""
echo "2. GitHub → Settings → Secrets → Actions → New secret"
echo "3. Name: VDS_SSH_KEY"
echo "4. Value: [Вставить ПОЛНОЕ содержимое ключа]"
echo ""
echo "⚠️  ВАЖНО:"
echo "   - Копируйте ВЕСЬ ключ от BEGIN до END включительно"
echo "   - НЕ добавляйте лишние пробелы или переносы"
echo "   - Проверьте что скопировали ВСЕ строки (обычно 8-10 строк)"
echo ""

# Опционально: Показать первые/последние строки для проверки
echo "================================================"
echo "Первые 2 строки ключа:"
echo "================================================"
head -n 2 "$KEY_FILE"
echo ""
echo "================================================"
echo "Последние 2 строки ключа:"
echo "================================================"
tail -n 2 "$KEY_FILE"
echo ""

# Тест подключения (если переданы параметры)
if [ -n "$2" ] && [ -n "$3" ]; then
    echo "================================================"
    echo "8. Тест SSH подключения..."
    echo "================================================"
    HOST="$2"
    USER="$3"

    echo "Тестирование: ssh -i $KEY_FILE $USER@$HOST \"echo 'OK'\""
    if ssh -i "$KEY_FILE" -o StrictHostKeyChecking=no "$USER@$HOST" "echo 'SSH connection successful!'" 2>/dev/null; then
        echo -e "${GREEN}✅ SSH подключение работает!${NC}"
    else
        echo -e "${RED}❌ SSH подключение не удалось!${NC}"
        echo "   Проверьте:"
        echo "   1. Публичный ключ добавлен на VDS (cat ~/.ssh/authorized_keys)"
        echo "   2. VDS доступен (ping $HOST)"
        echo "   3. SSH сервис запущен на VDS (systemctl status ssh)"
    fi
fi

echo ""
echo -e "${GREEN}🎉 Валидация завершена успешно!${NC}"
