@extends('layouts.app')

@section('title', $salesOrder->order_number)
@section('heading', $salesOrder->order_number)
@section('subheading', $salesOrder->customer_name.' · '.$salesOrder->warehouse?->name)

@section('content')
<div class="mb-5 flex flex-wrap gap-3">
    @if(auth()->user()->canOperateOrders() && $salesOrder->status !== 'fulfilled' && $salesOrder->status !== 'cancelled')
        <form method="POST" action="{{ route('sales-orders.fulfill', $salesOrder) }}">
            @csrf
            <button class="btn-primary">Fulfill & deduct stock</button>
        </form>
    @endif
    @if($salesOrder->status !== 'fulfilled' && $salesOrder->status !== 'cancelled')
        <form method="POST" action="{{ route('sales-orders.cancel', $salesOrder) }}">
            @csrf
            <button class="btn-danger">Cancel</button>
        </form>
    @endif
    <a href="{{ route('sales-orders.index') }}" class="btn-secondary">Back</a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="card space-y-3 p-6">
        <div class="flex justify-between text-sm"><span class="text-muted">Status</span><span class="badge bg-amber-400/15 text-amber-300">{{ $salesOrder->status }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Created by</span><span>{{ $salesOrder->createdBy?->name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Total</span><span class="font-semibold">${{ number_format($salesOrder->total, 2) }}</span></div>
        <p class="text-sm text-muted">{{ $salesOrder->notes ?: 'No notes.' }}</p>
    </div>
    <div class="card overflow-hidden lg:col-span-2">
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Unit price</th>
                        <th>Line total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesOrder->items as $item)
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
