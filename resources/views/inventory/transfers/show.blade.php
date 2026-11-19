@extends('layouts.app')
@section('title', $transfer->document_number)
@section('heading', $transfer->document_number)
@section('subheading', 'Warehouse Transfer · '.str_replace('_', ' ', ucfirst($transfer->status)))
@section('breadcrumb', 'SmartStock / Inventory / Transfers / Details')

@section('content')
@php $canManage = auth()->user()->hasPermission('inventory.transfer') || auth()->user()->hasPermission('inventory.manage') || auth()->user()->canManageInventory(); @endphp

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
    <span class="badge {{ $transfer->status === 'completed' ? 'bg-teal-400/15 text-teal-300' : ($transfer->status === 'cancelled' ? 'bg-rose-400/15 text-rose-300' : (in_array($transfer->status, ['in_transit','partially_received']) ? 'bg-sky-400/15 text-sky-300' : 'bg-amber-400/15 text-amber-300')) }}">
        {{ str_replace('_', ' ', ucfirst($transfer->status)) }}
    </span>
    <div class="flex flex-col gap-2 sm:flex-row">
        @if($canManage && $transfer->isDraft())
            <a href="{{ route('stock-transfers.edit', $transfer) }}" class="btn-secondary w-full justify-center sm:w-auto">Edit</a>
            <form method="POST" action="{{ route('stock-transfers.submit', $transfer) }}">@csrf<button class="btn-primary w-full justify-center sm:w-auto">Dispatch</button></form>
            <x-confirm-delete :action="route('stock-transfers.destroy', $transfer)" title="Delete draft?" message="Delete this draft transfer?" class="w-full sm:w-auto" buttonClass="btn-danger w-full justify-center" />
        @endif
        @if($canManage && $transfer->isOpenForReceive())
            <a href="{{ route('stock-transfers.receive', $transfer) }}" class="btn-primary w-full justify-center sm:w-auto">Receive stock</a>
        @endif
        @if($canManage && in_array($transfer->status, ['draft', 'in_transit', 'partially_received'], true))
            <form method="POST" action="{{ route('stock-transfers.cancel', $transfer) }}" onsubmit="return confirm('Cancel this transfer? Remaining in-transit stock returns to source.')">@csrf<button class="btn-secondary w-full justify-center sm:w-auto">Cancel</button></form>
        @endif
        <a href="{{ route('stock-transfers.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Back</a>
    </div>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Dispatched</p><p class="mt-1 text-xl font-semibold">{{ $transfer->totalDispatched() }}</p></div>
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Received</p><p class="mt-1 text-xl font-semibold">{{ $transfer->totalReceived() }}</p></div>
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">In transit</p><p class="mt-1 text-xl font-semibold text-sky-300">{{ $transfer->totalInTransit() }}</p></div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="ss-card space-y-3 p-5">
        <h2 class="font-semibold">Header</h2>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">From</span><span>{{ $transfer->fromWarehouse?->name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">To</span><span>{{ $transfer->toWarehouse?->name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Date</span><span>{{ $transfer->transaction_date?->format('Y-m-d') }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Reference</span><span>{{ $transfer->reference_number ?: '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Created by</span><span>{{ $transfer->creator?->name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Dispatched by</span><span>{{ $transfer->submitter?->name ?: '—' }}</span></div>
        <p class="text-sm text-ss-muted">{{ $transfer->remarks ?: 'No remarks.' }}</p>
    </div>
    <div class="ss-card overflow-hidden lg:col-span-2">
        <div class="border-b border-white/10 px-5 py-4 font-semibold">Lines</div>
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Dispatched</th>
                        <th>Received</th>
                        <th>In transit</th>
                        <th>Unit cost</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfer->items as $item)
                        <tr>
                            <td class="font-mono text-xs">{{ $item->product?->sku }}</td>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->quantity_received }}</td>
                            <td class="text-sky-300">{{ $item->quantityInTransit() }}</td>
                            <td>${{ number_format($item->unit_cost, 4) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
