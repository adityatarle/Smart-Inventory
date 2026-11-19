@extends('layouts.app')
@section('title', 'Stock Valuation')
@section('heading', 'Stock Valuation')
@section('subheading', 'Moving weighted-average costing by warehouse and product')
@section('breadcrumb', 'SmartStock / Inventory / Valuation')

@section('content')
<div class="mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Opening value</p><p class="mt-1 text-xl font-semibold">${{ number_format($totals['opening_value'], 2) }}</p></div>
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Received value</p><p class="mt-1 text-xl font-semibold text-teal-300">${{ number_format($totals['received_value'], 2) }}</p></div>
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Issued value</p><p class="mt-1 text-xl font-semibold text-rose-300">${{ number_format($totals['issued_value'], 2) }}</p></div>
    <div class="ss-card p-4"><p class="text-xs text-ss-muted">Closing / live value</p><p class="mt-1 text-xl font-semibold">${{ number_format($totals['closing_value'], 2) }} <span class="text-xs text-ss-muted">(live ${{ number_format($currentValue, 2) }})</span></p></div>
</div>

<div class="ss-page-toolbar">
    <form method="GET" class="w-full">
        <div class="grid w-full gap-2 sm:grid-cols-5">
            <select name="warehouse_id" class="input">
                <option value="">All warehouses</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected($filters['warehouse_id'] == $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <select name="product_id" class="input">
                <option value="">All products</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected($filters['product_id'] == $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" class="input" value="{{ $filters['date_from'] }}">
            <input type="date" name="date_to" class="input" value="{{ $filters['date_to'] }}">
            <div class="flex gap-2">
                <button class="btn-secondary flex-1">Filter</button>
                <a href="{{ request()->fullUrlWithQuery(['export' => 1]) }}" class="btn-secondary">CSV</a>
            </div>
        </div>
    </form>
</div>

@if($rows->isEmpty())
    <x-empty-state title="No valuation rows" description="Post stock movements in this period to see valuation." icon="calculator" />
@else
    <div class="card overflow-hidden">
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Warehouse</th>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Opening qty</th>
                        <th>Opening value</th>
                        <th>Received</th>
                        <th>Issued</th>
                        <th>Closing qty</th>
                        <th>Avg cost</th>
                        <th>Closing value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ $row['warehouse'] }}</td>
                            <td class="font-mono text-xs">{{ $row['sku'] }}</td>
                            <td>{{ $row['product'] }}</td>
                            <td>{{ $row['opening_qty'] }}</td>
                            <td>${{ number_format($row['opening_value'], 2) }}</td>
                            <td>{{ $row['received_qty'] }} <span class="text-xs text-ss-muted">(${{ number_format($row['received_value'], 2) }})</span></td>
                            <td>{{ $row['issued_qty'] }} <span class="text-xs text-ss-muted">(${{ number_format($row['issued_value'], 2) }})</span></td>
                            <td>{{ $row['closing_qty'] }}</td>
                            <td>${{ number_format($row['average_unit_cost'], 4) }}</td>
                            <td class="font-medium">${{ number_format($row['closing_value'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
