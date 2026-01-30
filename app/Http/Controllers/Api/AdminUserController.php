<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    /**
     * Список всех пользователей (super-admin)
     */
    public function index(Request $request)
    {
        $query = User::query()->orderBy('name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        $perPage = min(max((int) ($request->per_page ?? 20), 1), 100);
        $users = $query->paginate($perPage);

        return response()->json(array_merge($users->toArray(), [
            'total_super_admins' => User::where('is_super_admin', true)->count(),
        ]));
    }

    /**
     * Создать пользователя (super-admin)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'is_admin' => 'boolean',
            'is_super_admin' => 'boolean',
            'is_moderator' => 'boolean',
        ], [
            'name.required' => 'Имя обязательно.',
            'email.required' => 'Email обязателен.',
            'email.unique' => 'Этот email уже зарегистрирован.',
            'phone.required' => 'Телефон обязателен.',
            'password.required' => 'Пароль обязателен.',
            'password.min' => 'Пароль должен быть не менее 8 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $data = $validator->validated();
        $phone = preg_replace('/\D/', '', $data['phone']);
        if (strlen($phone) < 10) {
            throw ValidationException::withMessages([
                'phone' => ['Некорректный номер телефона.'],
            ]);
        }
        if (User::where('phone', $phone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => ['Этот номер телефона уже зарегистрирован.'],
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $phone,
            'password' => Hash::make($data['password']),
            'is_admin' => $data['is_admin'] ?? false,
            'is_super_admin' => $data['is_super_admin'] ?? false,
            'is_moderator' => $data['is_moderator'] ?? false,
        ]);

        return response()->json([
            'message' => 'Пользователь создан',
            'user' => $user,
        ], 201);
    }

    /**
     * Обновить пользователя (super-admin)
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'sometimes|required|string|max:20',
            'is_admin' => 'boolean',
            'is_super_admin' => 'boolean',
            'is_moderator' => 'boolean',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $data = $validator->validated();

        if (isset($data['phone'])) {
            $phone = preg_replace('/\D/', '', $data['phone']);
            if (strlen($phone) < 10) {
                throw ValidationException::withMessages([
                    'phone' => ['Некорректный номер телефона.'],
                ]);
            }
            if (User::where('phone', $phone)->where('id', '!=', $user->id)->exists()) {
                throw ValidationException::withMessages([
                    'phone' => ['Этот номер телефона уже используется.'],
                ]);
            }
            $data['phone'] = $phone;
        }

        $user->update($data);

        return response()->json([
            'message' => 'Пользователь обновлён',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Удалить пользователя (super-admin)
     */
    public function destroy(User $user)
    {
        if ($user->is_super_admin && User::where('is_super_admin', true)->count() <= 1) {
            return response()->json([
                'message' => 'Нельзя удалить последнего супер-администратора.',
            ], 422);
        }

        if ($user->avatar) {
            $oldPath = preg_replace('#^/storage/#', '', $user->avatar);
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $user->delete();

        return response()->json(null, 204);
    }

    /**
     * Смена пароля пользователя (super-admin)
     */
    public function changePassword(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'Введите новый пароль.',
            'password.min' => 'Пароль должен быть не менее 8 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Пароль успешно изменён',
        ]);
    }
}
