@extends('layouts.app')
@section('title', 'Blanket Orders')
@section('heading', 'Blanket Orders')
@section('subheading', 'Long-term quantity agreements with customers')

@section('content')
@if(auth()->user()->canOperateOrders())
<div class="mb-5 flex justify-end"><a href="{{ route('blanket-orders.create') }}" class="btn-primary">New blanket order</a></div>
@endif
<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead><tr><th>Number</th><th>Customer</th><th>Period</th><th>Status</th><th>Value</th><th></th></tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="font-medium">{{ $order->blanket_number }}</td>
                        <td>{{ $order->customer?->name }}</td>
                        <td class="text-xs text-muted">{{ optional($order->start_date)->toDateString() }} → {{ optional($order->end_date)->toDateString() }}</td>
                        <td><span class="badge bg-violet-400/15 text-violet-300">{{ $order->status }}</span></td>
                        <td>${{ number_format($order->total_value, 2) }}</td>
                        <td class="text-right"><a href="{{ route('blanket-orders.show', $order) }}" class="text-teal-300">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-muted">No blanket orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
