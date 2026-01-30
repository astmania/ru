# Система лицензирования для Embedded версии

## Описание

Система лицензирования позволяет управлять функционалом продаваемого проекта через лицензии. Поддерживает различные типы лицензий, проверку функций, лимиты пользователей и запросов.

## Установка

1. Выполните миграции:
```bash
php artisan migrate
```

2. Добавьте в `.env`:
```env
LICENSE_KEY=your_license_key_here
LICENSE_CHECK_MODE=local
```

3. Обновите автозагрузку Composer:
```bash
composer dump-autoload
```

## Основные возможности

### Типы лицензий

- **trial** - Пробная версия (30 дней, 1 пользователь)
- **basic** - Базовая (10 пользователей)
- **premium** - Премиум (100 пользователей, расширенные функции)
- **enterprise** - Корпоративная (без ограничений, все функции)

### Доступные функции

- `basic_auth` - Базовая авторизация
- `advanced_reports` - Расширенная отчетность
- `api_access` - Доступ к API
- `custom_branding` - Кастомный брендинг
- `user_management` - Управление пользователями
- `export_data` - Экспорт данных
- `import_data` - Импорт данных
- `backup_restore` - Резервное копирование
- `multi_tenant` - Мультитенантность
- `advanced_analytics` - Расширенная аналитика

## Использование

### Создание лицензии

```php
use App\Services\LicenseService;

$licenseService = app(LicenseService::class);

$license = $licenseService->createLicense([
    'type' => 'premium',
    'domain' => 'example.com',
    'expires_at' => now()->addYear(),
    'max_users' => 100,
    'customer_email' => 'customer@example.com',
    'customer_name' => 'Иван Иванов',
    'feature_list' => [
        'api_access' => ['enabled' => true],
        'advanced_reports' => ['enabled' => true],
    ],
]);
```

### Активация лицензии

**Через API:**
```bash
POST /api/license/activate
{
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "domain": "example.com"
}
```

**Программно:**
```php
$result = $licenseService->activate('XXXX-XXXX-XXXX-XXXX', 'example.com');
```

### Проверка лицензии в коде

**Использование хелпера:**
```php
if (has_license_feature('api_access')) {
    // Функция доступна
}

if (check_license()) {
    // Лицензия действительна
}

$license = get_current_license();
```

**Использование сервиса:**
```php
$licenseService = app(LicenseService::class);

if ($licenseService->hasFeature('advanced_reports')) {
    // Функция доступна
}
```

### Использование Middleware

**Проверка лицензии:**
```php
Route::middleware('license')->group(function () {
    Route::get('/api/data', [DataController::class, 'index']);
});
```

**Проверка конкретной функции:**
```php
Route::middleware('license:api_access')->group(function () {
    Route::get('/api/advanced', [AdvancedController::class, 'index']);
});
```

### Использование в Blade шаблонах

```blade
@if(has_license_feature('custom_branding'))
    <div class="custom-branding">
        <!-- Кастомный брендинг -->
    </div>
@endif
```

### Использование в Vue компонентах

```javascript
// В composable или компоненте
import api from '../services/api';

async function checkFeature(feature) {
    try {
        const response = await api.get(`/license/feature/${feature}`);
        return response.data.available;
    } catch (error) {
        return false;
    }
}

// Использование
if (await checkFeature('advanced_reports')) {
    // Показать расширенную отчетность
}
```

## API Endpoints

### POST /api/license/activate
Активация лицензии
```json
{
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "domain": "example.com"
}
```

### GET /api/license/check
Проверка текущей лицензии

### GET /api/license/info
Получение информации о лицензии

### GET /api/license/feature/{feature}
Проверка доступности функции

### POST /api/license/create (требует авторизации)
Создание новой лицензии
```json
{
    "type": "premium",
    "domain": "example.com",
    "expires_at": "2025-12-31",
    "max_users": 100,
    "features": ["api_access", "advanced_reports"]
}
```

### GET /api/license/list (требует авторизации)
Список всех лицензий

## Примеры использования

### Ограничение функционала по лицензии

```php
// В контроллере
public function exportData(Request $request)
{
    if (!has_license_feature('export_data')) {
        return response()->json([
            'message' => 'Экспорт данных недоступен в вашей лицензии'
        ], 403);
    }
    
    // Логика экспорта
}
```

### Проверка лимита пользователей

```php
public function register(Request $request)
{
    $licenseService = app(LicenseService::class);
    
    if (!$licenseService->checkUserLimit()) {
        return response()->json([
            'message' => 'Достигнут лимит пользователей'
        ], 403);
    }
    
    // Регистрация пользователя
}
```

## Конфигурация

Настройки находятся в `config/license.php`:

- `key` - Ключ текущей лицензии
- `check_mode` - Режим проверки (local/remote/hybrid)
- `features` - Список всех доступных функций
- `types` - Типы лицензий и их настройки

## Безопасность

1. **Хранение ключей**: Ключи лицензий хранятся в базе данных и `.env` файле
2. **Проверка домена**: Лицензии могут быть привязаны к конкретному домену
3. **Шифрование**: Опциональное шифрование данных лицензии
4. **Кеширование**: Результаты проверки кешируются для производительности

## Готовые решения

Если нужна более продвинутая система, рассмотрите:

1. **laravel-ready/license-server** - Готовый сервер лицензий
2. **masterix21/laravel-licensing** - Enterprise решение с офлайн проверкой
3. **fenixthelord/laravel-license-package** - Пакет с шифрованием

## Дальнейшее развитие

- [ ] Интеграция с сервером лицензий
- [ ] Офлайн проверка лицензий
- [ ] Шифрование данных лицензии
- [ ] Автоматическое обновление лицензий
- [ ] Уведомления об истечении лицензии
- [ ] Админ-панель для управления лицензиями
