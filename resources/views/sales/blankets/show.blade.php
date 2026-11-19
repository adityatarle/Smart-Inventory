@extends('layouts.app')
@section('title', $blanketOrder->blanket_number)
@section('heading', $blanketOrder->blanket_number)
@section('subheading', $blanketOrder->customer?->name)

@section('content')
<div class="mb-5 flex flex-wrap gap-3">
    @if(auth()->user()->canOperateOrders() && $blanketOrder->status === 'active')
        <a href="{{ route('sales-orders.create', ['blanket_order_id' => $blanketOrder->id]) }}" class="btn-primary">Create sales order against blanket</a>
        <form method="POST" action="{{ route('blanket-orders.cancel', $blanketOrder) }}">@csrf<button class="btn-danger">Cancel</button></form>
    @endif
    <a href="{{ route('blanket-orders.index') }}" class="btn-secondary">Back</a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="card space-y-3 p-6">
        <div class="flex justify-between text-sm"><span class="text-muted">Status</span><span class="badge bg-violet-400/15 text-violet-300">{{ $blanketOrder->status }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Period</span><span>{{ optional($blanketOrder->start_date)->toDateString() }} → {{ optional($blanketOrder->end_date)->toDateString() }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Sales person</span><span>{{ $blanketOrder->salesPerson?->name ?: '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Partner</span><span>{{ $blanketOrder->partner?->name ?: '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Value</span><span class="font-semibold">${{ number_format($blanketOrder->total_value, 2) }}</span></div>
    </div>
    <div class="card overflow-hidden lg:col-span-2">
        <div class="table-wrap border-0">
            <table class="data">
                <thead><tr><th>Product</th><th>Agreed</th><th>Ordered</th><th>Remaining</th><th>Unit price</th></tr></thead>
                <tbody>
                    @foreach($blanketOrder->items as $item)
                        <tr>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->agreed_quantity }}</td>
                            <td>{{ $item->ordered_quantity }}</td>
                            <td class="text-teal-300">{{ $item->remainingQuantity() }}</td>
                            <td>${{ number_format($item->unit_price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($blanketOrder->salesOrders->isNotEmpty())
    <div class="card mt-6 p-5">
        <h2 class="mb-3 font-semibold">Linked sales orders</h2>
        @foreach($blanketOrder->salesOrders as $order)
            <a href="{{ route('sales-orders.show', $order) }}" class="mb-2 block rounded-xl border border-line px-3 py-2 text-sm hover:border-teal-400/30">{{ $order->order_number }} · {{ $order->status }} · ${{ number_format($order->total, 2) }}</a>
        @endforeach
    </div>
@endif
@endsection
