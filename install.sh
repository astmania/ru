#!/bin/bash

###############################################################################
# Автоматическая установка проекта на Ubuntu Linux
# Версия: 1.0
###############################################################################

set -e  # Остановка при ошибке

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Переменные
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALL_DIR="/var/www/eg-web"
DOMAIN=""
DB_NAME="eg_web"
DB_USER="eg_web_user"
DB_PASS=""
ADMIN_EMAIL=""
PHP_VERSION="8.3"
WEB_SERVER="nginx"  # nginx или apache

# Логирование
LOG_FILE="/var/log/eg-web-install.log"
mkdir -p /var/log

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" | tee -a "$LOG_FILE"
    exit 1
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1" | tee -a "$LOG_FILE"
}

info() {
    echo -e "${BLUE}[INFO]${NC} $1" | tee -a "$LOG_FILE"
}

# Проверка прав root
check_root() {
    if [ "$EUID" -ne 0 ]; then 
        error "Пожалуйста, запустите скрипт с правами root (sudo)"
    fi
}

# Проверка системных требований
check_requirements() {
    log "Проверка системных требований..."
    
    # Проверка ОС
    if [ ! -f /etc/os-release ]; then
        error "Не удалось определить версию ОС"
    fi
    
    . /etc/os-release
    if [ "$ID" != "ubuntu" ]; then
        error "Этот скрипт предназначен для Ubuntu Linux"
    fi
    
    log "ОС: $PRETTY_NAME"
    
    # Проверка архитектуры
    ARCH=$(uname -m)
    log "Архитектура: $ARCH"
    
    # Проверка свободного места
    AVAILABLE_SPACE=$(df -BG / | awk 'NR==2 {print $4}' | sed 's/G//')
    if [ "$AVAILABLE_SPACE" -lt 2 ]; then
        error "Недостаточно свободного места. Требуется минимум 2GB"
    fi
    
    log "Свободное место: ${AVAILABLE_SPACE}GB"
}

# Обновление системы
update_system() {
    log "Обновление списка пакетов..."
    apt-get update -qq
    
    log "Установка базовых утилит..."
    apt-get install -y -qq \
        curl \
        wget \
        git \
        unzip \
        software-properties-common \
        apt-transport-https \
        ca-certificates \
        gnupg \
        lsb-release
}

# Установка PHP
install_php() {
    log "Установка PHP $PHP_VERSION..."
    
    # Добавление репозитория PHP
    add-apt-repository -y ppa:ondrej/php
    apt-get update -qq
    
    # Установка PHP и расширений
    apt-get install -y -qq \
        php${PHP_VERSION} \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-common \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-opcache
    
    # Настройка PHP
    log "Настройка PHP..."
    
    PHP_INI="/etc/php/${PHP_VERSION}/fpm/php.ini"
    PHP_INI_CLI="/etc/php/${PHP_VERSION}/cli/php.ini"
    
    # Увеличение лимитов
    sed -i 's/memory_limit = .*/memory_limit = 512M/' "$PHP_INI"
    sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' "$PHP_INI"
    sed -i 's/post_max_size = .*/post_max_size = 64M/' "$PHP_INI"
    sed -i 's/max_execution_time = .*/max_execution_time = 300/' "$PHP_INI"
    
    # Те же настройки для CLI
    sed -i 's/memory_limit = .*/memory_limit = 512M/' "$PHP_INI_CLI"
    
    # Настройка opcache
    PHP_OPCACHE="/etc/php/${PHP_VERSION}/fpm/conf.d/10-opcache.ini"
    cat >> "$PHP_OPCACHE" <<EOF
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.save_comments=1
EOF
    
    systemctl restart php${PHP_VERSION}-fpm
    log "PHP $PHP_VERSION установлен и настроен"
}

# Установка Composer
install_composer() {
    log "Установка Composer..."
    
    if [ -f /usr/local/bin/composer ]; then
        log "Composer уже установлен"
        return
    fi
    
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
    
    log "Composer установлен"
}

# Установка Node.js и npm
install_nodejs() {
    log "Установка Node.js..."
    
    if command -v node &> /dev/null; then
        log "Node.js уже установлен"
        return
    fi
    
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y -qq nodejs
    
    log "Node.js установлен: $(node --version)"
    log "npm установлен: $(npm --version)"
}

# Установка MySQL
install_mysql() {
    log "Установка MySQL..."
    
    if command -v mysql &> /dev/null; then
        log "MySQL уже установлен"
        return
    fi
    
    # Генерация случайного пароля для root MySQL
    MYSQL_ROOT_PASS=$(openssl rand -base64 32)
    
    debconf-set-selections <<< "mysql-server mysql-server/root_password password $MYSQL_ROOT_PASS"
    debconf-set-selections <<< "mysql-server mysql-server/root_password_again password $MYSQL_ROOT_PASS"
    
    apt-get install -y -qq mysql-server
    
    # Сохранение пароля root
    echo "MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASS" > /root/.mysql_root_password
    chmod 600 /root/.mysql_root_password
    
    # Настройка MySQL
    mysql -u root -p"$MYSQL_ROOT_PASS" <<EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EOF
    
    log "MySQL установлен и настроен"
    log "База данных создана: $DB_NAME"
    log "Пользователь создан: $DB_USER"
}

# Установка Nginx
install_nginx() {
    log "Установка Nginx..."
    
    apt-get install -y -qq nginx
    
    # Создание конфигурации
    NGINX_CONF="/etc/nginx/sites-available/eg-web"
    
    cat > "$NGINX_CONF" <<EOF
server {
    listen 80;
    server_name $DOMAIN;
    root $INSTALL_DIR/public;
    index index.php index.html;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss;
}
EOF
    
    # Активация сайта
    ln -sf "$NGINX_CONF" /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    # Проверка конфигурации
    nginx -t
    
    systemctl restart nginx
    systemctl enable nginx
    
    log "Nginx установлен и настроен"
}

# Установка Apache (альтернатива)
install_apache() {
    log "Установка Apache..."
    
    apt-get install -y -qq apache2 libapache2-mod-php${PHP_VERSION}
    
    # Включение модулей
    a2enmod rewrite
    a2enmod php${PHP_VERSION}
    
    # Создание конфигурации
    APACHE_CONF="/etc/apache2/sites-available/eg-web.conf"
    
    cat > "$APACHE_CONF" <<EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    DocumentRoot $INSTALL_DIR/public

    <Directory $INSTALL_DIR/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/eg-web-error.log
    CustomLog \${APACHE_LOG_DIR}/eg-web-access.log combined
</VirtualHost>
EOF
    
    # Активация сайта
    a2ensite eg-web.conf
    a2dissite 000-default.conf
    
    systemctl restart apache2
    systemctl enable apache2
    
    log "Apache установлен и настроен"
}

# Копирование файлов проекта
copy_project_files() {
    log "Копирование файлов проекта..."
    
    mkdir -p "$INSTALL_DIR"
    
    # Копирование всех файлов кроме .git
    rsync -av --exclude='.git' --exclude='node_modules' --exclude='vendor' \
        "$SCRIPT_DIR/" "$INSTALL_DIR/"
    
    # Установка прав доступа
    chown -R www-data:www-data "$INSTALL_DIR"
    chmod -R 755 "$INSTALL_DIR"
    chmod -R 775 "$INSTALL_DIR/storage"
    chmod -R 775 "$INSTALL_DIR/bootstrap/cache"
    
    log "Файлы проекта скопированы в $INSTALL_DIR"
}

# Установка зависимостей
install_dependencies() {
    log "Установка зависимостей Composer..."
    
    cd "$INSTALL_DIR"
    sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction
    
    log "Установка зависимостей npm..."
    sudo -u www-data npm ci
    
    log "Сборка фронтенда..."
    sudo -u www-data npm run build
    
    log "Зависимости установлены"
}

# Настройка окружения
setup_environment() {
    log "Настройка окружения..."
    
    cd "$INSTALL_DIR"
    
    # Копирование .env.example в .env
    if [ ! -f .env ]; then
        cp .env.example .env
        
        # Генерация ключа приложения
        php artisan key:generate --force
        
        # Настройка базы данных
        sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
        sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
        sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
        
        # Настройка APP_URL
        if [ -n "$DOMAIN" ]; then
            sed -i "s|APP_URL=.*|APP_URL=http://$DOMAIN|" .env
        fi
        
        # Настройка APP_ENV
        sed -i "s/APP_ENV=.*/APP_ENV=production/" .env
        sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" .env
    fi
    
    chown www-data:www-data .env
    chmod 600 .env
    
    log "Окружение настроено"
}

# Выполнение миграций
run_migrations() {
    log "Выполнение миграций базы данных..."
    
    cd "$INSTALL_DIR"
    
    # Выполнение всех миграций
    php artisan migrate --force
    
    # Запуск сидера для лицензий (опционально, только тестовые лицензии)
    # php artisan db:seed --class=LicenseSeeder
    
    log "Миграции выполнены"
}

# Настройка cron
setup_cron() {
    log "Настройка cron задач..."
    
    CRON_FILE="/etc/cron.d/eg-web"
    
    cat > "$CRON_FILE" <<EOF
* * * * * www-data cd $INSTALL_DIR && php artisan schedule:run >> /dev/null 2>&1
EOF
    
    chmod 0644 "$CRON_FILE"
    
    log "Cron задачи настроены"
}

# Создание systemd service
create_systemd_service() {
    log "Создание systemd service..."
    
    SERVICE_FILE="/etc/systemd/system/eg-web.service"
    
    cat > "$SERVICE_FILE" <<EOF
[Unit]
Description=EG Web Application
After=network.target mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=$INSTALL_DIR
ExecStart=/usr/bin/php $INSTALL_DIR/artisan serve --host=127.0.0.1 --port=8000
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF
    
    systemctl daemon-reload
    # Не включаем автозапуск, так как используем Nginx/Apache
    # systemctl enable eg-web
    
    log "Systemd service создан"
}

# Настройка SSL (Let's Encrypt)
setup_ssl() {
    if [ -z "$DOMAIN" ] || [ "$DOMAIN" == "localhost" ]; then
        log "Пропуск настройки SSL (localhost)"
        return
    fi
    
    log "Настройка SSL сертификата..."
    
    apt-get install -y -qq certbot python3-certbot-nginx
    
    certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos --email "$ADMIN_EMAIL" --redirect
    
    # Настройка автообновления
    systemctl enable certbot.timer
    
    log "SSL сертификат настроен"
}

# Финальная настройка
finalize_installation() {
    log "Финальная настройка..."
    
    cd "$INSTALL_DIR"
    
    # Очистка кеша
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Оптимизация
    php artisan optimize
    
    log "Установка завершена!"
}

# Вывод информации
show_summary() {
    echo ""
    echo "=========================================="
    echo -e "${GREEN}Установка завершена успешно!${NC}"
    echo "=========================================="
    echo ""
    echo "Информация об установке:"
    echo "  Директория: $INSTALL_DIR"
    echo "  Домен: $DOMAIN"
    echo "  База данных: $DB_NAME"
    echo "  Пользователь БД: $DB_USER"
    echo ""
    echo "Доступ к приложению:"
    if [ -n "$DOMAIN" ] && [ "$DOMAIN" != "localhost" ]; then
        echo "  http://$DOMAIN"
    else
        echo "  http://$(hostname -I | awk '{print $1}')"
    fi
    echo ""
    echo "Логи установки: $LOG_FILE"
    echo ""
    echo "Пароль root MySQL сохранен в: /root/.mysql_root_password"
    echo ""
    echo "=========================================="
    echo "  Следующие шаги:"
    echo "=========================================="
    echo ""
    echo "1. Активируйте лицензию:"
    echo "   curl -X POST http://$DOMAIN/api/license/activate \\"
    echo "     -H \"Content-Type: application/json\" \\"
    echo "     -d '{\"license_key\": \"ВАШ-КЛЮЧ-ЛИЦЕНЗИИ\"}'"
    echo ""
    echo "2. Откройте сайт в браузере:"
    if [ -n "$DOMAIN" ] && [ "$DOMAIN" != "localhost" ]; then
        echo "   http://$DOMAIN"
    else
        echo "   http://$(hostname -I | awk '{print $1}')"
    fi
    echo ""
    echo "3. Создайте первого пользователя через регистрацию"
    echo ""
    echo "Подробная инструкция: см. INSTALLATION_CLIENT.md"
    echo ""
}

# Главная функция
main() {
    clear
    echo "=========================================="
    echo "  Автоматическая установка EG Web"
    echo "=========================================="
    echo ""
    
    check_root
    check_requirements
    
    # Запрос параметров
    read -p "Введите домен (или нажмите Enter для localhost): " DOMAIN
    DOMAIN=${DOMAIN:-localhost}
    
    read -p "Введите пароль для базы данных: " DB_PASS
    if [ -z "$DB_PASS" ]; then
        DB_PASS=$(openssl rand -base64 32)
        log "Сгенерирован случайный пароль БД: $DB_PASS"
    fi
    
    read -p "Введите email администратора (для SSL): " ADMIN_EMAIL
    ADMIN_EMAIL=${ADMIN_EMAIL:-admin@$DOMAIN}
    
    read -p "Выберите веб-сервер (nginx/apache) [nginx]: " WEB_SERVER_INPUT
    WEB_SERVER=${WEB_SERVER_INPUT:-nginx}
    
    # Установка
    update_system
    install_php
    install_composer
    install_nodejs
    install_mysql
    copy_project_files
    install_dependencies
    setup_environment
    run_migrations
    
    if [ "$WEB_SERVER" == "nginx" ]; then
        install_nginx
    else
        install_apache
    fi
    
    setup_cron
    create_systemd_service
    
    if [ "$DOMAIN" != "localhost" ]; then
        setup_ssl
    fi
    
    finalize_installation
    show_summary
}

# Запуск
main "$@"
