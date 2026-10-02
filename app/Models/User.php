<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const PROFILE_ADMIN = 'admin';
    public const PROFILE_MOTORISTA = 'motorista';

    protected $fillable = ['name', 'email', 'password', 'profile'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'locked_until'      => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function motorista()
    {
        return $this->hasOne(Motorista::class);
    }

    public function isAdmin(): bool
    {
        return $this->profile === self::PROFILE_ADMIN;
    }

    public function isMotorista(): bool
    {
        return $this->profile === self::PROFILE_MOTORISTA;
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }
}
