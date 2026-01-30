<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('deployed_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Название проекта
            $table->string('url'); // URL проекта
            $table->string('api_key')->unique(); // Уникальный ключ для API доступа
            $table->string('api_secret'); // Секретный ключ для аутентификации
            $table->string('license_key')->nullable(); // Ключ лицензии проекта
            $table->foreignId('license_id')->nullable()->constrained('licenses')->onDelete('set null');
            $table->string('server_ip')->nullable(); // IP сервера
            $table->string('server_user')->nullable(); // Пользователь для SSH доступа
            $table->text('ssh_key')->nullable(); // SSH ключ для доступа
            $table->string('status')->default('active'); // active, inactive, error, maintenance
            $table->timestamp('last_health_check')->nullable(); // Последняя проверка
            $table->json('health_status')->nullable(); // Результаты проверки здоровья
            $table->json('server_info')->nullable(); // Информация о сервере
            $table->text('notes')->nullable(); // Примечания
            $table->string('contact_email')->nullable(); // Email контакта
            $table->string('contact_name')->nullable(); // Имя контакта
            $table->boolean('notifications_enabled')->default(true); // Включены ли уведомления
            $table->timestamps();
            
            $table->index('api_key');
            $table->index('status');
            $table->index('last_health_check');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployed_projects');
    }
};
