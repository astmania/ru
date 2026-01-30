<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Регистрация нового пользователя
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $phone = preg_replace('/\D/', '', $validated['phone']);
        if (strlen($phone) < 10) {
            throw ValidationException::withMessages([
                'phone' => ['Некорректный номер телефона. Укажите минимум 10 цифр.'],
            ]);
        }
        if (User::where('phone', $phone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => ['Этот номер телефона уже зарегистрирован.'],
            ]);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $phone,
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('Personal Access Token')->accessToken;

        return response()->json([
            'message' => 'Пользователь успешно зарегистрирован',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Авторизация пользователя
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        $token = $user->createToken('Personal Access Token')->accessToken;

        return response()->json([
            'message' => 'Успешная авторизация',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Выход пользователя (отзыв токена)
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Успешный выход из системы',
        ]);
    }

    /**
     * Получить информацию о текущем пользователе
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }
}
