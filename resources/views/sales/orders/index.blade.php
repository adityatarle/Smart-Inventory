@extends('layouts.app')
@section('title', 'Sales Orders')
@section('heading', 'Sales Orders')
@section('subheading', 'Confirmed outbound customer orders')

@section('content')
<div class="mb-5 flex justify-end"><a href="{{ route('sales-orders.create') }}" class="btn-primary">New sales order</a></div>
<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead><tr><th>Order #</th><th>Customer</th><th>Warehouse</th><th>Person</th><th>Status</th><th>Total</th><th></th></tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="font-medium">{{ $order->order_number }}</td>
                        <td>{{ $order->customer?->name ?: $order->customer_name }}</td>
                        <td>{{ $order->warehouse?->name }}</td>
                        <td>{{ $order->salesPerson?->name ?: '—' }}</td>
                        <td><span class="badge bg-amber-400/15 text-amber-300">{{ $order->status }}</span></td>
                        <td>${{ number_format($order->total, 2) }}</td>
                        <td class="text-right"><a href="{{ route('sales-orders.show', $order) }}" class="text-teal-300">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-muted">No sales orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
