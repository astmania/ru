#!/bin/bash

###############################################################################
# Скрипт создания минимального установочного пакета
# Только установочные скрипты и документация (без кода проекта)
###############################################################################

set -e

PACKAGE_NAME="eg-web-installer-minimal"
VERSION="1.0.0"
BUILD_DIR="./build"
PACKAGE_DIR="$BUILD_DIR/$PACKAGE_NAME-$VERSION"

# Цвета
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}Создание минимального установочного пакета...${NC}"

# Очистка старой сборки
rm -rf "$BUILD_DIR"
mkdir -p "$PACKAGE_DIR"

# Копирование только установочных файлов
echo "Копирование установочных файлов..."
cp install.sh "$PACKAGE_DIR/"
cp check-requirements.sh "$PACKAGE_DIR/"
cp update.sh "$PACKAGE_DIR/"
cp INSTALLATION.md "$PACKAGE_DIR/"
cp README_INSTALL.md "$PACKAGE_DIR/"

# Создание инструкции по получению кода проекта
cat > "$PACKAGE_DIR/GET_PROJECT_CODE.md" <<EOF
# Получение кода проекта

Этот пакет содержит только установочные скрипты.

Для установки вам также нужен код проекта.

## Вариант 1: Из Git репозитория

\`\`\`bash
git clone https://your-repo-url/eg-web.git
cd eg-web
cp ../install.sh .
cp ../check-requirements.sh .
cp ../update.sh .
chmod +x *.sh
sudo ./install.sh
\`\`\`

## Вариант 2: Из архива проекта

1. Получите архив с кодом проекта
2. Распакуйте его
3. Скопируйте установочные скрипты в директорию проекта
4. Запустите установку

## Вариант 3: Через установщик

Установщик может автоматически скачать код проекта, если указан URL репозитория.
EOF

# Установка прав на скрипты
chmod +x "$PACKAGE_DIR/install.sh"
chmod +x "$PACKAGE_DIR/check-requirements.sh"
chmod +x "$PACKAGE_DIR/update.sh"

# Создание README
cat > "$PACKAGE_DIR/README.txt" <<EOF
==========================================
EG Web - Минимальный установочный пакет
Версия: $VERSION
==========================================

Этот пакет содержит только установочные скрипты.

Для установки вам также нужен код проекта.

См. файл GET_PROJECT_CODE.md для инструкций.

==========================================
EOF

# Создание архива
echo "Создание архива..."
cd "$BUILD_DIR"
zip -r "$PACKAGE_NAME-$VERSION.zip" "$PACKAGE_NAME-$VERSION" -q
tar -czf "$PACKAGE_NAME-$VERSION.tar.gz" "$PACKAGE_NAME-$VERSION"

cd ..

echo ""
echo -e "${GREEN}✓ Минимальный пакет создан!${NC}"
echo ""
echo "Файлы:"
echo "  - $BUILD_DIR/$PACKAGE_NAME-$VERSION.zip"
echo "  - $BUILD_DIR/$PACKAGE_NAME-$VERSION.tar.gz"
echo ""
