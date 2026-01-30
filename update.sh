#!/bin/bash

###############################################################################
# Скрипт обновления проекта
###############################################################################

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

INSTALL_DIR="/var/www/eg-web"
BACKUP_DIR="/var/backups/eg-web"
DATE=$(date +%Y%m%d_%H%M%S)

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

# Проверка прав root
if [ "$EUID" -ne 0 ]; then 
    error "Пожалуйста, запустите скрипт с правами root (sudo)"
fi

# Проверка существования установки
if [ ! -d "$INSTALL_DIR" ]; then
    error "Проект не найден в $INSTALL_DIR"
fi

log "Начало обновления проекта..."

# Создание резервной копии
log "Создание резервной копии..."
mkdir -p "$BACKUP_DIR"

# Резервная копия базы данных
if [ -f "$INSTALL_DIR/.env" ]; then
    source "$INSTALL_DIR/.env"
    mysqldump -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_DIR/db_$DATE.sql"
    log "Резервная копия БД создана: $BACKUP_DIR/db_$DATE.sql"
fi

# Резервная копия файлов
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" -C "$INSTALL_DIR" \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='.git' \
    .
log "Резервная копия файлов создана: $BACKUP_DIR/files_$DATE.tar.gz"

# Переход в директорию проекта
cd "$INSTALL_DIR"

# Сохранение .env
if [ -f .env ]; then
    cp .env .env.backup
    log ".env файл сохранен"
fi

# Обновление из Git (если используется)
if [ -d .git ]; then
    log "Обновление из Git..."
    git fetch origin
    git reset --hard origin/main  # или master, в зависимости от ветки
    log "Код обновлен из Git"
fi

# Обновление зависимостей Composer
log "Обновление зависимостей Composer..."
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction

# Обновление зависимостей npm
log "Обновление зависимостей npm..."
sudo -u www-data npm ci

# Сборка фронтенда
log "Сборка фронтенда..."
sudo -u www-data npm run build

# Выполнение миграций
log "Выполнение миграций..."
php artisan migrate --force

# Очистка кеша
log "Очистка кеша..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Пересоздание кеша
log "Пересоздание кеша..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Оптимизация
php artisan optimize

# Установка прав доступа
log "Установка прав доступа..."
chown -R www-data:www-data "$INSTALL_DIR"
chmod -R 755 "$INSTALL_DIR"
chmod -R 775 "$INSTALL_DIR/storage"
chmod -R 775 "$INSTALL_DIR/bootstrap/cache"

# Перезапуск PHP-FPM
log "Перезапуск PHP-FPM..."
systemctl restart php8.3-fpm

# Перезапуск веб-сервера
if systemctl is-active --quiet nginx; then
    log "Перезапуск Nginx..."
    systemctl reload nginx
elif systemctl is-active --quiet apache2; then
    log "Перезапуск Apache..."
    systemctl reload apache2
fi

log "Обновление завершено успешно!"
log "Резервные копии сохранены в: $BACKUP_DIR"

# Очистка старых резервных копий (старше 30 дней)
find "$BACKUP_DIR" -type f -mtime +30 -delete
log "Старые резервные копии удалены"
