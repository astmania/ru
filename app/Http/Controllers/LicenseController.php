<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LicenseController extends Controller
{
    protected $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    /**
     * Активация лицензии
     */
    public function activate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'license_key' => 'required|string',
            'domain' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->licenseService->activate(
            $request->license_key,
            $request->domain ?? $request->getHost()
        );

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Проверка текущей лицензии
     */
    public function check(Request $request)
    {
        $license = $this->licenseService->getCurrentLicense();

        if (!$license) {
            return response()->json([
                'valid' => false,
                'message' => 'Лицензия не найдена',
            ], 404);
        }

        $isValid = $this->licenseService->checkLicense($request->getHost());

        return response()->json([
            'valid' => $isValid,
            'license' => [
                'key' => $license->license_key,
                'type' => $license->type,
                'expires_at' => $license->expires_at?->toIso8601String(),
                'is_active' => $license->is_active,
                'features' => $license->features,
            ],
        ]);
    }

    /**
     * Получить информацию о лицензии
     */
    public function info(Request $request)
    {
        $license = $this->licenseService->getCurrentLicense();

        if (!$license) {
            return response()->json([
                'message' => 'Лицензия не найдена',
            ], 404);
        }

        $features = [];
        foreach ($license->licenseFeatures as $feature) {
            $features[$feature->feature_key] = [
                'enabled' => $feature->enabled,
                'settings' => $feature->settings,
            ];
        }

        return response()->json([
            'license' => [
                'key' => $license->license_key,
                'type' => $license->type,
                'domain' => $license->domain,
                'expires_at' => $license->expires_at?->toIso8601String(),
                'is_active' => $license->is_active,
                'is_valid' => $license->isValid(),
                'max_users' => $license->max_users,
                'max_requests_per_month' => $license->max_requests_per_month,
                'features' => $features,
                'customer_name' => $license->customer_name,
                'customer_email' => $license->customer_email,
            ],
        ]);
    }

    /**
     * Проверка доступности функции
     */
    public function checkFeature(Request $request, string $feature)
    {
        $hasFeature = $this->licenseService->hasFeature($feature);

        return response()->json([
            'feature' => $feature,
            'available' => $hasFeature,
        ]);
    }

    /**
     * Создание новой лицензии (только для админов)
     */
    public function create(Request $request)
    {
        // TODO: Добавить проверку прав администратора

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:trial,basic,premium,enterprise',
            'domain' => 'nullable|string',
            'expires_at' => 'nullable|date',
            'max_users' => 'nullable|integer|min:1',
            'max_requests_per_month' => 'nullable|integer|min:1',
            'customer_email' => 'nullable|email',
            'customer_name' => 'nullable|string',
            'notes' => 'nullable|string',
            'features' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $license = $this->licenseService->createLicense($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Лицензия успешно создана',
            'license' => $license,
        ], 201);
    }

    /**
     * Список всех лицензий (только для админов)
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $search = $request->get('search');
        $type = $request->get('type');
        $status = $request->get('status'); // active, expired, inactive

        $query = License::with('licenseFeatures');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('license_key', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($status === 'active') {
            $query->where('is_active', true)
                  ->where(function($q) {
                      $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                  });
        } elseif ($status === 'expired') {
            $query->where('expires_at', '<=', now());
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $licenses = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($licenses);
    }

    /**
     * Получить одну лицензию
     */
    public function show($id)
    {
        $license = License::with('licenseFeatures')->findOrFail($id);

        return response()->json([
            'license' => $license,
        ]);
    }

    /**
     * Обновление лицензии
     */
    public function update(Request $request, $id)
    {
        $license = License::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:trial,basic,premium,enterprise',
            'domain' => 'nullable|string',
            'expires_at' => 'nullable|date',
            'is_active' => 'sometimes|boolean',
            'max_users' => 'nullable|integer|min:1',
            'max_requests_per_month' => 'nullable|integer|min:1',
            'customer_email' => 'nullable|email',
            'customer_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $license->update($validator->validated());

        // Очистка кеша
        \Illuminate\Support\Facades\Cache::forget("license:{$license->license_key}");

        return response()->json([
            'success' => true,
            'message' => 'Лицензия обновлена',
            'license' => $license->fresh(['licenseFeatures']),
        ]);
    }

    /**
     * Удаление лицензии
     */
    public function destroy($id)
    {
        $license = License::findOrFail($id);

        // Нельзя удалить текущую активную лицензию
        $currentLicense = $this->licenseService->getCurrentLicense();
        if ($currentLicense && $currentLicense->id === $license->id) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить текущую активную лицензию',
            ], 400);
        }

        $licenseKey = $license->license_key;
        $license->delete();

        // Очистка кеша
        \Illuminate\Support\Facades\Cache::forget("license:{$licenseKey}");

        return response()->json([
            'success' => true,
            'message' => 'Лицензия удалена',
        ]);
    }

    /**
     * Активация/деактивация лицензии
     */
    public function toggleStatus($id)
    {
        $license = License::findOrFail($id);
        $license->is_active = !$license->is_active;
        $license->save();

        // Очистка кеша
        \Illuminate\Support\Facades\Cache::forget("license:{$license->license_key}");

        return response()->json([
            'success' => true,
            'message' => $license->is_active ? 'Лицензия активирована' : 'Лицензия деактивирована',
            'license' => $license->fresh(['licenseFeatures']),
        ]);
    }

    /**
     * Управление функциями лицензии
     */
    public function updateFeatures(Request $request, $id)
    {
        $license = License::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'features' => 'required|array',
            'features.*.feature_key' => 'required|string',
            'features.*.enabled' => 'required|boolean',
            'features.*.settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Удаляем старые функции
        $license->licenseFeatures()->delete();

        // Создаем новые функции
        foreach ($request->features as $featureData) {
            $license->licenseFeatures()->create([
                'feature_key' => $featureData['feature_key'],
                'enabled' => $featureData['enabled'],
                'settings' => $featureData['settings'] ?? null,
            ]);
        }

        // Очистка кеша
        \Illuminate\Support\Facades\Cache::forget("license:{$license->license_key}");

        return response()->json([
            'success' => true,
            'message' => 'Функции лицензии обновлены',
            'license' => $license->fresh(['licenseFeatures']),
        ]);
    }

    /**
     * Статистика лицензий
     */
    public function statistics()
    {
        $stats = [
            'total' => License::count(),
            'active' => License::where('is_active', true)
                ->where(function($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                })->count(),
            'expired' => License::where('expires_at', '<=', now())->count(),
            'inactive' => License::where('is_active', false)->count(),
            'by_type' => License::selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
        ];

        return response()->json($stats);
    }
}
