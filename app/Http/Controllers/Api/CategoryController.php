<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Список категорий (публичный, для фильтров и формы статьи)
     */
    public function index()
    {
        $categories = Category::orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'slug', 'sort_order']);
        return response()->json($categories);
    }
}
