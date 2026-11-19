@extends('layouts.app')

@section('title', $product->name)
@section('heading', $product->name)
@section('subheading', $product->sku.' · '.($product->category?->name ?? 'Uncategorized'))

@section('content')
<div class="mb-5 flex gap-3">
    @if(auth()->user()->canManageInventory())
        <a href="{{ route('products.edit', $product) }}" class="btn-primary">Edit</a>
    @endif
    <a href="{{ route('products.index') }}" class="btn-secondary">Back</a>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="card space-y-3 p-6">
        <div class="flex justify-between text-sm"><span class="text-muted">Cost</span><span>${{ number_format($product->cost_price, 2) }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Sell</span><span>${{ number_format($product->sell_price, 2) }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Unit</span><span>{{ $product->unit }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Reorder level</span><span>{{ $product->reorder_level }}</span></div>
        <p class="pt-2 text-sm text-muted">{{ $product->description ?: 'No description.' }}</p>
    </div>

    <div class="card p-6">
        <h2 class="mb-4 font-semibold">Stock by warehouse</h2>
        <div class="space-y-3">
            @forelse($product->stocks as $stock)
                <div class="flex items-center justify-between rounded-xl border border-line bg-panel-2/50 px-3 py-2.5">
                    <div>
                        <div class="text-sm font-medium">{{ $stock->warehouse?->name }}</div>
                        <div class="text-xs text-muted">Reserved {{ $stock->reserved_quantity }}</div>
                    </div>
                    <div class="text-lg font-semibold text-teal-300">{{ $stock->quantity }}</div>
                </div>
            @empty
                <p class="text-sm text-muted">No stock records yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
