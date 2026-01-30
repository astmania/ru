<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shejire_trees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255)->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejected_reason')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shejire_trees');
    }
};
