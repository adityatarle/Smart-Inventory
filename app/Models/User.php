<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'role',
        'is_active',
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
            'is_active' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function primaryRole(): ?Role
    {
        return $this->roles->first();
    }

    public function primaryRoleSlug(): string
    {
        return $this->primaryRole()?->slug
            ?? $this->role
            ?? 'viewer';
    }

    public function primaryRoleName(): string
    {
        return $this->primaryRole()?->name
            ?? ucfirst(str_replace('_', ' ', $this->primaryRoleSlug()));
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        $slugs = $this->roles->pluck('slug')->all();

        if ($slugs === [] && $this->role) {
            $slugs[] = $this->role;
        }

        return count(array_intersect($slugs, $roles)) > 0;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole(['super_admin'])) {
            return true;
        }

        $module = explode('.', $permission)[0] ?? null;

        foreach ($this->roles as $role) {
            $slugs = $role->permissions->pluck('slug');

            if ($slugs->contains($permission)) {
                return true;
            }

            // `{module}.manage` grants all actions for that module
            if ($module && $slugs->contains("{$module}.manage")) {
                return true;
            }
        }

        // Legacy fallback while migrating from enum role
        return match ($this->role) {
            'admin' => true,
            'manager' => in_array($permission, [
                'dashboard.view', 'products.view', 'categories.view', 'warehouses.view',
                'inventory.view', 'purchase_orders.view', 'sales_orders.view', 'customers.view',
                'suppliers.view', 'reports.view', 'notifications.view', 'settings.view',
            ], true) || str_ends_with($permission, '.view') || str_ends_with($permission, '.manage'),
            'staff', 'storekeeper' => in_array($permission, [
                'dashboard.view', 'products.view', 'inventory.view', 'warehouses.view', 'sales_orders.view',
                'notifications.view',
            ], true),
            default => $permission === 'dashboard.view',
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(['super_admin']);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(['super_admin', 'admin']) || $this->role === 'admin';
    }

    public function canManageInventory(): bool
    {
        return $this->hasPermission('warehouses.manage') || $this->hasRole(['super_admin', 'admin', 'manager']);
    }

    public function canOperateOrders(): bool
    {
        return $this->hasPermission('sales_orders.view')
            || $this->hasRole(['super_admin', 'admin', 'manager', 'storekeeper', 'sales_manager', 'purchase_manager'])
            || in_array($this->role, ['admin', 'manager', 'staff'], true);
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer' && ! $this->roles()->exists();
    }

    public function isManager(): bool
    {
        return $this->hasRole(['manager']) || $this->role === 'manager';
    }

    public function isStaff(): bool
    {
        return $this->hasRole(['storekeeper']) || $this->role === 'staff';
    }

    public function unreadNotificationsCount(): int
    {
        return $this->appNotifications()->whereNull('read_at')->count();
    }
}
