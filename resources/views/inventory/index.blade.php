@extends('layouts.app')
@section('title', 'Inventory Overview')
@section('heading', 'Inventory Overview')
@section('subheading', 'Balances, value, and recent ledger activity')
@section('breadcrumb', 'SmartStock / Inventory')

@section('content')
<div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-kpi-card label="Product Types" :value="number_format($stats['product_types'])" icon="package" tone="primary" />
    <x-kpi-card label="Total Quantity" :value="number_format($stats['total_qty'])" icon="boxes" tone="info" />
    <x-kpi-card label="Inventory Value" :value="'$'.number_format($stats['inventory_value'], 2)" icon="wallet" tone="secondary" />
    <x-kpi-card label="Low Stock" :value="number_format($stats['low_stock'])" icon="triangle-alert" tone="warning" />
</div>

<div class="mb-5 grid gap-4 sm:grid-cols-3">
    <x-kpi-card label="Out of Stock" :value="number_format($stats['out_of_stock'])" icon="circle-off" tone="danger" />
    <x-kpi-card label="Received This Month" :value="number_format($stats['received_month'])" icon="arrow-down-to-line" tone="success" />
    <x-kpi-card label="Issued This Month" :value="number_format($stats['issued_month'])" icon="arrow-up-from-line" tone="warning" />
</div>

<div class="ss-page-toolbar">
    <form method="GET" class="w-full">
        <div class="grid w-full gap-2 sm:grid-cols-2 lg:grid-cols-5">
            <select name="warehouse_id" class="input">
                <option value="">All warehouses</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected($filters['warehouse_id'] == $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <select name="category_id" class="input">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected($filters['category_id'] == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="product_id" class="input">
                <option value="">All products</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected($filters['product_id'] == $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                @endforeach
            </select>
            <select name="stock_status" class="input">
                <option value="">All statuses</option>
                <option value="in_stock" @selected($filters['stock_status'] === 'in_stock')>In stock</option>
                <option value="low" @selected($filters['stock_status'] === 'low')>Low stock</option>
                <option value="out" @selected($filters['stock_status'] === 'out')>Out of stock</option>
            </select>
            <div class="flex gap-2">
                <button class="btn-secondary w-full justify-center">Filter</button>
                <a href="{{ route('inventory.index') }}" class="btn-secondary shrink-0 px-3">Clear</a>
            </div>
        </div>
    </form>
    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
        <a href="{{ route('stock-receipts.create') }}" class="btn-primary w-full justify-center sm:w-auto">Stock In</a>
        <a href="{{ route('stock-issues.create') }}" class="btn-secondary w-full justify-center sm:w-auto">Stock Out</a>
        <a href="{{ route('stock-movements.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Ledger</a>
    </div>
</div>

<div class="grid gap-6 xl:grid-cols-3">
    <div class="ss-card overflow-hidden xl:col-span-2">
        <div class="border-b border-white/10 px-5 py-4 font-semibold">Stock by product / warehouse</div>
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Warehouse</th>
                        <th>Qty</th>
                        <th>Avg cost</th>
                        <th>Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks as $stock)
                        @php
                            $low = $stock->quantity > 0 && $stock->quantity <= ($stock->product?->reorder_level ?? 0);
                            $out = $stock->quantity <= 0;
                        @endphp
                        <tr>
                            <td class="font-mono text-xs">{{ $stock->product?->sku }}</td>
                            <td>{{ $stock->product?->name }}</td>
                            <td>{{ $stock->warehouse?->name }}</td>
                            <td class="font-semibold">{{ $stock->quantity }} {{ $stock->product?->unit }}</td>
                            <td>${{ number_format($stock->average_cost, 4) }}</td>
                            <td>${{ number_format($stock->inventoryValue(), 2) }}</td>
                            <td>
                                @if($out)
                                    <span class="badge bg-rose-400/15 text-rose-300">Out</span>
                                @elseif($low)
                                    <span class="badge bg-amber-400/15 text-amber-300">Low</span>
                                @else
                                    <span class="badge bg-teal-400/15 text-teal-300">OK</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-ss-muted">No stock rows match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $stocks->links() }}</div>
    </div>

    <div class="ss-card p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold">Recent movements</h2>
            <a href="{{ route('stock-movements.index') }}" class="text-xs font-semibold text-ss-primary">View ledger</a>
        </div>
        <div class="space-y-3">
            @forelse($recentMovements as $movement)
                <a href="{{ route('stock-movements.show', $movement) }}" class="block rounded-xl border border-white/10 bg-ss-card-2 px-3 py-2 hover:border-ss-primary/30">
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium">{{ $movement->product?->name }}</div>
                            <div class="text-xs text-ss-muted">{{ $movement->document_number }} · {{ strtoupper($movement->type) }}</div>
                        </div>
                        <div class="shrink-0 font-semibold {{ $movement->quantity >= 0 ? 'text-teal-300' : 'text-rose-300' }}">
                            {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                        </div>
                    </div>
                </a>
            @empty
                <p class="text-sm text-ss-muted">No ledger activity yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
