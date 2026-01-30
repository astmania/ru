<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DeployedProject extends Model
{
    protected $fillable = [
        'name',
        'url',
        'api_key',
        'api_secret',
        'license_key',
        'license_id',
        'server_ip',
        'server_user',
        'ssh_key',
        'status',
        'last_health_check',
        'health_status',
        'server_info',
        'notes',
        'contact_email',
        'contact_name',
        'notifications_enabled',
    ];

    protected $casts = [
        'health_status' => 'array',
        'server_info' => 'array',
        'last_health_check' => 'datetime',
        'notifications_enabled' => 'boolean',
    ];

    /**
     * Связь с лицензией
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    /**
     * Генерация API ключей
     */
    public static function generateApiKey(): string
    {
        return 'dp_' . Str::random(32);
    }

    public static function generateApiSecret(): string
    {
        return Str::random(64);
    }

    /**
     * Проверка, активен ли проект
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Проверка, есть ли проблемы
     */
    public function hasIssues(): bool
    {
        return in_array($this->status, ['error', 'maintenance']);
    }

    /**
     * Получить статус здоровья
     */
    public function getHealthStatus(): array
    {
        return $this->health_status ?? [];
    }
}
