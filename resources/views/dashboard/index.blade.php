@extends('layouts.app')

@section('title', 'Dashboard · Smart Inventory')
@section('heading', 'Operations Dashboard')
@section('subheading', 'Warehouses, stock health, and open orders')

@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <div class="card p-5">
        <div class="text-xs font-semibold uppercase tracking-wide text-muted">Warehouses</div>
        <div class="mt-2 text-3xl font-bold text-teal-300">{{ $stats['warehouses'] }}</div>
    </div>
    <div class="card p-5">
        <div class="text-xs font-semibold uppercase tracking-wide text-muted">Products</div>
        <div class="mt-2 text-3xl font-bold text-sky-300">{{ $stats['products'] }}</div>
    </div>
    <div class="card p-5">
        <div class="text-xs font-semibold uppercase tracking-wide text-muted">Categories</div>
        <div class="mt-2 text-3xl font-bold text-cyan-300">{{ $stats['categories'] }}</div>
    </div>
    <div class="card p-5">
        <div class="text-xs font-semibold uppercase tracking-wide text-muted">Purchase Orders</div>
        <div class="mt-2 text-3xl font-bold text-emerald-300">{{ $stats['purchase_orders'] }}</div>
    </div>
    <div class="card p-5">
        <div class="text-xs font-semibold uppercase tracking-wide text-muted">Sales Orders</div>
        <div class="mt-2 text-3xl font-bold text-blue-300">{{ $stats['sales_orders'] }}</div>
    </div>
    <div class="card p-5">
        <div class="text-xs font-semibold uppercase tracking-wide text-muted">Low Stock Alerts</div>
        <div class="mt-2 text-3xl font-bold text-amber-300">{{ $stats['low_stock'] }}</div>
    </div>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <div class="card p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold">Recent stock movements</h2>
            <a href="{{ route('stock.index') }}" class="text-sm text-teal-300 hover:text-teal-200">Live stock</a>
        </div>
        <div class="space-y-3">
            @forelse($recentMovements as $movement)
                <div class="flex items-center justify-between rounded-xl border border-line bg-panel-2/50 px-3 py-2.5">
                    <div>
                        <div class="text-sm font-medium">{{ $movement->product?->name }}</div>
                        <div class="text-xs text-muted">{{ $movement->warehouse?->name }} · {{ $movement->type }} · {{ $movement->created_at?->diffForHumans() }}</div>
                    </div>
                    <div class="text-sm font-semibold {{ $movement->quantity >= 0 ? 'text-teal-300' : 'text-rose-300' }}">
                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                    </div>
                </div>
            @empty
                <p class="text-sm text-muted">No movements yet.</p>
            @endforelse
        </div>
    </div>

    <div class="space-y-6">
        <div class="card p-5">
            <h2 class="mb-4 font-semibold">Open purchase orders</h2>
            <div class="space-y-3">
                @forelse($pendingPurchases as $order)
                    <a href="{{ route('purchase-orders.show', $order) }}" class="flex items-center justify-between rounded-xl border border-line bg-panel-2/50 px-3 py-2.5 hover:border-teal-400/30">
                        <div>
                            <div class="text-sm font-medium">{{ $order->order_number }}</div>
                            <div class="text-xs text-muted">{{ $order->supplier_name }} · {{ $order->warehouse?->name }}</div>
                        </div>
                        <span class="badge bg-sky-400/15 text-sky-300">{{ $order->status }}</span>
                    </a>
                @empty
                    <p class="text-sm text-muted">No open purchase orders.</p>
                @endforelse
            </div>
        </div>

        <div class="card p-5">
            <h2 class="mb-4 font-semibold">Open sales orders</h2>
            <div class="space-y-3">
                @forelse($pendingSales as $order)
                    <a href="{{ route('sales-orders.show', $order) }}" class="flex items-center justify-between rounded-xl border border-line bg-panel-2/50 px-3 py-2.5 hover:border-teal-400/30">
                        <div>
                            <div class="text-sm font-medium">{{ $order->order_number }}</div>
                            <div class="text-xs text-muted">{{ $order->customer_name }} · {{ $order->warehouse?->name }}</div>
                        </div>
                        <span class="badge bg-amber-400/15 text-amber-300">{{ $order->status }}</span>
                    </a>
                @empty
                    <p class="text-sm text-muted">No open sales orders.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
