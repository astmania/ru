<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Тарих', 'slug' => 'tarih', 'sort_order' => 1],
            ['name' => 'Мәдениет', 'slug' => 'madeniet', 'sort_order' => 2],
            ['name' => 'Саясат', 'slug' => 'saiasat', 'sort_order' => 3],
            ['name' => 'Батырлар', 'slug' => 'batyrlar', 'sort_order' => 4],
            ['name' => 'Спорт', 'slug' => 'sport', 'sort_order' => 5],
        ];

        foreach ($items as $item) {
            Category::firstOrCreate(
                ['slug' => $item['slug']],
                ['name' => $item['name'], 'sort_order' => $item['sort_order']]
            );
        }
    }
}
