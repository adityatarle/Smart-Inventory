@extends('layouts.app')
@section('title', $receipt->document_number)
@section('heading', $receipt->document_number)
@section('subheading', 'Stock In · '.ucfirst($receipt->status))
@section('breadcrumb', 'SmartStock / Inventory / Stock In / Details')

@section('content')
@php $canManage = auth()->user()->hasPermission('inventory.receive') || auth()->user()->hasPermission('inventory.manage') || auth()->user()->canManageInventory(); @endphp

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
    <span class="badge {{ $receipt->status === 'submitted' ? 'bg-teal-400/15 text-teal-300' : ($receipt->status === 'cancelled' ? 'bg-rose-400/15 text-rose-300' : 'bg-amber-400/15 text-amber-300') }}">{{ ucfirst($receipt->status) }}</span>
    <div class="flex flex-col gap-2 sm:flex-row">
        @if($canManage && $receipt->isDraft())
            <a href="{{ route('stock-receipts.edit', $receipt) }}" class="btn-secondary w-full justify-center sm:w-auto">Edit</a>
            <form method="POST" action="{{ route('stock-receipts.submit', $receipt) }}">@csrf<button class="btn-primary w-full justify-center sm:w-auto">Submit</button></form>
            <x-confirm-delete :action="route('stock-receipts.destroy', $receipt)" title="Delete draft?" message="Delete this draft receipt?" class="w-full sm:w-auto" buttonClass="btn-danger w-full justify-center" />
        @endif
        @if($canManage && in_array($receipt->status, ['draft', 'submitted'], true))
            <form method="POST" action="{{ route('stock-receipts.cancel', $receipt) }}" onsubmit="return confirm('Cancel this receipt? Submitted receipts will reverse ledger entries.')">@csrf<button class="btn-secondary w-full justify-center sm:w-auto">Cancel document</button></form>
        @endif
        <a href="{{ route('stock-receipts.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Back</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="ss-card space-y-3 p-5">
        <h2 class="font-semibold">Header</h2>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Warehouse</span><span>{{ $receipt->warehouse?->name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Date</span><span>{{ $receipt->transaction_date?->format('Y-m-d') }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Reference</span><span>{{ $receipt->reference_number ?: '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Created by</span><span>{{ $receipt->creator?->name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Submitted by</span><span>{{ $receipt->submitter?->name ?: '—' }}</span></div>
        <p class="text-sm text-ss-muted">{{ $receipt->remarks ?: 'No remarks.' }}</p>
    </div>
    <div class="ss-card overflow-hidden lg:col-span-2">
        <div class="border-b border-white/10 px-5 py-4 font-semibold">Lines</div>
        <div class="table-wrap border-0">
            <table class="data">
                <thead><tr><th>SKU</th><th>Product</th><th>Qty</th><th>Unit cost</th><th>Line total</th></tr></thead>
                <tbody>
                    @foreach($receipt->items as $item)
                        <tr>
                            <td class="font-mono text-xs">{{ $item->product?->sku }}</td>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>${{ number_format($item->unit_cost, 4) }}</td>
                            <td>${{ number_format($item->quantity * $item->unit_cost, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
