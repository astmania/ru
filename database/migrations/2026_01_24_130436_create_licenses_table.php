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
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key')->unique();
            $table->string('domain')->nullable(); // Домен для привязки лицензии
            $table->string('type')->default('trial'); // trial, basic, premium, enterprise
            $table->json('features')->nullable(); // Список доступных функций
            $table->dateTime('expires_at')->nullable(); // Дата истечения
            $table->boolean('is_active')->default(true);
            $table->integer('max_users')->nullable(); // Максимальное количество пользователей
            $table->integer('max_requests_per_month')->nullable(); // Лимит запросов
            $table->text('notes')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_name')->nullable();
            $table->timestamps();
            
            $table->index('license_key');
            $table->index('is_active');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
