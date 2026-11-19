@extends('layouts.app')

@section('title', 'Purchase Orders')
@section('heading', 'Purchase Orders')
@section('subheading', 'Inbound procurement and receiving')

@section('content')
<div class="mb-5 flex justify-end">
    <a href="{{ route('purchase-orders.create') }}" class="btn-primary">New purchase order</a>
</div>

<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Supplier</th>
                    <th>Warehouse</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td class="font-medium">{{ $order->order_number }}</td>
                        <td>{{ $order->supplier_name }}</td>
                        <td>{{ $order->warehouse?->name }}</td>
                        <td><span class="badge bg-sky-400/15 text-sky-300">{{ $order->status }}</span></td>
                        <td>${{ number_format($order->total, 2) }}</td>
                        <td class="text-right"><a href="{{ route('purchase-orders.show', $order) }}" class="text-teal-300">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
