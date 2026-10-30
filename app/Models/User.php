<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $attributes = ['is_active' => true, 'role' => 'customer'];

    public const ROLES = ['admin' => 'Admin hệ thống', 'manager' => 'Quản lý cửa hàng', 'customer' => 'Khách hàng'];

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    protected $fillable = ['name', 'email', 'password', 'delivery_name', 'phone', 'shipping_address'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean'];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
