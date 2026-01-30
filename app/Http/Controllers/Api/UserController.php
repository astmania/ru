<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Получить информацию об аутентифицированном пользователе
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Обновить профиль пользователя
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'sometimes|required|string|max:20|unique:users,phone,' . $user->id,
            'avatar' => 'sometimes|nullable|string|max:500',
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
            $data['phone'] = $phone;
        }

        if (array_key_exists('avatar', $data)) {
            if (empty($data['avatar']) && $user->avatar) {
                $oldPath = preg_replace('#^/storage/#', '', $user->avatar);
                if ($oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
                $data['avatar'] = null;
            }
        }

        $user->update($data);

        return response()->json([
            'message' => 'Профиль обновлён',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Загрузить аватар пользователя
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = $request->user();

        if ($user->avatar) {
            $oldPath = preg_replace('#^/storage/#', '', $user->avatar);
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $url = '/storage/' . $path;

        $user->update(['avatar' => $url]);

        return response()->json([
            'message' => 'Аватар загружен',
            'avatar' => $url,
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Смена пароля
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'Введите текущий пароль.',
            'password.required' => 'Введите новый пароль.',
            'password.min' => 'Пароль должен быть не менее 8 символов.',
            'password.confirmed' => 'Пароли не совпадают.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Неверный текущий пароль.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Пароль успешно изменён',
        ]);
    }

    /**
     * Пример защищенного API endpoint
     */
    public function protectedData(Request $request)
    {
        return response()->json([
            'message' => 'Это защищенные данные, доступные только для аутентифицированных пользователей',
            'user_id' => $request->user()->id,
            'timestamp' => now(),
        ]);
    }
}
