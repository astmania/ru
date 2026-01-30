<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseFeature extends Model
{
    protected $fillable = [
        'license_id',
        'feature_key',
        'enabled',
        'settings',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Связь с лицензией
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
