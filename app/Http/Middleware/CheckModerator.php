<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModerator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isModerator()) {
            return response()->json([
                'message' => 'Доступ запрещен. Требуются права модератора.',
                'error' => 'forbidden',
            ], 403);
        }

        return $next($request);
    }
}
