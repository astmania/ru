<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShejireNode extends Model
{
    protected $fillable = [
        'shejire_tree_id',
        'parent_id',
        'full_name',
        'birth_date',
        'death_date',
        'moderator_comment',
        'sort_order',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
    ];

    protected $appends = ['display_name'];

    public function tree(): BelongsTo
    {
        return $this->belongsTo(ShejireTree::class, 'shejire_tree_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ShejireNode::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ShejireNode::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Отображаемое имя: Фамилия И.О.
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = preg_split('/\s+/u', trim($this->full_name), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts)) {
            return $this->full_name;
        }
        $surname = $parts[0];
        $initials = array_slice($parts, 1);
        $initialsStr = implode('.', array_map(fn ($p) => mb_substr($p, 0, 1) . '.', $initials));
        return trim($surname . ' ' . $initialsStr);
    }
}
