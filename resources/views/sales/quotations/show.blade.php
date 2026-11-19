@extends('layouts.app')
@section('title', $quotation->quotation_number)
@section('heading', $quotation->quotation_number)
@section('subheading', $quotation->customer?->name)

@section('content')
<div class="mb-5 flex flex-wrap gap-3">
    @if(auth()->user()->canOperateOrders() && $quotation->status !== 'converted')
        <form method="POST" action="{{ route('quotations.convert', $quotation) }}">@csrf<button class="btn-primary">Convert to sales order</button></form>
        <form method="POST" action="{{ route('quotations.status', $quotation) }}" class="flex gap-2">
            @csrf
            <select name="status" class="input">
                @foreach(['draft','sent','accepted','rejected','expired'] as $status)
                    <option value="{{ $status }}" @selected($quotation->status === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary">Update status</button>
        </form>
    @endif
    <a href="{{ route('quotations.index') }}" class="btn-secondary">Back</a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="card space-y-3 p-6">
        <div class="flex justify-between text-sm"><span class="text-muted">Status</span><span class="badge bg-sky-400/15 text-sky-300">{{ $quotation->status }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Warehouse</span><span>{{ $quotation->warehouse?->name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Sales person</span><span>{{ $quotation->salesPerson?->name ?: '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Partner</span><span>{{ $quotation->partner?->name ?: '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Valid until</span><span>{{ optional($quotation->valid_until)->toDateString() ?: '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Total</span><span class="font-semibold">${{ number_format($quotation->total, 2) }}</span></div>
        @if($quotation->salesOrder)
            <a href="{{ route('sales-orders.show', $quotation->salesOrder) }}" class="text-sm text-teal-300">Linked order: {{ $quotation->salesOrder->order_number }}</a>
        @endif
    </div>
    <div class="card overflow-hidden lg:col-span-2">
        <div class="table-wrap border-0">
            <table class="data">
                <thead><tr><th>Product</th><th>Qty</th><th>Unit price</th><th>Line total</th></tr></thead>
                <tbody>
                    @foreach($quotation->items as $item)
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
