<?php

namespace App\Services;

use App\Models\License;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    /**
     * Получить текущую лицензию из конфигурации
     */
    public function getCurrentLicense(): ?License
    {
        $licenseKey = config('license.key');
        
        if (!$licenseKey) {
            return null;
        }

        return Cache::remember("license:{$licenseKey}", 3600, function () use ($licenseKey) {
            return License::where('license_key', $licenseKey)
                ->with('licenseFeatures')
                ->first();
        });
    }

    /**
     * Проверка лицензии
     */
    public function checkLicense(?string $domain = null): bool
    {
        $license = $this->getCurrentLicense();

        if (!$license) {
            Log::warning('License not found');
            return false;
        }

        // Проверка домена, если указан
        if ($domain && $license->domain && $license->domain !== $domain) {
            Log::warning("License domain mismatch: expected {$license->domain}, got {$domain}");
            return false;
        }

        if (!$license->isValid()) {
            Log::warning("License is invalid or expired: {$license->license_key}");
            return false;
        }

        return true;
    }

    /**
     * Проверка доступности функции
     */
    public function hasFeature(string $featureKey): bool
    {
        $license = $this->getCurrentLicense();

        if (!$license) {
            return false;
        }

        return $license->hasFeature($featureKey);
    }

    /**
     * Активация лицензии
     */
    public function activate(string $licenseKey, ?string $domain = null): array
    {
        $license = License::where('license_key', $licenseKey)->first();

        if (!$license) {
            return [
                'success' => false,
                'message' => 'Лицензия не найдена',
            ];
        }

        if (!$license->isValid()) {
            return [
                'success' => false,
                'message' => 'Лицензия недействительна или истекла',
            ];
        }

        // Проверка домена
        if ($license->domain && $license->domain !== $domain) {
            return [
                'success' => false,
                'message' => 'Лицензия привязана к другому домену',
            ];
        }

        // Сохранение лицензии в конфигурацию
        $this->saveLicenseKey($licenseKey);

        // Очистка кеша
        Cache::forget("license:{$licenseKey}");

        return [
            'success' => true,
            'message' => 'Лицензия успешно активирована',
            'license' => $license,
        ];
    }

    /**
     * Сохранение ключа лицензии в конфигурацию
     */
    protected function saveLicenseKey(string $licenseKey): void
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        // Обновление или добавление LICENSE_KEY
        if (preg_match('/^LICENSE_KEY=.*/m', $envContent)) {
            $envContent = preg_replace('/^LICENSE_KEY=.*/m', "LICENSE_KEY={$licenseKey}", $envContent);
        } else {
            $envContent .= "\nLICENSE_KEY={$licenseKey}\n";
        }

        file_put_contents($envFile, $envContent);
    }

    /**
     * Создание новой лицензии
     */
    public function createLicense(array $data): License
    {
        $licenseKey = $data['license_key'] ?? License::generateKey();

        $license = License::create([
            'license_key' => $licenseKey,
            'domain' => $data['domain'] ?? null,
            'type' => $data['type'] ?? 'trial',
            'features' => $data['features'] ?? [],
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'max_users' => $data['max_users'] ?? null,
            'max_requests_per_month' => $data['max_requests_per_month'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        // Создание функций лицензии
        if (isset($data['feature_list']) && is_array($data['feature_list'])) {
            foreach ($data['feature_list'] as $featureKey => $featureData) {
                $license->licenseFeatures()->create([
                    'feature_key' => $featureKey,
                    'enabled' => $featureData['enabled'] ?? true,
                    'settings' => $featureData['settings'] ?? null,
                ]);
            }
        }

        return $license;
    }

    /**
     * Проверка лимита пользователей
     */
    public function checkUserLimit(): bool
    {
        $license = $this->getCurrentLicense();

        if (!$license || !$license->max_users) {
            return true; // Нет лимита
        }

        $currentUsers = \App\Models\User::count();

        return $currentUsers < $license->max_users;
    }

    /**
     * Проверка лимита запросов
     */
    public function checkRequestLimit(): bool
    {
        $license = $this->getCurrentLicense();

        if (!$license || !$license->max_requests_per_month) {
            return true; // Нет лимита
        }

        // Здесь можно добавить логику подсчета запросов за месяц
        // Например, через Redis или отдельную таблицу
        $monthlyRequests = Cache::get("license_requests:{$license->license_key}:month", 0);

        return $monthlyRequests < $license->max_requests_per_month;
    }

    /**
     * Увеличение счетчика запросов
     */
    public function incrementRequestCount(): void
    {
        $license = $this->getCurrentLicense();

        if (!$license || !$license->max_requests_per_month) {
            return;
        }

        $key = "license_requests:{$license->license_key}:month";
        $count = Cache::get($key, 0);
        Cache::put($key, $count + 1, now()->endOfMonth());
    }
}
