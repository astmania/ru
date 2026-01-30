<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('meta_title', 255)->nullable()->after('image');
            $table->string('meta_description', 500)->nullable()->after('meta_title');
            $table->string('meta_keywords', 500)->nullable()->after('meta_description');
            $table->foreignId('category_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['meta_title', 'meta_description', 'meta_keywords']);
        });
    }
};
