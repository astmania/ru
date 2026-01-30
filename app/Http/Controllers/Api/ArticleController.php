<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    /**
     * Список опубликованных статей (публичный)
     */
    public function index(Request $request)
    {
        $query = Article::published()->with(['author:id,name,avatar', 'category:id,name,slug', 'tags:id,name,slug'])->orderByDesc('published_at');

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }
        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $request->tag));
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('excerpt', 'like', "%{$s}%");
            });
        }
        if ($request->has('per_page')) {
            $perPage = min(max((int) $request->per_page, 1), 100);
            $page = max((int) $request->input('page', 1), 1);
            $articles = $query->paginate($perPage, ['*'], 'page', $page);
        } else {
            $articles = $query->get();
        }

        return response()->json($articles);
    }

    /**
     * Одна статья по id или slug (публичный)
     */
    public function show(string $idOrSlug)
    {
        $article = Article::published()
            ->with(['author:id,name,avatar', 'category:id,name,slug', 'tags:id,name,slug'])
            ->where(function ($q) use ($idOrSlug) {
                $q->where('id', $idOrSlug)->orWhere('slug', $idOrSlug);
            })
            ->firstOrFail();

        return response()->json($article);
    }

    /**
     * Список всех статей для админа (включая черновики)
     */
    public function adminIndex(Request $request)
    {
        $query = Article::with(['author:id,name,avatar', 'category:id,name,slug', 'tags:id,name,slug'])->orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('excerpt', 'like', "%{$s}%");
            });
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('id', $request->tag));
        }
        if ($request->filled('published')) {
            if ($request->published === '1') {
                $query->published();
            } else {
                $query->whereNull('published_at')->orWhere('published_at', '>', now());
            }
        }

        $perPage = min(max((int) ($request->per_page ?? 15), 1), 100);
        $articles = $query->paginate($perPage);

        return response()->json($articles);
    }

    /**
     * Одна статья для админа (по id, включая черновики)
     */
    public function adminShow(Article $article)
    {
        $article->load(['author:id,name,avatar', 'category:id,name,slug', 'tags:id,name,slug']);
        return response()->json($article);
    }

    /**
     * Загрузка картинки для статьи (админ)
     */
    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|image|max:5120', // до 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $path = $request->file('image')->storePublicly('articles', 'public');
        // Формируем публичный URL через /storage (нужен storage:link)
        $url = asset('storage/' . ltrim($path, '/'));

        return response()->json([
            'url' => $url,
            'path' => $path,
        ], 201);
    }

    /**
     * Создание статьи (админ)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:articles,slug',
            'excerpt' => 'nullable|string|max:250',
            'body' => 'required|string',
            'image' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'published_at' => 'nullable|date',
            'category_id' => 'nullable|exists:categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $tagIds = $data['tag_ids'] ?? null;
        unset($data['tag_ids']);
        $data['slug'] = $data['slug'] ?? Str::slug($data['title']);
        $data['user_id'] = $request->user()->id;

        $article = Article::create($data);
        if ($tagIds !== null) {
            $article->tags()->sync($tagIds);
        }
        $article->load(['author:id,name,avatar', 'category:id,name,slug', 'tags:id,name,slug']);

        return response()->json($article, 201);
    }

    /**
     * Обновление статьи (админ)
     */
    public function update(Request $request, Article $article)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:articles,slug,' . $article->id,
            'excerpt' => 'nullable|string|max:250',
            'body' => 'sometimes|required|string',
            'image' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'published_at' => 'nullable|date',
            'category_id' => 'nullable|exists:categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $tagIds = $data['tag_ids'] ?? null;
        unset($data['tag_ids']);
        if (isset($data['title']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $article->update($data);
        if ($tagIds !== null) {
            $article->tags()->sync($tagIds);
        }
        $article->load(['author:id,name,avatar', 'category:id,name,slug', 'tags:id,name,slug']);

        return response()->json($article);
    }

    /**
     * Удаление статьи (админ)
     */
    public function destroy(Article $article)
    {
        $article->delete();
        return response()->json(null, 204);
    }
}
