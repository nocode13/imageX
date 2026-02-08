<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Атрибуты, доступные для массового заполнения.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Атрибуты, скрытые при сериализации.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Получить атрибуты для приведения типов.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /**
     * @return HasMany<UserImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(UserImage::class);
    }
}
