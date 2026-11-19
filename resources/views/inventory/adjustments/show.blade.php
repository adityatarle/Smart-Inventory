@extends('layouts.app')
@section('title', $adjustment->document_number)
@section('heading', $adjustment->document_number)
@section('subheading', 'Stock Adjustment · '.ucfirst($adjustment->status))
@section('breadcrumb', 'SmartStock / Inventory / Adjustments / Details')

@section('content')
@php $canManage = auth()->user()->hasPermission('inventory.adjust') || auth()->user()->hasPermission('inventory.manage') || auth()->user()->canManageInventory(); @endphp

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
    <span class="badge {{ $adjustment->status === 'submitted' ? 'bg-teal-400/15 text-teal-300' : ($adjustment->status === 'cancelled' ? 'bg-rose-400/15 text-rose-300' : 'bg-amber-400/15 text-amber-300') }}">{{ ucfirst($adjustment->status) }}</span>
    <div class="flex flex-col gap-2 sm:flex-row">
        @if($canManage && $adjustment->isDraft())
            <a href="{{ route('stock-adjustments.edit', $adjustment) }}" class="btn-secondary w-full justify-center sm:w-auto">Edit</a>
            <form method="POST" action="{{ route('stock-adjustments.submit', $adjustment) }}">@csrf<button class="btn-primary w-full justify-center sm:w-auto">Submit</button></form>
            <x-confirm-delete :action="route('stock-adjustments.destroy', $adjustment)" title="Delete draft?" message="Delete this draft adjustment?" class="w-full sm:w-auto" buttonClass="btn-danger w-full justify-center" />
        @endif
        @if($canManage && in_array($adjustment->status, ['draft', 'submitted'], true))
            <form method="POST" action="{{ route('stock-adjustments.cancel', $adjustment) }}" onsubmit="return confirm('Cancel this adjustment? Submitted documents reverse ledger entries.')">@csrf<button class="btn-secondary w-full justify-center sm:w-auto">Cancel document</button></form>
        @endif
        <a href="{{ route('stock-adjustments.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Back</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="ss-card space-y-3 p-5">
        <h2 class="font-semibold">Header</h2>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Warehouse</span><span>{{ $adjustment->warehouse?->name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Date</span><span>{{ $adjustment->transaction_date?->format('Y-m-d') }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Reference</span><span>{{ $adjustment->reference_number ?: '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Created by</span><span>{{ $adjustment->creator?->name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Approved by</span><span>{{ $adjustment->submitter?->name ?: '—' }}</span></div>
        <p class="text-sm text-ss-muted">{{ $adjustment->remarks ?: 'No remarks.' }}</p>
    </div>
    <div class="ss-card overflow-hidden lg:col-span-2">
        <div class="border-b border-white/10 px-5 py-4 font-semibold">Lines</div>
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>System</th>
                        <th>Physical</th>
                        <th>Diff</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($adjustment->items as $item)
                        <tr>
                            <td class="font-mono text-xs">{{ $item->product?->sku }}</td>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->system_quantity }}</td>
                            <td>{{ $item->physical_quantity }}</td>
                            <td class="{{ $item->difference < 0 ? 'text-rose-300' : ($item->difference > 0 ? 'text-teal-300' : '') }}">
                                {{ $item->difference > 0 ? '+' : '' }}{{ $item->difference }}
                            </td>
                            <td>{{ \App\Models\StockAdjustment::REASONS[$item->reason] ?? $item->reason }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
