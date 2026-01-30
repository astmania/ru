<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShejireTree extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'status',
        'moderator_id',
        'rejected_reason',
        'moderated_at',
    ];

    protected $casts = [
        'moderated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(ShejireNode::class, 'shejire_tree_id')->orderBy('sort_order');
    }

    public function rootNodes(): HasMany
    {
        return $this->hasMany(ShejireNode::class, 'shejire_tree_id')
            ->whereNull('parent_id')
            ->orderBy('sort_order');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
