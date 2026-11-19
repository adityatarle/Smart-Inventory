@extends('layouts.app')
@section('title', $warehouse->name)
@section('heading', $warehouse->name)
@section('subheading', $warehouse->code)
@section('breadcrumb', 'SmartStock / Warehouses / Details')

@section('content')
@php
    $canManage = auth()->user()->hasPermission('warehouses.manage')
        || auth()->user()->hasPermission('warehouses.update')
        || auth()->user()->canManageInventory();
@endphp

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <span class="badge {{ $warehouse->is_active ? 'bg-teal-400/15 text-teal-300' : 'bg-rose-400/15 text-rose-300' }}">
        {{ $warehouse->is_active ? 'Active' : 'Inactive' }}
    </span>
    <div class="flex flex-col gap-2 sm:flex-row">
        @if($canManage)
            <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn-primary w-full justify-center sm:w-auto">Edit</a>
            <x-confirm-delete :action="route('warehouses.destroy', $warehouse)" title="Delete warehouse?" :message="count($blockers) ? implode(' ', $blockers) : 'Delete this warehouse?'" class="w-full sm:w-auto" buttonClass="btn-danger w-full justify-center" />
        @endif
        <a href="{{ route('warehouses.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Back</a>
    </div>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <x-kpi-card label="Product Types" :value="number_format($warehouse->stocks_count)" icon="package" tone="primary" />
    <x-kpi-card label="Total Units" :value="number_format($warehouse->total_qty ?? 0)" icon="boxes" tone="success" />
    <x-kpi-card label="Contact" :value="$warehouse->contact_person ?: '—'" icon="user" tone="secondary" />
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="ss-card space-y-3 p-5 lg:col-span-1">
        <h2 class="font-semibold">Warehouse information</h2>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Code</span><span class="font-mono">{{ $warehouse->code }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Phone</span><span>{{ $warehouse->phone ?: '—' }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Email</span><span>{{ $warehouse->email ?: '—' }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">City</span><span>{{ $warehouse->city ?: '—' }}</span></div>
        <p class="pt-2 text-sm text-ss-muted">{{ $warehouse->address ?: 'No address on file.' }}</p>
    </div>

    <div class="ss-card overflow-hidden lg:col-span-2">
        <div class="border-b border-white/10 px-5 py-4 font-semibold">Current stock summary</div>
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Reserved</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks as $stock)
                        <tr>
                            <td class="font-mono text-xs">{{ $stock->product?->sku }}</td>
                            <td>{{ $stock->product?->name }}</td>
                            <td>{{ $stock->quantity }}</td>
                            <td>{{ $stock->reserved_quantity }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-ss-muted">No stock in this warehouse.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $stocks->links() }}</div>
    </div>
</div>

<div class="mt-6 ss-card p-5">
    <h2 class="mb-4 font-semibold">Recent stock movements</h2>
    <div class="space-y-3">
        @forelse($movements as $movement)
            <div class="flex items-center justify-between rounded-xl border border-white/10 bg-ss-card-2 px-3 py-2">
                <div>
                    <div class="text-sm font-medium">{{ $movement->product?->name }}</div>
                    <div class="text-xs text-ss-muted">{{ strtoupper($movement->type) }} · {{ $movement->created_at?->diffForHumans() }}</div>
                </div>
                <div class="font-semibold {{ $movement->quantity >= 0 ? 'text-teal-300' : 'text-rose-300' }}">
                    {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                </div>
            </div>
        @empty
            <p class="text-sm text-ss-muted">No stock movements recorded yet.</p>
        @endforelse
    </div>
</div>
@endsection
