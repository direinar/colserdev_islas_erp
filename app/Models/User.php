<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_ISLERO = 'islero';
    public const ROLE_JEFE_PATIOS = 'jefe_patios';
    public const ROLE_ADMINISTRADOR = 'administrador';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

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
        ];
    }

    public static function roles(): array
    {
        return [
            self::ROLE_ISLERO,
            self::ROLE_JEFE_PATIOS,
            self::ROLE_ADMINISTRADOR,
        ];
    }

    public function hasRole(string $role): bool
    {
        return strcasecmp($this->role, $role) === 0;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array(strtolower($this->role), array_map('strtolower', $roles), true);
    }

    public function isIslero(): bool
    {
        return $this->hasRole(self::ROLE_ISLERO);
    }

    public function isJefePatios(): bool
    {
        return $this->hasRole(self::ROLE_JEFE_PATIOS);
    }

    public function isAdministrador(): bool
    {
        return $this->hasRole(self::ROLE_ADMINISTRADOR);
    }

    public function canAccessTurnos(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ISLERO,
            self::ROLE_JEFE_PATIOS,
            self::ROLE_ADMINISTRADOR,
        ]);
    }

    public function canAccessCartera(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_JEFE_PATIOS,
            self::ROLE_ADMINISTRADOR,
        ]);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
