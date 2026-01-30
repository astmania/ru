<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Опциональная аутентификация: если передан Bearer-токен — устанавливает пользователя,
 * иначе продолжает без ошибки (user = null).
 */
class OptionalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken()) {
            try {
                $user = Auth::guard('api')->user();
                if ($user) {
                    $request->setUserResolver(fn () => $user);
                }
            } catch (\Throwable $e) {
                // Токен невалиден — продолжаем без пользователя
            }
        }

        return $next($request);
    }
}
