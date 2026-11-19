@extends('layouts.app')
@section('title', 'Stock Ledger')
@section('heading', 'Stock Ledger')
@section('subheading', 'Immutable append-only inventory transaction history')
@section('breadcrumb', 'SmartStock / Inventory / Ledger')

@section('content')
<div class="ss-page-toolbar">
    <form method="GET" class="w-full">
        <div class="grid w-full gap-2 sm:grid-cols-2 xl:grid-cols-6">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Doc #, SKU, product" class="input xl:col-span-2">
            <select name="warehouse_id" class="input">
                <option value="">All warehouses</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected(request('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <select name="product_id" class="input">
                <option value="">All products</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->sku }}</option>
                @endforeach
            </select>
            <select name="type" class="input">
                <option value="">All types</option>
                @foreach($types as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                @endforeach
            </select>
            <select name="direction" class="input">
                <option value="">In & Out</option>
                <option value="in" @selected(request('direction') === 'in')>In</option>
                <option value="out" @selected(request('direction') === 'out')>Out</option>
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="input">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="input">
            <div class="flex gap-2 xl:col-span-2">
                <button class="btn-secondary w-full justify-center">Filter</button>
                <a href="{{ route('stock-movements.index') }}" class="btn-secondary shrink-0 px-3">Clear</a>
            </div>
        </div>
    </form>
    <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
        <a href="{{ route('inventory.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Overview</a>
        <a href="{{ route('stock-movements.index', request()->query() + ['export' => 1]) }}" class="btn-primary w-full justify-center sm:w-auto">Export CSV</a>
    </div>
</div>

@if($movements->isEmpty())
    <x-empty-state title="No ledger entries" description="Posted stock transactions will appear here." icon="scroll-text" />
@else
    <div class="card overflow-hidden">
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Document</th>
                        <th>Product</th>
                        <th>Warehouse</th>
                        <th>Type</th>
                        <th>In</th>
                        <th>Out</th>
                        <th>Balance</th>
                        <th>Unit cost</th>
                        <th>By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movements as $movement)
                        <tr>
                            <td class="whitespace-nowrap text-xs">{{ $movement->transaction_date?->format('Y-m-d H:i') }}</td>
                            <td class="font-mono text-xs">{{ $movement->document_number }}</td>
                            <td>
                                <div class="font-medium">{{ $movement->product?->name }}</div>
                                <div class="font-mono text-xs text-ss-muted">{{ $movement->product?->sku }}</div>
                            </td>
                            <td>{{ $movement->warehouse?->name }}</td>
                            <td>
                                <span class="badge {{ $movement->direction === 'in' ? 'bg-teal-400/15 text-teal-300' : 'bg-rose-400/15 text-rose-300' }}">
                                    {{ strtoupper($movement->type) }}
                                </span>
                            </td>
                            <td class="text-teal-300">{{ $movement->quantity_in ?: '—' }}</td>
                            <td class="text-rose-300">{{ $movement->quantity_out ?: '—' }}</td>
                            <td class="font-semibold">{{ $movement->balance_after }}</td>
                            <td>${{ number_format($movement->unit_cost, 4) }}</td>
                            <td>{{ $movement->user?->name ?: '—' }}</td>
                            <td class="text-right">
                                <a href="{{ route('stock-movements.show', $movement) }}" class="text-sky-300">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="mt-4 overflow-x-auto">{{ $movements->links() }}</div>
@endsection
