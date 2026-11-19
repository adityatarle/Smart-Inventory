@extends('layouts.app')
@section('title', 'Stock Alerts')
@section('heading', 'Stock Alerts')
@section('subheading', 'Low, out-of-stock, reorder, and excess warnings')
@section('breadcrumb', 'SmartStock / Inventory / Alerts')

@section('content')
@php $canManage = auth()->user()->hasPermission('inventory.manage') || auth()->user()->canManageInventory(); @endphp

<div class="mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Open alerts</p><p class="mt-1 text-xl font-semibold">{{ $counts['total'] }}</p></div>
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Low stock</p><p class="mt-1 text-xl font-semibold text-amber-300">{{ $counts['low_stock'] }}</p></div>
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Out of stock</p><p class="mt-1 text-xl font-semibold text-rose-300">{{ $counts['out_of_stock'] }}</p></div>
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Excess</p><p class="mt-1 text-xl font-semibold text-sky-300">{{ $counts['excess'] }}</p></div>
</div>

<div class="ss-page-toolbar">
    <form method="GET" class="w-full">
        <div class="grid w-full gap-2 sm:grid-cols-4">
            <select name="status" class="input">
                <option value="open" @selected(request('status', 'open') === 'open')>Open</option>
                <option value="resolved" @selected(request('status') === 'resolved')>Resolved</option>
                <option value="all" @selected(request('status') === 'all')>All history</option>
            </select>
            <select name="type" class="input">
                <option value="">All types</option>
                @foreach(['low_stock','out_of_stock','reorder','excess'] as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                @endforeach
            </select>
            <select name="warehouse_id" class="input">
                <option value="">All warehouses</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected(request('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <button class="btn-secondary">Filter</button>
        </div>
    </form>
    @if($canManage)
        <form method="POST" action="{{ route('inventory.alerts.scan') }}">@csrf<button class="btn-primary w-full justify-center sm:w-auto">Rescan stock</button></form>
    @endif
</div>

@if($alerts->isEmpty())
    <x-empty-state title="No alerts" description="Stock levels look healthy for the current filters." icon="bell-ring" />
@else
    <div class="card overflow-hidden">
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Type</th>
                        <th>Warehouse</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Threshold</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($alerts as $alert)
                        <tr>
                            <td class="text-xs text-ss-muted">{{ $alert->created_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                <span class="badge {{ $alert->severity === 'critical' ? 'bg-rose-400/15 text-rose-300' : ($alert->severity === 'warning' ? 'bg-amber-400/15 text-amber-300' : 'bg-sky-400/15 text-sky-300') }}">
                                    {{ str_replace('_', ' ', ucfirst($alert->type)) }}
                                </span>
                            </td>
                            <td>{{ $alert->warehouse?->name }}</td>
                            <td>
                                <div>{{ $alert->product?->name }}</div>
                                <div class="font-mono text-xs text-ss-muted">{{ $alert->product?->sku }}</div>
                            </td>
                            <td>{{ $alert->quantity }}</td>
                            <td>{{ $alert->threshold ?? '—' }}</td>
                            <td>{{ $alert->resolved_at ? 'Resolved' : 'Open' }}</td>
                            <td class="text-right">
                                @if($canManage && $alert->isOpen())
                                    <form method="POST" action="{{ route('inventory.alerts.resolve', $alert) }}">@csrf<button class="text-sky-300">Resolve</button></form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
<div class="mt-4">{{ $alerts->links() }}</div>
@endsection
