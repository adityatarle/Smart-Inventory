@php
    $u = auth()->user();

    $make = function (string $label, string $routeName, string $icon, ?string $permission, array $activePatterns) use ($u) {
        if ($permission && ! $u->hasPermission($permission)) {
            return null;
        }

        return [
            'label' => $label,
            'href' => Route::has($routeName) ? route($routeName) : '#',
            'icon' => $icon,
            'active' => request()->routeIs(...$activePatterns),
        ];
    };

    $links = collect([
        $make('Dashboard', 'dashboard', 'layout-dashboard', 'dashboard.view', ['dashboard']),
        $make('Inventory', 'inventory.index', 'boxes', 'inventory.view', ['inventory.index', 'stock.index', 'stock.live', 'stock.adjust*']),
        $make('Stock In', 'stock-receipts.index', 'arrow-down-to-line', 'inventory.view', ['stock-receipts.*']),
        $make('Stock Out', 'stock-issues.index', 'arrow-up-from-line', 'inventory.view', ['stock-issues.*']),
        $make('Adjustments', 'stock-adjustments.index', 'sliders-horizontal', 'inventory.view', ['stock-adjustments.*', 'stock.adjust*']),
        $make('Transfers', 'stock-transfers.index', 'git-compare-arrows', 'inventory.view', ['stock-transfers.*']),
        $make('Valuation', 'inventory.valuation', 'calculator', 'inventory.view', ['inventory.valuation']),
        $make('Alerts', 'inventory.alerts', 'bell-ring', 'inventory.view', ['inventory.alerts*', 'inventory.low-stock']),
        $make('Stock Ledger', 'stock-movements.index', 'arrow-left-right', 'inventory.view', ['stock-movements.*']),
        $make('Products', 'products.index', 'package', 'products.view', ['products.*']),
        $make('Categories', 'categories.index', 'tags', 'categories.view', ['categories.*']),
        $make('Warehouses', 'warehouses.index', 'warehouse', 'warehouses.view', ['warehouses.*']),
        $make('Suppliers', 'suppliers.index', 'truck', 'suppliers.view', ['suppliers.*']),
        $make('Purchase Orders', 'purchase-orders.index', 'clipboard-list', 'purchase_orders.view', ['purchase-orders.*']),
        $make('Customers', 'customers.index', 'users', 'customers.view', ['customers.*']),
        $make('Sales Orders', 'sales-orders.index', 'shopping-cart', 'sales_orders.view', ['sales-orders.*']),
        $make('Payments', 'payments.index', 'credit-card', 'payments.view', ['payments.*']),
        $make('Reports', 'reports.index', 'bar-chart-3', 'reports.view', ['reports.*']),
        $make('Notifications', 'notifications.index', 'bell', 'notifications.view', ['notifications.*']),
        $make('Users & Roles', 'users.index', 'shield', 'users.view', ['users.*', 'roles.*']),
        $make('Settings', 'settings.index', 'settings', 'settings.view', ['settings.*', 'profile.*']),
    ])->filter()->values();
@endphp

@foreach($links as $link)
    <a
        href="{{ $link['href'] }}"
        class="ss-nav-item {{ $link['active'] ? 'ss-nav-item-active' : '' }}"
        :class="!sidebarOpen && isDesktop ? 'justify-center px-0' : ''"
        :title="(!sidebarOpen && isDesktop) ? '{{ $link['label'] }}' : ''"
        @click="mobileOpen = false"
    >
        <i data-lucide="{{ $link['icon'] }}" class="h-4 w-4 shrink-0"></i>
        <span class="truncate" x-show="sidebarOpen || !isDesktop" x-cloak>{{ $link['label'] }}</span>
    </a>
@endforeach
