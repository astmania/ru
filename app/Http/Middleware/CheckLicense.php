<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LicenseService;

class CheckLicense
{
    protected $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        // Проверка лицензии
        if (!$this->licenseService->checkLicense($request->getHost())) {
            return response()->json([
                'message' => 'Лицензия недействительна или истекла',
                'error' => 'license_invalid',
            ], 403);
        }

        // Проверка конкретной функции, если указана
        if ($feature && !$this->licenseService->hasFeature($feature)) {
            return response()->json([
                'message' => "Функция '{$feature}' недоступна в вашей лицензии",
                'error' => 'feature_not_available',
            ], 403);
        }

        // Проверка лимита пользователей
        if (!$this->licenseService->checkUserLimit()) {
            return response()->json([
                'message' => 'Достигнут лимит пользователей',
                'error' => 'user_limit_exceeded',
            ], 403);
        }

        // Увеличение счетчика запросов
        $this->licenseService->incrementRequestCount();

        return $next($request);
    }
}
