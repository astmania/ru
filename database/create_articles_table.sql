-- Создание таблицы articles для MariaDB/MySQL
-- Выполните в phpMyAdmin или: mysql -u USER -p laravel_ru < create_articles_table.sql

CREATE TABLE IF NOT EXISTS `articles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `excerpt` VARCHAR(500) NULL,
    `body` LONGTEXT NOT NULL,
    `image` VARCHAR(255) NULL,
    `published_at` TIMESTAMP NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `articles_slug_unique` (`slug`),
    CONSTRAINT `articles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
