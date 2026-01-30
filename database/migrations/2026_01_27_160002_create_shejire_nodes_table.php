<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shejire_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shejire_tree_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('shejire_nodes')->nullOnDelete();
            $table->string('full_name', 255);
            $table->date('birth_date')->nullable();
            $table->date('death_date')->nullable();
            $table->text('moderator_comment')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shejire_nodes');
    }
};
