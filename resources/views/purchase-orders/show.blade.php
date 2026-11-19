@extends('layouts.app')

@section('title', $purchaseOrder->order_number)
@section('heading', $purchaseOrder->order_number)
@section('subheading', $purchaseOrder->supplier_name.' · '.$purchaseOrder->warehouse?->name)

@section('content')
<div class="mb-5 flex flex-wrap gap-3">
    @if($purchaseOrder->status !== 'received' && $purchaseOrder->status !== 'cancelled')
        <form method="POST" action="{{ route('purchase-orders.receive', $purchaseOrder) }}">
            @csrf
            <button class="btn-primary">Receive & update stock</button>
        </form>
        <form method="POST" action="{{ route('purchase-orders.cancel', $purchaseOrder) }}">
            @csrf
            <button class="btn-danger">Cancel</button>
        </form>
    @endif
    <a href="{{ route('purchase-orders.index') }}" class="btn-secondary">Back</a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="card space-y-3 p-6 lg:col-span-1">
        <div class="flex justify-between text-sm"><span class="text-muted">Status</span><span class="badge bg-sky-400/15 text-sky-300">{{ $purchaseOrder->status }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Ordered by</span><span>{{ $purchaseOrder->orderedBy?->name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Total</span><span class="font-semibold">${{ number_format($purchaseOrder->total, 2) }}</span></div>
        <p class="text-sm text-muted">{{ $purchaseOrder->notes ?: 'No notes.' }}</p>
    </div>
    <div class="card overflow-hidden lg:col-span-2">
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Received</th>
                        <th>Unit cost</th>
                        <th>Line total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $item)
                        <tr>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->received_quantity }}</td>
                            <td>${{ number_format($item->unit_cost, 2) }}</td>
                            <td>${{ number_format($item->lineTotal(), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
