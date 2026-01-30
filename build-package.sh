#!/bin/bash

###############################################################################
# Скрипт создания установочного пакета
# Создает архив со всеми необходимыми файлами для установки
###############################################################################

set -e

PACKAGE_NAME="eg-web-installer"
VERSION="1.0.0"
BUILD_DIR="./build"
PACKAGE_DIR="$BUILD_DIR/$PACKAGE_NAME-$VERSION"

# Цвета
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}Создание установочного пакета...${NC}"

# Очистка старой сборки
rm -rf "$BUILD_DIR"
mkdir -p "$PACKAGE_DIR"

# Копирование файлов проекта
echo "Копирование файлов проекта..."
rsync -av \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='.env' \
    --exclude='build' \
    --exclude='*.log' \
    ./ "$PACKAGE_DIR/"

# Копирование установочных скриптов
echo "Копирование установочных скриптов..."
cp install.sh "$PACKAGE_DIR/"
cp check-requirements.sh "$PACKAGE_DIR/"
cp update.sh "$PACKAGE_DIR/"
cp register-project.sh "$PACKAGE_DIR/"
cp INSTALLATION.md "$PACKAGE_DIR/"
cp INSTALLATION_CLIENT.md "$PACKAGE_DIR/"
cp README_CLIENT.md "$PACKAGE_DIR/"
cp README.md "$PACKAGE_DIR/"

# Создание директорий для установки
mkdir -p "$PACKAGE_DIR/storage/logs"
mkdir -p "$PACKAGE_DIR/storage/framework/cache"
mkdir -p "$PACKAGE_DIR/storage/framework/sessions"
mkdir -p "$PACKAGE_DIR/storage/framework/views"
mkdir -p "$PACKAGE_DIR/bootstrap/cache"

# Установка прав на скрипты
chmod +x "$PACKAGE_DIR/install.sh"
chmod +x "$PACKAGE_DIR/check-requirements.sh"
chmod +x "$PACKAGE_DIR/update.sh"
chmod +x "$PACKAGE_DIR/register-project.sh"

# Создание README для пакета
cat > "$PACKAGE_DIR/README.txt" <<EOF
==========================================
EG Web - Установочный пакет
Версия: $VERSION
==========================================

ИНСТРУКЦИЯ ПО УСТАНОВКЕ:

1. Распакуйте архив:
   unzip $PACKAGE_NAME-$VERSION.zip
   cd $PACKAGE_NAME-$VERSION

2. Сделайте скрипты исполняемыми:
   chmod +x install.sh check-requirements.sh update.sh register-project.sh

3. Проверьте требования (опционально):
   sudo ./check-requirements.sh

4. Запустите установку:
   sudo ./install.sh

5. Активируйте лицензию:
   curl -X POST http://ваш-домен/api/license/activate \\
     -H "Content-Type: application/json" \\
     -d '{"license_key": "ВАШ-КЛЮЧ-ЛИЦЕНЗИИ"}'

Подробная документация:
- INSTALLATION_CLIENT.md - для клиентов
- INSTALLATION.md - техническая документация
- README_CLIENT.md - краткая инструкция

==========================================
EOF

# Создание архива
echo "Создание архива..."
cd "$BUILD_DIR"
zip -r "$PACKAGE_NAME-$VERSION.zip" "$PACKAGE_NAME-$VERSION" -q

# Создание архива tar.gz (альтернатива)
tar -czf "$PACKAGE_NAME-$VERSION.tar.gz" "$PACKAGE_NAME-$VERSION"

cd ..

# Вывод информации
echo ""
echo -e "${GREEN}✓ Пакет создан успешно!${NC}"
echo ""
echo "Файлы:"
echo "  - $BUILD_DIR/$PACKAGE_NAME-$VERSION.zip"
echo "  - $BUILD_DIR/$PACKAGE_NAME-$VERSION.tar.gz"
echo ""
echo "Размер пакета:"
du -h "$BUILD_DIR/$PACKAGE_NAME-$VERSION.zip" | cut -f1
echo ""
echo "Готово к распространению!"
