<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TagController extends Controller
{
    /**
     * Список тегов (публичный, для быстрого поиска и формы)
     */
    public function index()
    {
        $tags = Tag::orderBy('name')->get(['id', 'name', 'slug']);
        return response()->json($tags);
    }

    /**
     * Создание тега (админ)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $name = trim($request->name);
        $slug = Str::slug($name);
        $tag = Tag::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name]
        );
        return response()->json($tag, 201);
    }

    /**
     * Удаление тега (админ). Связи со статьями удаляются автоматически (pivot cascade).
     */
    public function destroy(Tag $tag)
    {
        $tag->delete();
        return response()->json(null, 204);
    }
}
