@extends('layouts.app')
@section('title', $movement->document_number)
@section('heading', $movement->document_number)
@section('subheading', 'Immutable ledger entry · '.strtoupper($movement->type))
@section('breadcrumb', 'SmartStock / Inventory / Ledger / Details')

@section('content')
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:justify-between">
    <div class="flex flex-wrap gap-2">
        <span class="badge {{ $movement->direction === 'in' ? 'bg-teal-400/15 text-teal-300' : 'bg-rose-400/15 text-rose-300' }}">
            {{ strtoupper($movement->direction) }}
        </span>
        <span class="badge bg-ss-primary/15 text-ss-primary">{{ $movement->status }}</span>
    </div>
    <a href="{{ route('stock-movements.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Back to ledger</a>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="ss-card space-y-3 p-5">
        <h2 class="font-semibold">Transaction</h2>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Date</span><span>{{ $movement->transaction_date?->format('Y-m-d H:i') }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Type</span><span>{{ $movement->type }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Quantity in</span><span class="text-teal-300">{{ $movement->quantity_in }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Quantity out</span><span class="text-rose-300">{{ $movement->quantity_out }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Signed qty</span><span>{{ $movement->quantity }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Balance after</span><span class="font-semibold">{{ $movement->balance_after }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Unit cost</span><span>${{ number_format($movement->unit_cost, 4) }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Total cost</span><span>${{ number_format($movement->total_cost, 4) }}</span></div>
        <p class="pt-2 text-sm text-ss-muted">{{ $movement->notes ?: 'No remarks.' }}</p>
    </div>

    <div class="ss-card space-y-3 p-5">
        <h2 class="font-semibold">Context</h2>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Product</span><span>{{ $movement->product?->name }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">SKU</span><span class="font-mono">{{ $movement->product?->sku }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Warehouse</span><span>{{ $movement->warehouse?->name }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Created by</span><span>{{ $movement->user?->name ?: '—' }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Reference</span><span>{{ $movement->reference_type ? class_basename($movement->reference_type).' #'.$movement->reference_id : '—' }}</span></div>
        <p class="mt-4 rounded-xl border border-amber-400/20 bg-amber-400/10 px-3 py-2 text-xs text-amber-200">
            Ledger entries cannot be edited or deleted. Corrections must use controlled reversal postings.
        </p>
    </div>
</div>
@endsection
