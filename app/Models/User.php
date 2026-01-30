<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'is_admin',
        'is_super_admin',
        'is_moderator',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_super_admin' => 'boolean',
            'is_moderator' => 'boolean',
        ];
    }

    /**
     * Проверка, является ли пользователь администратором
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * Проверка, является ли пользователь супер-администратором
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    /**
     * Проверка, является ли пользователь модератором (Шежіре)
     */
    public function isModerator(): bool
    {
        return $this->is_moderator === true;
    }

    public function shejireTrees(): HasMany
    {
        return $this->hasMany(ShejireTree::class);
    }
}
