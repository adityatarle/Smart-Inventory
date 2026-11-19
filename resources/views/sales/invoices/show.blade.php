@extends('layouts.app')
@section('title', $salesInvoice->invoice_number)
@section('heading', $salesInvoice->invoice_number)
@section('subheading', $salesInvoice->customer?->name)

@section('content')
<div class="mb-5 flex flex-wrap gap-3">
    @if(auth()->user()->canOperateOrders() && $salesInvoice->status !== 'paid' && $salesInvoice->status !== 'cancelled')
        <form method="POST" action="{{ route('sales-invoices.pay', $salesInvoice) }}">@csrf<button class="btn-primary">Mark paid</button></form>
        <form method="POST" action="{{ route('sales-invoices.cancel', $salesInvoice) }}">@csrf<button class="btn-danger">Cancel</button></form>
    @endif
    <a href="{{ route('sales-invoices.index') }}" class="btn-secondary">Back</a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="card space-y-3 p-6">
        <div class="flex justify-between text-sm"><span class="text-muted">Status</span><span class="badge bg-emerald-400/15 text-emerald-300">{{ $salesInvoice->status }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Invoice date</span><span>{{ optional($salesInvoice->invoice_date)->toDateString() }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Due date</span><span>{{ optional($salesInvoice->due_date)->toDateString() }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Total</span><span class="font-semibold">${{ number_format($salesInvoice->total, 2) }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Paid</span><span>${{ number_format($salesInvoice->amount_paid, 2) }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Balance</span><span>${{ number_format($salesInvoice->balanceDue(), 2) }}</span></div>
        @if($salesInvoice->salesOrder)
            <a href="{{ route('sales-orders.show', $salesInvoice->salesOrder) }}" class="text-sm text-teal-300">Order: {{ $salesInvoice->salesOrder->order_number }}</a>
        @endif
    </div>
    <div class="card overflow-hidden lg:col-span-2">
        <div class="table-wrap border-0">
            <table class="data">
                <thead><tr><th>Product</th><th>Qty</th><th>Unit price</th><th>Line total</th></tr></thead>
                <tbody>
                    @foreach($salesInvoice->items as $item)
                        <tr>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>${{ number_format($item->unit_price, 2) }}</td>
                            <td>${{ number_format($item->lineTotal(), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
