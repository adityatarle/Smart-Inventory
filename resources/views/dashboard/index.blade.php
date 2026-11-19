@extends('layouts.app')
@section('title', 'Dashboard · SmartStock')
@section('heading', 'Operations Dashboard')
@section('subheading', 'Live inventory, logistics, and order performance')
@section('breadcrumb', 'SmartStock / Dashboard')

@section('content')
{{-- KPI cards --}}
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach([
        ['Total Products', number_format($kpis['products']), 'package', 'text-ss-primary', 'bg-ss-primary/15'],
        ['Inventory Value', '$'.number_format($kpis['inventory_value'], 2), 'wallet', 'text-ss-secondary', 'bg-ss-secondary/15'],
        ['Total Orders', number_format($kpis['orders']), 'shopping-bag', 'text-sky-300', 'bg-sky-400/15'],
        ['Low Stock Products', number_format($kpis['low_stock']), 'triangle-alert', 'text-ss-warning', 'bg-ss-warning/15'],
    ] as [$label, $value, $icon, $text, $bg])
        <div class="ss-card p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-ss-muted">{{ $label }}</div>
                    <div class="mt-2 text-2xl font-bold {{ $text }}">{{ $value }}</div>
                </div>
                <div class="rounded-xl {{ $bg }} p-2.5 {{ $text }}">
                    <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Analytics row --}}
<div class="mt-6 grid gap-4 sm:gap-6 lg:grid-cols-2">
    <div class="ss-card min-w-0 p-4 sm:p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold">Inventory Overview</h2>
            <span class="text-xs text-ss-muted">Stock by category</span>
        </div>
        <div class="ss-chart-box">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
    <div class="ss-card min-w-0 p-4 sm:p-5">
        <div class="mb-4">
            <h2 class="font-semibold">Warehouse Overview</h2>
            <p class="text-xs text-ss-muted">{{ $totalWarehouses }} warehouses · {{ number_format($totalUnits) }} units</p>
        </div>
        <div class="ss-chart-box">
            <canvas id="warehouseChart"></canvas>
        </div>
    </div>
</div>

{{-- Wide analytics --}}
<div class="mt-6 grid gap-4 sm:gap-6 lg:grid-cols-2">
    <div class="ss-card min-w-0 p-4 sm:p-5">
        <h2 class="mb-4 font-semibold">Purchase vs Sales Trends</h2>
        <div class="ss-chart-box">
            <canvas id="ordersChart"></canvas>
        </div>
    </div>
    <div class="ss-card min-w-0 p-4 sm:p-5">
        <h2 class="mb-4 font-semibold">Stock In / Stock Out</h2>
        <div class="ss-chart-box">
            <canvas id="stockFlowChart"></canvas>
        </div>
    </div>
</div>

{{-- Widgets --}}
<div class="mt-6 grid gap-4 sm:grid-cols-2 sm:gap-6 xl:grid-cols-4">
    <div class="ss-card p-5 xl:col-span-1">
        <h2 class="mb-4 font-semibold">Order Processing</h2>
        <div class="space-y-3">
            @foreach($orderStatus as $status => $count)
                <div class="flex items-center justify-between rounded-xl border border-white/10 bg-ss-card-2 px-3 py-2 text-sm">
                    <span class="capitalize text-ss-muted">{{ $status }}</span>
                    <span class="font-semibold">{{ $count }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="ss-card p-5 xl:col-span-1">
        <h2 class="mb-4 font-semibold">Recent Orders</h2>
        <div class="space-y-3">
            @forelse($recentOrders as $order)
                <a href="{{ route('sales-orders.show', $order) }}" class="block rounded-xl border border-white/10 bg-ss-card-2 px-3 py-2 hover:border-ss-primary/30">
                    <div class="text-sm font-medium">{{ $order->order_number }}</div>
                    <div class="text-xs text-ss-muted">{{ $order->customer?->name ?: $order->customer_name }} · {{ $order->status }}</div>
                </a>
            @empty
                <p class="text-sm text-ss-muted">No sales orders yet.</p>
            @endforelse
        </div>
    </div>

    <div class="ss-card p-5 xl:col-span-1">
        <h2 class="mb-4 font-semibold">Low Stock Alerts</h2>
        <div class="space-y-3">
            @forelse($lowStockItems as $stock)
                <div class="rounded-xl border border-ss-warning/20 bg-ss-warning/5 px-3 py-2">
                    <div class="text-sm font-medium">{{ $stock->product?->name }}</div>
                    <div class="text-xs text-ss-muted">{{ $stock->warehouse?->name }} · Qty {{ $stock->quantity }}</div>
                </div>
            @empty
                <p class="text-sm text-ss-muted">No low stock alerts.</p>
            @endforelse
        </div>
    </div>

    <div class="ss-card p-5 xl:col-span-1">
        <h2 class="mb-4 font-semibold">Recent Activities</h2>
        <div class="space-y-3">
            @forelse($recentActivities as $activity)
                <div class="rounded-xl border border-white/10 bg-ss-card-2 px-3 py-2">
                    <div class="text-sm">{{ $activity->description }}</div>
                    <div class="text-xs text-ss-muted">{{ $activity->user?->name ?: 'System' }} · {{ $activity->created_at?->diffForHumans() }}</div>
                </div>
            @empty
                <p class="text-sm text-ss-muted">No activity logged yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const purple = '#A855F7';
    const gold = '#D6A85B';
    const green = '#22C55E';
    const red = '#EF4444';
    const muted = '#A1A1AA';

    Chart.defaults.color = muted;
    Chart.defaults.borderColor = 'rgba(255,255,255,0.08)';
    Chart.defaults.responsive = true;
    Chart.defaults.maintainAspectRatio = false;

    const baseOpts = {
        responsive: true,
        maintainAspectRatio: false,
    };

    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: @json($categoryStock->pluck('name')),
            datasets: [{
                label: 'Units',
                data: @json($categoryStock->pluck('qty')),
                backgroundColor: purple,
                borderRadius: 8,
            }]
        },
        options: { ...baseOpts, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    new Chart(document.getElementById('warehouseChart'), {
        type: 'doughnut',
        data: {
            labels: @json($warehouseStock->pluck('name')),
            datasets: [{
                data: @json($warehouseStock->pluck('qty')),
                backgroundColor: [purple, gold, '#38BDF8', '#22C55E', '#F472B6'],
                borderWidth: 0,
            }]
        },
        options: { ...baseOpts, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
    });

    const months = @json($months->map(fn ($m) => \Carbon\Carbon::createFromFormat('Y-m', $m)->format('M')));

    new Chart(document.getElementById('ordersChart'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                { label: 'Sales', data: @json($monthlySales), borderColor: purple, tension: 0.35 },
                { label: 'Purchases', data: @json($monthlyPurchases), borderColor: gold, tension: 0.35 },
            ]
        },
        options: { ...baseOpts, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });

    new Chart(document.getElementById('stockFlowChart'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                { label: 'Stock In', data: @json($stockIn), backgroundColor: green, borderRadius: 6 },
                { label: 'Stock Out', data: @json($stockOut), backgroundColor: red, borderRadius: 6 },
            ]
        },
        options: { ...baseOpts, scales: { y: { beginAtZero: true } } }
    });
});
</script>
@endpush
