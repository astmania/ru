<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class License extends Model
{
    protected $fillable = [
        'license_key',
        'domain',
        'type',
        'features',
        'expires_at',
        'is_active',
        'max_users',
        'max_requests_per_month',
        'notes',
        'customer_email',
        'customer_name',
    ];

    protected $casts = [
        'features' => 'array',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'max_users' => 'integer',
        'max_requests_per_month' => 'integer',
    ];

    /**
     * Связь с функциями лицензии
     */
    public function licenseFeatures(): HasMany
    {
        return $this->hasMany(LicenseFeature::class);
    }

    /**
     * Проверка, действительна ли лицензия
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Проверка, доступна ли функция
     */
    public function hasFeature(string $featureKey): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // Проверка в таблице license_features
        $feature = $this->licenseFeatures()
            ->where('feature_key', $featureKey)
            ->where('enabled', true)
            ->first();

        if ($feature) {
            return true;
        }

        // Проверка в JSON поле features
        if ($this->features && in_array($featureKey, $this->features)) {
            return true;
        }

        return false;
    }

    /**
     * Генерация нового ключа лицензии
     */
    public static function generateKey(): string
    {
        return strtoupper(
            substr(md5(uniqid(rand(), true)), 0, 8) . '-' .
            substr(md5(uniqid(rand(), true)), 0, 8) . '-' .
            substr(md5(uniqid(rand(), true)), 0, 8) . '-' .
            substr(md5(uniqid(rand(), true)), 0, 8)
        );
    }
}
