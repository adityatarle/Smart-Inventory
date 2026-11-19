<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart Inventory')</title>
    @include('partials.assets')
</head>
<body>
    <div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
        <aside class="border-b border-line bg-panel/80 backdrop-blur lg:border-b-0 lg:border-r">
            <div class="flex items-center justify-between px-5 py-5">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-teal-400 to-sky-500 text-sm font-bold text-slate-950">SI</span>
                    <div>
                        <div class="text-sm font-bold tracking-wide">Smart Inventory</div>
                        <div class="text-xs text-muted">{{ ucfirst(auth()->user()->role) }} workspace</div>
                    </div>
                </a>
            </div>

            <nav class="space-y-1 px-3 pb-6">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">Dashboard</a>

                <div class="px-3 pt-3 text-[11px] font-semibold uppercase tracking-wider text-muted/70">Sales</div>
                <a href="{{ route('sales.hub') }}" class="nav-link {{ request()->routeIs('sales.hub') ? 'nav-link-active' : '' }}">Sales Hub</a>
                <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'nav-link-active' : '' }}">Customers</a>
                <a href="{{ route('quotations.index') }}" class="nav-link {{ request()->routeIs('quotations.*') ? 'nav-link-active' : '' }}">Quotations</a>
                <a href="{{ route('sales-orders.index') }}" class="nav-link {{ request()->routeIs('sales-orders.*') ? 'nav-link-active' : '' }}">Sales Orders</a>
                <a href="{{ route('sales-invoices.index') }}" class="nav-link {{ request()->routeIs('sales-invoices.*') ? 'nav-link-active' : '' }}">Sales Invoices</a>
                <a href="{{ route('blanket-orders.index') }}" class="nav-link {{ request()->routeIs('blanket-orders.*') ? 'nav-link-active' : '' }}">Blanket Orders</a>
                @if(auth()->user()->canOperateOrders())
                    <a href="{{ route('sales-partners.index') }}" class="nav-link {{ request()->routeIs('sales-partners.*') ? 'nav-link-active' : '' }}">Sales Partners</a>
                    <a href="{{ route('sales-persons.index') }}" class="nav-link {{ request()->routeIs('sales-persons.*') ? 'nav-link-active' : '' }}">Sales Persons</a>
                @endif

                @if(auth()->user()->canOperateOrders())
                    <div class="px-3 pt-3 text-[11px] font-semibold uppercase tracking-wider text-muted/70">Inventory</div>
                    <a href="{{ route('stock.index') }}" class="nav-link {{ request()->routeIs('stock.*') ? 'nav-link-active' : '' }}">Live Stock</a>
                    <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'nav-link-active' : '' }}">Products</a>
                @endif

                @if(auth()->user()->canManageInventory())
                    <a href="{{ route('warehouses.index') }}" class="nav-link {{ request()->routeIs('warehouses.*') ? 'nav-link-active' : '' }}">Warehouses</a>
                    <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'nav-link-active' : '' }}">Categories</a>
                    <a href="{{ route('purchase-orders.index') }}" class="nav-link {{ request()->routeIs('purchase-orders.*') ? 'nav-link-active' : '' }}">Purchase Orders</a>
                @endif

                @if(auth()->user()->isAdmin())
                    <div class="px-3 pt-3 text-[11px] font-semibold uppercase tracking-wider text-muted/70">Admin</div>
                    <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'nav-link-active' : '' }}">Users & Roles</a>
                @endif
            </nav>
        </aside>

        <div class="flex min-w-0 flex-col">
            <header class="flex items-center justify-between gap-4 border-b border-line bg-panel/40 px-5 py-4 backdrop-blur">
                <div>
                    <h1 class="text-lg font-semibold">@yield('heading', 'Dashboard')</h1>
                    <p class="text-sm text-muted">@yield('subheading', 'Inventory operations at a glance')</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block">
                        <div class="text-sm font-medium">{{ auth()->user()->name }}</div>
                        <div class="text-xs capitalize text-muted">{{ auth()->user()->role }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn-secondary">Logout</button>
                    </form>
                </div>
            </header>

            <main class="flex-1 px-5 py-6">
                @if(session('success'))
                    <div class="mb-4 rounded-xl border border-teal-400/30 bg-teal-400/10 px-4 py-3 text-sm text-teal-200">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-200">
                        <ul class="list-disc space-y-1 pl-4">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
