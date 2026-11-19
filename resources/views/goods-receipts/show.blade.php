@extends('layouts.app')
@section('title', $receipt->document_number)
@section('heading', $receipt->document_number)
@section('subheading', 'GRN · '.ucfirst($receipt->status).' · '.$receipt->purchaseOrder?->order_number)
@section('breadcrumb', 'SmartStock / Purchase / GRN / Details')

@section('content')
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
    <span class="badge bg-sky-400/15 text-sky-300">{{ ucfirst($receipt->status) }}</span>
    <div class="flex flex-col gap-2 sm:flex-row">
        @if($canManage && $receipt->isDraft())
            <form method="POST" action="{{ route('goods-receipts.submit', $receipt) }}">@csrf<button class="btn-primary w-full justify-center sm:w-auto">Submit & post stock</button></form>
            <form method="POST" action="{{ route('goods-receipts.cancel', $receipt) }}">@csrf<button class="btn-secondary w-full justify-center sm:w-auto">Cancel draft</button></form>
            <x-confirm-delete :action="route('goods-receipts.destroy', $receipt)" title="Delete draft GRN?" message="Delete this draft GRN?" class="w-full sm:w-auto" buttonClass="btn-danger w-full justify-center" />
        @endif
        <a href="{{ route('purchase-orders.show', $receipt->purchaseOrder) }}" class="btn-secondary w-full justify-center sm:w-auto">View PO</a>
        <a href="{{ route('goods-receipts.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Back</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="ss-card space-y-3 p-5">
        <h2 class="font-semibold">Header</h2>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">PO</span><span>{{ $receipt->purchaseOrder?->order_number }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Supplier</span><span>{{ $receipt->supplier?->name ?: $receipt->purchaseOrder?->supplier_name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Warehouse</span><span>{{ $receipt->warehouse?->name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Receipt date</span><span>{{ optional($receipt->receipt_date)->format('Y-m-d') }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Created by</span><span>{{ $receipt->creator?->name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Submitted by</span><span>{{ $receipt->submitter?->name ?: '—' }}</span></div>
        <p class="text-sm text-ss-muted">{{ $receipt->remarks ?: 'No remarks.' }}</p>
    </div>
    <div class="ss-card overflow-hidden lg:col-span-2">
        <div class="border-b border-white/10 px-5 py-4 font-semibold">Lines</div>
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Ordered</th>
                        <th>Prev</th>
                        <th>Received</th>
                        <th>Rejected</th>
                        <th>Accepted</th>
                        <th>Unit cost</th>
                        <th>Batch</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receipt->items as $item)
                        <tr>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->ordered_quantity }}</td>
                            <td>{{ $item->previously_received }}</td>
                            <td>{{ $item->received_quantity }}</td>
                            <td>{{ $item->rejected_quantity }}</td>
                            <td class="text-teal-300">{{ $item->accepted_quantity }}</td>
                            <td>${{ number_format($item->unit_cost, 4) }}</td>
                            <td>{{ $item->batch_reference ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
