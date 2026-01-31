<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShejireNode;
use App\Models\ShejireTree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShejireController extends Controller
{
    /**
     * Проверка: пользователь может редактировать дерево (владелец, администратор или супер-админ).
     */
    private function canManageTree(Request $request, ShejireTree $shejire): bool
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($shejire->user_id === $user->id) {
            return true;
        }
        return $user->isAdmin() || $user->isSuperAdmin();
    }

    /**
     * Список одобренных деревьев (публичный)
     */
    public function index(Request $request)
    {
        $query = ShejireTree::query()
            ->with(['user:id,name', 'nodes' => fn ($q) => $q->orderBy('sort_order')])
            ->orderByDesc('created_at');

        if (!$request->user()) {
            $query->where('status', 'approved');
        } else {
            $query->where(function ($q) use ($request) {
                $q->where('status', 'approved')
                    ->orWhere('user_id', $request->user()->id);
            });
        }

        $trees = $query->get();

        return response()->json($trees);
    }

    /**
     * Одно дерево по id (публично если approved, иначе владелец или админ/супер-админ)
     */
    public function show(Request $request, ShejireTree $shejire)
    {
        $user = $request->user();
        $canView = $shejire->isApproved()
            || ($user && ($shejire->user_id === $user->id || $user->isAdmin() || $user->isSuperAdmin()));
        if (!$canView) {
            return response()->json(['message' => 'Дерево не найдено или ожидает модерации'], 404);
        }

        $shejire->load(['user:id,name', 'nodes' => fn ($q) => $q->orderBy('sort_order')]);

        return response()->json($shejire);
    }

    /**
     * Создать дерево (авторизованный пользователь)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tree = ShejireTree::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'status' => 'pending',
        ]);

        return response()->json($tree->load('nodes'), 201);
    }

    /**
     * Обновить дерево (владелец). При редактировании одобренного — статус сбрасывается на pending.
     */
    public function update(Request $request, ShejireTree $shejire)
    {
        if (!$this->canManageTree($request, $shejire)) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        if ($shejire->isApproved()) {
            $data['status'] = 'pending';
            $data['moderator_id'] = null;
            $data['moderated_at'] = null;
            $data['rejected_reason'] = null;
        }
        $shejire->update($data);
        $shejire->load('nodes');

        return response()->json($shejire);
    }

    /**
     * Удалить дерево (владелец)
     */
    public function destroy(Request $request, ShejireTree $shejire)
    {
        if (!$this->canManageTree($request, $shejire)) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $shejire->delete();
        return response()->json(null, 204);
    }

    /**
     * Добавить узел (владелец дерева). При редактировании одобренного — статус сбрасывается на pending.
     */
    public function storeNode(Request $request, ShejireTree $shejire)
    {
        if (!$this->canManageTree($request, $shejire)) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }
        if ($shejire->isApproved()) {
            $shejire->update([
                'status' => 'pending',
                'moderator_id' => null,
                'moderated_at' => null,
                'rejected_reason' => null,
            ]);
        }

        $input = $request->all();
        // Нормализация пустых строк в null, чтобы валидация date не падала на ""
        foreach (['parent_id', 'birth_date', 'death_date', 'moderator_comment'] as $key) {
            if (isset($input[$key]) && $input[$key] === '') {
                $input[$key] = null;
            }
        }

        $validator = Validator::make($input, [
            'parent_id' => [
                'nullable',
                'exists:shejire_nodes,id',
                function ($attribute, $value, $fail) use ($shejire) {
                    if ($value && ShejireNode::where('id', $value)->where('shejire_tree_id', $shejire->id)->doesntExist()) {
                        $fail('Родитель должен принадлежать этому дереву.');
                    }
                },
            ],
            'full_name' => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'death_date' => 'nullable|date|after_or_equal:birth_date',
            'moderator_comment' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['shejire_tree_id'] = $shejire->id;
        $data['parent_id'] = $data['parent_id'] ?? null;
        $data['sort_order'] = $data['sort_order'] ?? (($shejire->nodes()->max('sort_order') ?? 0) + 1);

        $node = ShejireNode::create($data);

        return response()->json($node, 201);
    }

    /**
     * Обновить узел (владелец дерева)
     */
    public function updateNode(Request $request, ShejireTree $shejire, ShejireNode $node)
    {
        if ($node->shejire_tree_id !== $shejire->id) {
            return response()->json(['message' => 'Узел не принадлежит этому дереву'], 404);
        }
        if (!$this->canManageTree($request, $shejire)) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }
        if ($shejire->isApproved()) {
            $shejire->update([
                'status' => 'pending',
                'moderator_id' => null,
                'moderated_at' => null,
                'rejected_reason' => null,
            ]);
        }

        $input = $request->all();
        foreach (['parent_id', 'birth_date', 'death_date', 'moderator_comment'] as $key) {
            if (isset($input[$key]) && $input[$key] === '') {
                $input[$key] = null;
            }
        }

        $validator = Validator::make($input, [
            'parent_id' => 'nullable|exists:shejire_nodes,id',
            'full_name' => 'sometimes|required|string|max:255',
            'birth_date' => 'nullable|date',
            'death_date' => 'nullable|date|after_or_equal:birth_date',
            'moderator_comment' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $node->update($validator->validated());

        return response()->json($node->fresh());
    }

    /**
     * Удалить узел (владелец дерева). При редактировании одобренного — статус сбрасывается на pending.
     */
    public function destroyNode(Request $request, ShejireTree $shejire, ShejireNode $node)
    {
        if ($node->shejire_tree_id !== $shejire->id) {
            return response()->json(['message' => 'Узел не принадлежит этому дереву'], 404);
        }
        if (!$this->canManageTree($request, $shejire)) {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }
        if ($shejire->isApproved()) {
            $shejire->update([
                'status' => 'pending',
                'moderator_id' => null,
                'moderated_at' => null,
                'rejected_reason' => null,
            ]);
        }

        $node->delete();
        return response()->json(null, 204);
    }

    /**
     * Список деревьев пользователя (свои)
     */
    public function myTrees(Request $request)
    {
        $trees = ShejireTree::where('user_id', $request->user()->id)
            ->with(['user:id,name', 'nodes'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($trees);
    }

    /**
     * Список деревьев на модерации (модератор)
     */
    public function moderationIndex(Request $request)
    {
        $trees = ShejireTree::where('status', 'pending')
            ->with(['user:id,name,email', 'nodes'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($trees);
    }

    /**
     * Одобрить дерево (модератор)
     */
    public function approve(Request $request, ShejireTree $shejire)
    {
        if (!$shejire->isPending()) {
            return response()->json(['message' => 'Дерево уже обработано'], 400);
        }

        $shejire->update([
            'status' => 'approved',
            'moderator_id' => $request->user()->id,
            'moderated_at' => now(),
            'rejected_reason' => null,
        ]);

        return response()->json($shejire->load('nodes'));
    }

    /**
     * Отклонить дерево (модератор)
     */
    public function reject(Request $request, ShejireTree $shejire)
    {
        if (!$shejire->isPending()) {
            return response()->json(['message' => 'Дерево уже обработано'], 400);
        }

        $validator = Validator::make($request->all(), [
            'rejected_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $shejire->update([
            'status' => 'rejected',
            'moderator_id' => $request->user()->id,
            'moderated_at' => now(),
            'rejected_reason' => $request->rejected_reason,
        ]);

        return response()->json($shejire);
    }
}
