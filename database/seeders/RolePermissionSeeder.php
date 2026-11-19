<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'dashboard' => ['view'],
            'products' => ['view', 'create', 'update', 'delete', 'manage'],
            'categories' => ['view', 'create', 'update', 'delete', 'manage'],
            'warehouses' => ['view', 'create', 'update', 'delete', 'manage'],
            'inventory' => ['view', 'adjust', 'transfer', 'receive', 'issue', 'manage'],
            'suppliers' => ['view', 'create', 'update', 'delete', 'manage'],
            'purchase_orders' => ['view', 'create', 'update', 'approve', 'receive', 'manage'],
            'customers' => ['view', 'create', 'update', 'delete', 'manage'],
            'sales_orders' => ['view', 'create', 'update', 'confirm', 'dispatch', 'manage'],
            'payments' => ['view', 'create', 'update', 'manage'],
            'reports' => ['view', 'export'],
            'notifications' => ['view', 'manage'],
            'users' => ['view', 'create', 'update', 'delete', 'manage'],
            'roles' => ['view', 'manage'],
            'settings' => ['view', 'manage'],
        ];

        $permissionIds = [];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $slug = "{$module}.{$action}";
                $permission = Permission::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => ucfirst(str_replace('_', ' ', $module)).' '.ucfirst($action),
                        'module' => $module,
                        'description' => "Allow {$action} on {$module}",
                    ]
                );
                $permissionIds[$slug] = $permission->id;
            }
        }

        $all = array_values($permissionIds);

        $matrix = [
            'super_admin' => [
                'name' => 'Super Admin',
                'description' => 'Full system access',
                'permissions' => $all,
            ],
            'admin' => [
                'name' => 'Admin',
                'description' => 'Administrative access',
                'permissions' => $all,
            ],
            'manager' => [
                'name' => 'Manager',
                'description' => 'Operations manager',
                'permissions' => $this->pick($permissionIds, [
                    'dashboard.view', 'products.manage', 'categories.manage', 'warehouses.manage',
                    'inventory.manage', 'suppliers.manage', 'purchase_orders.manage', 'customers.manage',
                    'sales_orders.manage', 'payments.view', 'reports.view', 'reports.export',
                    'notifications.view', 'settings.view',
                ]),
            ],
            'storekeeper' => [
                'name' => 'Storekeeper',
                'description' => 'Warehouse operations',
                'permissions' => $this->pick($permissionIds, [
                    'dashboard.view', 'products.view', 'categories.view', 'warehouses.view',
                    'inventory.view', 'inventory.adjust', 'inventory.transfer', 'inventory.receive', 'inventory.issue',
                    'purchase_orders.view', 'purchase_orders.receive',
                    'sales_orders.view', 'sales_orders.dispatch', 'notifications.view',
                ]),
            ],
            'purchase_manager' => [
                'name' => 'Purchase Manager',
                'description' => 'Procurement operations',
                'permissions' => $this->pick($permissionIds, [
                    'dashboard.view', 'products.view', 'suppliers.manage', 'purchase_orders.manage',
                    'inventory.view', 'inventory.receive', 'warehouses.view', 'payments.view', 'reports.view', 'notifications.view',
                ]),
            ],
            'sales_manager' => [
                'name' => 'Sales Manager',
                'description' => 'Sales operations',
                'permissions' => $this->pick($permissionIds, [
                    'dashboard.view', 'products.view', 'customers.manage', 'sales_orders.manage',
                    'inventory.view', 'inventory.issue', 'warehouses.view', 'payments.view', 'reports.view', 'notifications.view',
                ]),
            ],
            'viewer' => [
                'name' => 'Viewer',
                'description' => 'Read-only access',
                'permissions' => $this->pick($permissionIds, [
                    'dashboard.view', 'products.view', 'categories.view', 'warehouses.view',
                    'inventory.view', 'suppliers.view', 'purchase_orders.view', 'customers.view',
                    'sales_orders.view', 'payments.view', 'reports.view', 'notifications.view',
                ]),
            ],
        ];

        foreach ($matrix as $slug => $data) {
            $role = Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_active' => true,
                ]
            );
            $role->permissions()->sync($data['permissions']);
        }

        $users = [
            ['name' => 'Super Admin', 'email' => 'superadmin@smartstock.test', 'role' => 'super_admin'],
            ['name' => 'System Admin', 'email' => 'admin@smartstock.test', 'role' => 'admin'],
            ['name' => 'Operations Manager', 'email' => 'manager@smartstock.test', 'role' => 'manager'],
            ['name' => 'Store Keeper', 'email' => 'storekeeper@smartstock.test', 'role' => 'storekeeper'],
            ['name' => 'Purchase Manager', 'email' => 'purchase@smartstock.test', 'role' => 'purchase_manager'],
            ['name' => 'Sales Manager', 'email' => 'sales@smartstock.test', 'role' => 'sales_manager'],
            ['name' => 'Viewer User', 'email' => 'viewer@smartstock.test', 'role' => 'viewer'],
            // Legacy demo accounts mapped into new RBAC
            ['name' => 'System Admin', 'email' => 'admin@smart.test', 'role' => 'admin'],
            ['name' => 'Warehouse Manager', 'email' => 'manager@smart.test', 'role' => 'manager'],
            ['name' => 'Floor Staff', 'email' => 'staff@smart.test', 'role' => 'storekeeper'],
        ];

        foreach ($users as $data) {
            $legacyRole = match ($data['role']) {
                'super_admin', 'admin' => 'admin',
                'manager', 'purchase_manager', 'sales_manager' => 'manager',
                'storekeeper' => 'staff',
                'viewer' => 'customer',
                default => 'staff',
            };

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role' => $legacyRole,
                    'is_active' => true,
                ]
            );

            $role = Role::where('slug', $data['role'])->first();
            if ($role) {
                $user->roles()->sync([$role->id]);
            }
        }
    }

    private function pick(array $permissionIds, array $slugs): array
    {
        return collect($slugs)
            ->map(fn ($slug) => $permissionIds[$slug] ?? null)
            ->filter()
            ->values()
            ->all();
    }
}
