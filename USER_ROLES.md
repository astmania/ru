# Управление ролями пользователей

## Создание супер-администратора

### Способ 1: Через Artisan команду (интерактивно)

```bash
php artisan user:create-super-admin
```

Команда запросит:
- Имя пользователя
- Email
- Пароль

### Способ 2: Через Artisan команду (с параметрами)

```bash
php artisan user:create-super-admin --name="Admin User" --email="admin@example.com" --password="secure_password123"
```

### Способ 3: Через Seeder

```bash
php artisan db:seed --class=SuperAdminSeeder
```

Создаст пользователя:
- Email: `admin@example.com`
- Пароль: `admin123456`
- ⚠️ **ВАЖНО**: Измените пароль после первого входа!

### Способ 4: При установке проекта

```bash
php artisan migrate --seed
```

Автоматически создаст супер-администратора через `DatabaseSeeder`.

## Назначение ролей существующему пользователю

### Назначить роль администратора

```bash
php artisan user:assign-role user@example.com --admin
```

### Назначить роль супер-администратора

```bash
php artisan user:assign-role user@example.com --super-admin
```

### Убрать роль администратора

```bash
php artisan user:assign-role user@example.com --remove-admin
```

### Убрать роль супер-администратора

```bash
php artisan user:assign-role user@example.com --remove-super-admin
```

## Проверка ролей пользователя

```bash
php artisan user:assign-role user@example.com
```

Без опций команда покажет текущие роли пользователя.

## Через Tinker (интерактивная консоль)

```bash
php artisan tinker
```

```php
// Найти пользователя
$user = User::where('email', 'user@example.com')->first();

// Назначить супер-администратора
$user->is_admin = true;
$user->is_super_admin = true;
$user->save();

// Проверить роли
$user->isAdmin(); // true
$user->isSuperAdmin(); // true
```

## Через SQL (напрямую в БД)

```sql
-- Назначить супер-администратора
UPDATE users 
SET is_admin = 1, is_super_admin = 1 
WHERE email = 'user@example.com';

-- Проверить роли
SELECT id, name, email, is_admin, is_super_admin 
FROM users 
WHERE email = 'user@example.com';
```
