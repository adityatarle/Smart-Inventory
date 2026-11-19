@extends('layouts.app')
@section('title', 'Sales Invoices')
@section('heading', 'Sales Invoices')
@section('subheading', 'Billing documents linked to sales orders')

@section('content')
@if(auth()->user()->canOperateOrders())
<div class="mb-5 flex justify-end"><a href="{{ route('sales-invoices.create') }}" class="btn-primary">New invoice</a></div>
@endif
<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead><tr><th>Invoice #</th><th>Customer</th><th>Order</th><th>Status</th><th>Due</th><th>Total</th><th></th></tr></thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td class="font-medium">{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->customer?->name }}</td>
                        <td>{{ $invoice->salesOrder?->order_number ?: '—' }}</td>
                        <td><span class="badge bg-emerald-400/15 text-emerald-300">{{ $invoice->status }}</span></td>
                        <td>{{ optional($invoice->due_date)->toDateString() ?: '—' }}</td>
                        <td>${{ number_format($invoice->total, 2) }}</td>
                        <td class="text-right"><a href="{{ route('sales-invoices.show', $invoice) }}" class="text-teal-300">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-muted">No invoices yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $invoices->links() }}</div>
@endsection
