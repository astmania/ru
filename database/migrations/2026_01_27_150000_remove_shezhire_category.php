<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $category = DB::table('categories')->where('slug', 'shezhire')->first();
        if ($category) {
            DB::table('articles')->where('category_id', $category->id)->update(['category_id' => null]);
            DB::table('categories')->where('id', $category->id)->delete();
        }
    }

    public function down(): void
    {
        // При откате можно заново создать категорию — не делаем автоматически
    }
};
