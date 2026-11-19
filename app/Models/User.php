<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_STAFF = 'staff';
    public const ROLE_CUSTOMER = 'customer';

    public const ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_MANAGER,
        self::ROLE_STAFF,
        self::ROLE_CUSTOMER,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'customer_id');
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function canManageInventory(): bool
    {
        return $this->hasRole([self::ROLE_ADMIN, self::ROLE_MANAGER]);
    }

    public function canOperateOrders(): bool
    {
        return $this->hasRole([self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_STAFF]);
    }

    public function dashboardRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'dashboard',
            self::ROLE_MANAGER => 'dashboard',
            self::ROLE_STAFF => 'dashboard',
            self::ROLE_CUSTOMER => 'dashboard',
            default => 'dashboard',
        };
    }
}
