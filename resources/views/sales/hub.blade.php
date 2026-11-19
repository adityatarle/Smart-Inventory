@extends('layouts.app')

@section('title', 'Sales Hub')
@section('heading', 'Sales Module')
@section('subheading', 'Customers, quotations, orders, invoices, blankets, partners & persons')

@section('content')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach([
        ['Customers', $stats['customers'], route('customers.index'), 'Manage customer master'],
        ['Quotations', $stats['quotations'], route('quotations.index'), 'Quotes before confirmation'],
        ['Sales Orders', $stats['orders'], route('sales-orders.index'), 'Confirmed outbound orders'],
        ['Sales Invoices', $stats['invoices'], route('sales-invoices.index'), 'Billing & payments'],
        ['Blanket Orders', $stats['blankets'], route('blanket-orders.index'), 'Long-term agreements'],
        ['Sales Partners', $stats['partners'], auth()->user()->canOperateOrders() ? route('sales-partners.index') : route('sales.hub'), 'Channel partners'],
        ['Sales Persons', $stats['persons'], auth()->user()->canOperateOrders() ? route('sales-persons.index') : route('sales.hub'), 'Assigned sellers'],
    ] as [$label, $value, $url, $desc])
        <a href="{{ $url }}" class="card block p-5 transition hover:border-teal-400/40">
            <div class="text-xs font-semibold uppercase tracking-wide text-muted">{{ $label }}</div>
            <div class="mt-2 text-3xl font-bold text-teal-300">{{ $value }}</div>
            <p class="mt-2 text-sm text-muted">{{ $desc }}</p>
        </a>
    @endforeach
</div>

@if(auth()->user()->canOperateOrders())
    <div class="mt-6 flex flex-wrap gap-3">
        <a href="{{ route('customers.create') }}" class="btn-primary">New customer</a>
        <a href="{{ route('quotations.create') }}" class="btn-secondary">New quotation</a>
        <a href="{{ route('sales-orders.create') }}" class="btn-secondary">New sales order</a>
        <a href="{{ route('sales-invoices.create') }}" class="btn-secondary">New invoice</a>
        <a href="{{ route('blanket-orders.create') }}" class="btn-secondary">New blanket order</a>
    </div>
@endif
@endsection
