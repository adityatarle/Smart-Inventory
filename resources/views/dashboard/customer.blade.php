@extends('layouts.app')

@section('title', 'My Sales')
@section('heading', 'Customer Dashboard')
@section('subheading', 'Your quotations, orders, and invoices')

@section('content')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-muted">Welcome back, {{ auth()->user()->name }}{{ $customer ? ' · '.$customer->code : '' }}.</p>
    <div class="flex gap-2">
        <a href="{{ route('sales.hub') }}" class="btn-secondary">Sales hub</a>
        <a href="{{ route('sales-orders.create') }}" class="btn-primary">New order</a>
    </div>
</div>

<div class="grid gap-6 xl:grid-cols-3">
    <div class="card overflow-hidden xl:col-span-2">
        <div class="border-b border-line px-5 py-4 font-semibold">Sales orders</div>
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Warehouse</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="font-medium">{{ $order->order_number }}</td>
                            <td>{{ $order->warehouse?->name }}</td>
                            <td><span class="badge bg-sky-400/15 text-sky-300">{{ $order->status }}</span></td>
                            <td>${{ number_format($order->total, 2) }}</td>
                            <td><a href="{{ route('sales-orders.show', $order) }}" class="text-teal-300">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-muted">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card p-5">
            <h2 class="mb-3 font-semibold">Quotations</h2>
            @forelse($quotations as $quotation)
                <a href="{{ route('quotations.show', $quotation) }}" class="mb-2 block rounded-xl border border-line px-3 py-2 text-sm hover:border-teal-400/30">
                    {{ $quotation->quotation_number }} · {{ $quotation->status }}
                </a>
            @empty
                <p class="text-sm text-muted">No quotations.</p>
            @endforelse
        </div>
        <div class="card p-5">
            <h2 class="mb-3 font-semibold">Invoices</h2>
            @forelse($invoices as $invoice)
                <a href="{{ route('sales-invoices.show', $invoice) }}" class="mb-2 block rounded-xl border border-line px-3 py-2 text-sm hover:border-teal-400/30">
                    {{ $invoice->invoice_number }} · {{ $invoice->status }}
                </a>
            @empty
                <p class="text-sm text-muted">No invoices.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
