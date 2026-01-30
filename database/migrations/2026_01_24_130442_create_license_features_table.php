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
        Schema::create('license_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('licenses')->onDelete('cascade');
            $table->string('feature_key'); // Например: 'advanced_reports', 'api_access', 'custom_branding'
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable(); // Дополнительные настройки функции
            $table->timestamps();
            
            $table->unique(['license_id', 'feature_key']);
            $table->index('feature_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_features');
    }
};
