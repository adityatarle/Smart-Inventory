@extends('layouts.app')
@section('title', $purchaseOrder->order_number)
@section('heading', $purchaseOrder->order_number)
@section('subheading', $purchaseOrder->supplierDisplayName().' · '.$purchaseOrder->statusLabel())
@section('breadcrumb', 'SmartStock / Purchase / Details')

@section('content')
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
    <span class="badge bg-sky-400/15 text-sky-300">{{ $purchaseOrder->statusLabel() }}</span>
    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
        @if($canManage && in_array($purchaseOrder->status, ['draft','rejected'], true))
            <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}" class="btn-secondary w-full justify-center sm:w-auto">Edit</a>
            <form method="POST" action="{{ route('purchase-orders.submit', $purchaseOrder) }}">@csrf<button class="btn-primary w-full justify-center sm:w-auto">Submit for approval</button></form>
            @if($purchaseOrder->isDraft())
                <x-confirm-delete :action="route('purchase-orders.destroy', $purchaseOrder)" title="Delete draft?" message="Delete this draft PO?" class="w-full sm:w-auto" buttonClass="btn-danger w-full justify-center" />
            @endif
        @endif
        @if($canApprove && $purchaseOrder->isPendingApproval())
            <form method="POST" action="{{ route('purchase-orders.approve', $purchaseOrder) }}">@csrf<button class="btn-primary w-full justify-center sm:w-auto">Approve</button></form>
            <form method="POST" action="{{ route('purchase-orders.reject', $purchaseOrder) }}" class="flex flex-col gap-2 sm:flex-row">
                @csrf
                <input name="rejection_reason" class="input" placeholder="Rejection reason" required>
                <button class="btn-danger w-full justify-center sm:w-auto">Reject</button>
            </form>
        @endif
        @if($canReceive && $purchaseOrder->canReceiveGoods())
            <a href="{{ route('goods-receipts.create', ['purchase_order_id' => $purchaseOrder->id]) }}" class="btn-primary w-full justify-center sm:w-auto">Create GRN</a>
        @endif
        @if($canManage && !in_array($purchaseOrder->status, ['cancelled','fully_received'], true))
            <form method="POST" action="{{ route('purchase-orders.cancel', $purchaseOrder) }}" onsubmit="return confirm('Cancel this purchase order?')">@csrf<button class="btn-secondary w-full justify-center sm:w-auto">Cancel PO</button></form>
        @endif
        <a href="{{ route('purchase-orders.print', $purchaseOrder) }}" target="_blank" class="btn-secondary w-full justify-center sm:w-auto">Print / PDF</a>
        <a href="{{ route('purchase-orders.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Back</a>
    </div>
</div>

@if($purchaseOrder->rejection_reason)
    <div class="mb-4 rounded-xl border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-200">Rejected: {{ $purchaseOrder->rejection_reason }}</div>
@endif

<div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Subtotal</p><p class="mt-1 text-lg font-semibold">${{ number_format($purchaseOrder->subtotal, 2) }}</p></div>
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Discount</p><p class="mt-1 text-lg font-semibold">${{ number_format($purchaseOrder->discount_amount, 2) }}</p></div>
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Tax</p><p class="mt-1 text-lg font-semibold">${{ number_format($purchaseOrder->tax_amount, 2) }}</p></div>
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Grand total</p><p class="mt-1 text-lg font-semibold">${{ number_format($purchaseOrder->total, 2) }}</p></div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="ss-card space-y-3 p-5">
        <h2 class="font-semibold">Header</h2>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Supplier</span>
            <span class="text-right">
                @if($purchaseOrder->supplier)
                    <a href="{{ route('suppliers.show', $purchaseOrder->supplier) }}" class="text-sky-300">{{ $purchaseOrder->supplierDisplayName() }}</a>
                    <div class="text-xs text-ss-muted">{{ $purchaseOrder->supplier->code }}</div>
                @else
                    {{ $purchaseOrder->supplierDisplayName() }}
                @endif
            </span>
        </div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Warehouse</span><span>{{ $purchaseOrder->warehouse?->name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Order date</span><span>{{ optional($purchaseOrder->order_date)->format('Y-m-d') }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Expected</span><span>{{ optional($purchaseOrder->expected_delivery_date)->format('Y-m-d') ?: '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Created by</span><span>{{ $purchaseOrder->orderedBy?->name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Submitted by</span><span>{{ $purchaseOrder->submitter?->name ?: '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Approved by</span><span>{{ $purchaseOrder->approver?->name ?: '—' }}</span></div>
        <p class="text-sm text-ss-muted">{{ $purchaseOrder->notes ?: 'No notes.' }}</p>
        @if($purchaseOrder->terms)<p class="text-xs text-ss-muted whitespace-pre-line">{{ $purchaseOrder->terms }}</p>@endif
    </div>
    <div class="ss-card overflow-hidden lg:col-span-2">
        <div class="border-b border-white/10 px-5 py-4 font-semibold">Lines</div>
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Received</th>
                        <th>Rejected</th>
                        <th>Unit</th>
                        <th>Disc%</th>
                        <th>Tax%</th>
                        <th>Line total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $item)
                        <tr>
                            <td>{{ $item->product?->name }}<div class="font-mono text-xs text-ss-muted">{{ $item->product?->sku }}</div></td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->received_quantity }}</td>
                            <td>{{ $item->rejected_quantity }}</td>
                            <td>${{ number_format($item->unit_cost, 4) }}</td>
                            <td>{{ number_format($item->discount_percent, 2) }}</td>
                            <td>{{ number_format($item->tax_percent, 2) }}</td>
                            <td>${{ number_format($item->lineTotal(), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-6 ss-card overflow-hidden">
    <div class="border-b border-white/10 px-5 py-4 font-semibold">Goods receipts</div>
    <div class="table-wrap border-0">
        <table class="data">
            <thead><tr><th>GRN</th><th>Date</th><th>Status</th><th>Warehouse</th><th></th></tr></thead>
            <tbody>
                @forelse($purchaseOrder->goodsReceipts as $grn)
                    <tr>
                        <td class="font-mono text-xs">{{ $grn->document_number }}</td>
                        <td>{{ optional($grn->receipt_date)->format('Y-m-d') }}</td>
                        <td>{{ ucfirst($grn->status) }}</td>
                        <td>{{ $grn->warehouse?->name }}</td>
                        <td class="text-right"><a href="{{ route('goods-receipts.show', $grn) }}" class="text-sky-300">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-ss-muted">No GRNs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
