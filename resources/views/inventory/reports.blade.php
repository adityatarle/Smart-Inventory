@extends('layouts.app')
@section('title', 'Stock Reports')
@section('heading', 'Stock Reports')
@section('subheading', 'Inventory analytics hub')
@section('breadcrumb', 'SmartStock / Reports')

@section('content')
<div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Inventory value</p><p class="mt-1 text-xl font-semibold">${{ number_format($inventoryValue, 2) }}</p></div>
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Units on hand</p><p class="mt-1 text-xl font-semibold">{{ number_format($totalUnits) }}</p></div>
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Movements this month</p><p class="mt-1 text-xl font-semibold">{{ number_format($movementsMonth) }}</p></div>
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Open alerts</p><p class="mt-1 text-xl font-semibold text-amber-300">{{ $openAlerts }}</p></div>
</div>

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    <a href="{{ route('inventory.valuation') }}" class="ss-card block p-5 transition hover:border-ss-primary/40">
        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-ss-primary/15 text-ss-primary"><i data-lucide="calculator" class="h-5 w-5"></i></div>
        <h2 class="font-semibold">Stock valuation</h2>
        <p class="mt-1 text-sm text-ss-muted">Opening / received / issued / closing with weighted average cost.</p>
    </a>
    <a href="{{ route('inventory.low-stock') }}" class="ss-card block p-5 transition hover:border-ss-primary/40">
        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-amber-400/15 text-amber-300"><i data-lucide="triangle-alert" class="h-5 w-5"></i></div>
        <h2 class="font-semibold">Low stock report</h2>
        <p class="mt-1 text-sm text-ss-muted">{{ $lowStock }} low · {{ $outOfStock }} out of stock</p>
    </a>
    <a href="{{ route('inventory.alerts') }}" class="ss-card block p-5 transition hover:border-ss-primary/40">
        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-rose-400/15 text-rose-300"><i data-lucide="bell-ring" class="h-5 w-5"></i></div>
        <h2 class="font-semibold">Alert history</h2>
        <p class="mt-1 text-sm text-ss-muted">Open and resolved stock warning history.</p>
    </a>
    <a href="{{ route('stock-movements.index') }}" class="ss-card block p-5 transition hover:border-ss-primary/40">
        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-sky-400/15 text-sky-300"><i data-lucide="scroll-text" class="h-5 w-5"></i></div>
        <h2 class="font-semibold">Stock ledger</h2>
        <p class="mt-1 text-sm text-ss-muted">Immutable movement history with CSV export.</p>
    </a>
    <a href="{{ route('inventory.index') }}" class="ss-card block p-5 transition hover:border-ss-primary/40">
        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-teal-400/15 text-teal-300"><i data-lucide="boxes" class="h-5 w-5"></i></div>
        <h2 class="font-semibold">Inventory overview</h2>
        <p class="mt-1 text-sm text-ss-muted">Live balances, KPIs, and recent activity.</p>
    </a>
    <a href="{{ route('stock-transfers.index') }}" class="ss-card block p-5 transition hover:border-ss-primary/40">
        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-violet-400/15 text-violet-300"><i data-lucide="git-compare-arrows" class="h-5 w-5"></i></div>
        <h2 class="font-semibold">Transfers</h2>
        <p class="mt-1 text-sm text-ss-muted">In-transit and inter-warehouse movement status.</p>
    </a>
</div>
@endsection
