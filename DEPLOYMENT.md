# Публикация на GitHub и развёртывание на хост

Пошаговая инструкция: выгрузка проекта в GitHub и развёртывание на сервере.

---

## Часть 1. Публикация на GitHub

### 1.1. Перед первым коммитом

Убедитесь, что в репозиторий не попадут секреты и лишние файлы:

- Файл **`.env`** — в `.gitignore` (никогда не коммитить).
- Файл **`CREDENTIALS.md`** — в `.gitignore` (учётные данные).
- В корне есть **`.env.example`** — шаблон переменных окружения для хоста.

Проверка:

```powershell
# Windows (PowerShell)
git status
# Убедитесь, что .env и CREDENTIALS.md не в списке новых файлов
```

### 1.2. Создание репозитория на GitHub

1. Зайдите на [github.com](https://github.com) и войдите в аккаунт.
2. **New repository** → укажите имя (например, `eg-web`).
3. Репозиторий лучше создать **без** README, .gitignore и лицензии (всё уже есть в проекте).
4. Скопируйте URL репозитория (HTTPS или SSH), например:  
   `https://github.com/ВАШ_ЛОГИН/eg-web.git`

### 1.3. Инициализация Git и первый push (если ещё не делали)

В корне проекта:

```powershell
# Если Git ещё не инициализирован
git init
git branch -M main

# Добавить удалённый репозиторий
git remote add origin https://github.com/ВАШ_ЛОГИН/eg-web.git

# Добавить все файлы (учитывается .gitignore)
git add .
git status   # ещё раз проверить, что нет .env и CREDENTIALS.md

# Первый коммит и отправка
git commit -m "Initial commit: EG Web Laravel + Vue"
git push -u origin main
```

Если репозиторий уже был инициализирован, достаточно:

```powershell
git remote add origin https://github.com/ВАШ_ЛОГИН/eg-web.git
git add .
git commit -m "Prepare for deployment"
git push -u origin main
```

### 1.4. Ветка по умолчанию

Скрипт `update.sh` на хосте использует ветку **`main`**. В настройках репозитория на GitHub задайте ветку по умолчанию **main** (если используете другую ветку — поправьте в `update.sh` строку с `git reset --hard origin/main`).

---

## Часть 2. Развёртывание на хост

На сервере приложение можно развернуть двумя способами: **клонированием с GitHub** (рекомендуется) или **установкой из архива** скриптом `install.sh`.

### 2.1. Вариант A: Клонирование с GitHub (рекомендуется)

Подходит для VPS/dedicated (Ubuntu 20.04+). Так проще обновлять: `git pull` + скрипт обновления.

#### На сервере (Ubuntu)

1. Установите Git (если ещё нет):
   ```bash
   sudo apt update
   sudo apt install -y git
   ```

2. Клонируйте репозиторий в каталог, из которого будете раздавать сайт (например, `/var/www/eg-web`):
   ```bash
   sudo mkdir -p /var/www
   cd /var/www
   sudo git clone https://github.com/ВАШ_ЛОГИН/eg-web.git eg-web
   cd eg-web
   ```

3. Для **приватного** репозитория настройте доступ:
   - Личный токен (GitHub → Settings → Developer settings → Personal access tokens) и:
     ```bash
     git clone https://ВАШ_ТОКЕН@github.com/ВАШ_ЛОГИН/eg-web.git eg-web
     ```
   - Или SSH-ключ на сервере и добавление его в GitHub (рекомендуется для продакшена).

4. Дальше — как в [INSTALLATION.md](INSTALLATION.md): зависимости, `.env`, миграции, сборка фронта, веб-сервер, PHP-FPM.

   Кратко:
   ```bash
   cd /var/www/eg-web
   cp .env.example .env
   php artisan key:generate
   # Отредактируйте .env: APP_URL, DB_*, и т.д.
   composer install --no-dev --optimize-autoloader
   npm ci
   npm run build
   php artisan migrate --force
   php artisan db:seed --class=LicenseSeeder
   php artisan db:seed --class=SuperAdminSeeder
   php artisan passport:install
   php artisan storage:link
   sudo chown -R www-data:www-data /var/www/eg-web
   sudo chmod -R 775 storage bootstrap/cache
   ```

5. Настройте Nginx/Apache на корень сайта = `public` (например, DocumentRoot `/var/www/eg-web/public`). Подробно — в [INSTALLATION.md](INSTALLATION.md).

#### Обновление с GitHub

На сервере в каталоге проекта:

```bash
cd /var/www/eg-web
sudo ./update.sh
```

Скрипт сделает бэкап, `git fetch` + `git reset --hard origin/main`, обновит зависимости, соберёт фронт, выполнит миграции и перезапустит сервисы.

### 2.2. Вариант B: Установка из архива (install.sh)

Если на хосте нет Git или вы раздаёте готовый архив:

1. Соберите архив из репозитория (например, через **Code → Download ZIP** на GitHub или `git archive`).
2. Загрузите архив на сервер и распакуйте в каталог установки.
3. Запустите проверку и установку по [INSTALLATION.md](INSTALLATION.md):
   ```bash
   chmod +x check-requirements.sh install.sh update.sh
   sudo ./check-requirements.sh
   sudo ./install.sh
   ```

После установки для обновлений можно перейти на вариант A (склонировать тот же репозиторий в эту директорию и настроить `git pull`/`update.sh`).

---

## Часть 3. Чеклист перед публикацией

- [ ] В репозитории нет файлов `.env`, `.env.production`, `CREDENTIALS.md`
- [ ] В репозитории есть `.env.example` и при необходимости `CREDENTIALS.example.md`
- [ ] В `.gitignore` указаны: `.env`, `CREDENTIALS.md`, `vendor`, `node_modules`, `public/build`, `public/hot`
- [ ] В README указана ветка по умолчанию (main) и ссылка на эту инструкцию
- [ ] На хосте после первого развёртывания создан супер-админ и сменены пароли по умолчанию

---

## Часть 4. Полезные команды

| Действие | Команда |
|----------|--------|
| Локально: отправить изменения на GitHub | `git add .` → `git commit -m "..."` → `git push` |
| На хосте: обновить приложение из GitHub | `cd /var/www/eg-web` → `sudo ./update.sh` |
| На хосте: только подтянуть код (без зависимостей) | `git fetch origin` → `git reset --hard origin/main` |
| Сгенерировать ключ приложения | `php artisan key:generate` |
| Установить Passport (OAuth) | `php artisan passport:install` |
| Создать симлинк для загрузок | `php artisan storage:link` |

---

## Ссылки

- [README.md](README.md) — описание проекта и быстрый старт
- [INSTALLATION.md](INSTALLATION.md) — установка на Ubuntu (требования, скрипты, ручная настройка)
- [INSTALLATION_CLIENT.md](INSTALLATION_CLIENT.md) — инструкция для клиентов
- [USER_ROLES.md](USER_ROLES.md) — роли и создание супер-администратора
