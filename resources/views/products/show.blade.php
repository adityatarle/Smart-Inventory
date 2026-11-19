@extends('layouts.app')

@section('title', $product->name)
@section('heading', $product->name)
@section('subheading', $product->sku.' · '.($product->category?->name ?? 'Uncategorized'))
@section('breadcrumb', 'SmartStock / Products / Details')

@section('content')
@php
    $canManage = auth()->user()->hasPermission('products.manage')
        || auth()->user()->hasPermission('products.update')
        || auth()->user()->canManageInventory();
@endphp

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex flex-wrap items-center gap-2">
        <span class="badge {{ $product->is_active ? 'bg-teal-400/15 text-teal-300' : 'bg-rose-400/15 text-rose-300' }}">
            {{ $product->is_active ? 'Active' : 'Inactive' }}
        </span>
        @if(($product->total_qty ?? 0) <= $product->reorder_level)
            <span class="badge bg-amber-400/15 text-amber-300">Low stock</span>
        @endif
    </div>
    <div class="flex flex-col gap-2 sm:flex-row">
        @if($canManage)
            <a href="{{ route('products.edit', $product) }}" class="btn-primary w-full justify-center sm:w-auto">Edit</a>
            <x-confirm-delete
                :action="route('products.destroy', $product)"
                title="Delete product?"
                :message="count($blockers) ? implode(' ', $blockers) : 'Delete this product permanently?'"
                class="w-full sm:w-auto"
                buttonClass="btn-danger w-full justify-center"
            />
        @endif
        <a href="{{ route('products.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Back</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="ss-card space-y-4 p-5 lg:col-span-2">
        <div class="flex flex-col gap-4 sm:flex-row">
            @if($product->imageUrl())
                <img src="{{ $product->imageUrl() }}" alt="" class="h-40 w-full rounded-2xl object-cover sm:h-32 sm:w-32">
            @else
                <div class="flex h-40 w-full items-center justify-center rounded-2xl bg-ss-primary/10 text-ss-primary sm:h-32 sm:w-32">
                    <i data-lucide="package" class="h-10 w-10"></i>
                </div>
            @endif
            <div class="min-w-0 flex-1 space-y-2 text-sm">
                <div class="flex justify-between gap-3"><span class="text-ss-muted">SKU</span><span class="font-mono">{{ $product->sku }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-ss-muted">Product code</span><span>{{ $product->product_code ?: '—' }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-ss-muted">Barcode</span><span>{{ $product->barcode ?: '—' }}</span></div>
                <div class="flex justify-between gap-3"><span class="text-ss-muted">Unit</span><span>{{ $product->unit }}</span></div>
            </div>
        </div>
        <p class="text-sm text-ss-muted">{{ $product->description ?: 'No description.' }}</p>
    </div>

    <div class="ss-card space-y-3 p-5">
        <h2 class="font-semibold">Pricing & levels</h2>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Purchase price</span><span>${{ number_format($product->cost_price, 2) }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Selling price</span><span>${{ number_format($product->sell_price, 2) }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Min stock</span><span>{{ $product->reorder_level }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Max stock</span><span>{{ $product->max_stock_level ?? '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Total stock</span><span class="font-semibold text-teal-300">{{ $product->total_qty ?? 0 }}</span></div>
    </div>
</div>

<div class="mt-6 ss-card p-5">
    <h2 class="mb-4 font-semibold">Stock by warehouse</h2>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @forelse($product->stocks as $stock)
            <div class="rounded-xl border border-white/10 bg-ss-card-2 px-3 py-3">
                <div class="text-sm font-medium">{{ $stock->warehouse?->name }}</div>
                <div class="mt-1 text-xs text-ss-muted">Reserved {{ $stock->reserved_quantity }}</div>
                <div class="mt-2 text-lg font-semibold text-teal-300">{{ $stock->quantity }} {{ $product->unit }}</div>
            </div>
        @empty
            <p class="text-sm text-ss-muted">No warehouse stock records yet.</p>
        @endforelse
    </div>
</div>
@endsection
