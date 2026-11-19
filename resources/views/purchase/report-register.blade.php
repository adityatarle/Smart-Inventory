@extends('layouts.app')
@section('title', 'PO Register')
@section('heading', 'Purchase Order Register')
@section('breadcrumb', 'SmartStock / Purchase / Reports / Register')
@section('content')
@include('purchase._report-filters', ['export' => true])
<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead><tr><th>PO</th><th>Supplier</th><th>Warehouse</th><th>Status</th><th>Date</th><th>Total</th><th></th></tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="font-mono text-xs">{{ $order->order_number }}</td>
                        <td>{{ $order->supplierDisplayName() }}</td>
                        <td>{{ $order->warehouse?->name }}</td>
                        <td>{{ $order->statusLabel() }}</td>
                        <td>{{ optional($order->order_date)->format('Y-m-d') }}</td>
                        <td>${{ number_format($order->total, 2) }}</td>
                        <td class="text-right"><a href="{{ route('purchase-orders.show', $order) }}" class="text-sky-300">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-ss-muted">No rows.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
