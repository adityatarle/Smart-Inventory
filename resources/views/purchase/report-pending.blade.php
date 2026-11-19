@extends('layouts.app')
@section('title', 'Pending POs')
@section('heading', 'Pending Purchase Orders')
@section('breadcrumb', 'SmartStock / Purchase / Reports / Pending')
@section('content')
@include('purchase._report-filters')
<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead><tr><th>PO</th><th>Supplier</th><th>Status</th><th>Total</th><th></th></tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="font-mono text-xs">{{ $order->order_number }}</td>
                        <td>{{ $order->supplierDisplayName() }}</td>
                        <td>{{ $order->statusLabel() }}</td>
                        <td>${{ number_format($order->total, 2) }}</td>
                        <td class="text-right"><a href="{{ route('purchase-orders.show', $order) }}" class="text-sky-300">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-ss-muted">No pending rows.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
