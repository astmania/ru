#!/bin/bash

###############################################################################
# Скрипт регистрации развернутого проекта в системе управления
# Запускается на развернутом проекте после установки
###############################################################################

set -e

# Цвета
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Параметры
MANAGEMENT_URL="${MANAGEMENT_URL:-http://localhost:8000}"
PROJECT_NAME="${PROJECT_NAME:-$(hostname)}"
PROJECT_URL="${PROJECT_URL:-http://$(hostname)}"
LICENSE_KEY="${LICENSE_KEY:-}"

# Генерация API ключей
API_KEY="dp_$(openssl rand -hex 16)"
API_SECRET="$(openssl rand -hex 32)"

echo -e "${GREEN}Регистрация проекта в системе управления...${NC}"
echo ""

# Проверка параметров
if [ -z "$LICENSE_KEY" ]; then
    echo -e "${YELLOW}Предупреждение: LICENSE_KEY не указан${NC}"
    read -p "Введите ключ лицензии (или нажмите Enter для пропуска): " LICENSE_KEY
fi

# Регистрация через API
echo "Отправка запроса на регистрацию..."
echo "  URL управления: $MANAGEMENT_URL"
echo "  Название проекта: $PROJECT_NAME"
echo "  URL проекта: $PROJECT_URL"
echo ""

RESPONSE=$(curl -s -X POST "$MANAGEMENT_URL/api/projects/register" \
    -H "Content-Type: application/json" \
    -d "{
        \"name\": \"$PROJECT_NAME\",
        \"url\": \"$PROJECT_URL\",
        \"license_key\": \"$LICENSE_KEY\",
        \"api_key\": \"$API_KEY\",
        \"api_secret\": \"$API_SECRET\"
    }")

# Проверка ответа
if echo "$RESPONSE" | grep -q '"success":true'; then
    echo -e "${GREEN}✓ Проект успешно зарегистрирован!${NC}"
    echo ""
    echo "API ключи сохранены в .env:"
    echo "  MANAGEMENT_API_KEY=$API_KEY"
    echo "  MANAGEMENT_API_SECRET=$API_SECRET"
    echo ""
    
    # Сохранение в .env
    ENV_FILE=".env"
    if [ -f "$ENV_FILE" ]; then
        if ! grep -q "MANAGEMENT_API_KEY" "$ENV_FILE"; then
            echo "" >> "$ENV_FILE"
            echo "# Management System API Keys" >> "$ENV_FILE"
            echo "MANAGEMENT_API_KEY=$API_KEY" >> "$ENV_FILE"
            echo "MANAGEMENT_API_SECRET=$API_SECRET" >> "$ENV_FILE"
            echo "MANAGEMENT_URL=$MANAGEMENT_URL" >> "$ENV_FILE"
        fi
        echo -e "${GREEN}✓ Ключи сохранены в .env${NC}"
    fi
else
    echo -e "${RED}✗ Ошибка регистрации проекта${NC}"
    echo "Ответ сервера: $RESPONSE"
    exit 1
fi

echo ""
echo -e "${GREEN}Регистрация завершена!${NC}"
