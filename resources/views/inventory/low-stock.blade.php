@extends('layouts.app')
@section('title', 'Low Stock Report')
@section('heading', 'Low Stock Report')
@section('subheading', 'Items at or below reorder level, plus out-of-stock')
@section('breadcrumb', 'SmartStock / Inventory / Low Stock')

@section('content')
<div class="ss-page-toolbar">
    <form method="GET" class="w-full sm:max-w-xs">
        <select name="warehouse_id" class="input w-full" onchange="this.form.submit()">
            <option value="">All warehouses</option>
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected($warehouseId == $warehouse->id)>{{ $warehouse->name }}</option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('inventory.alerts') }}" class="btn-secondary w-full justify-center sm:w-auto">Alert history</a>
</div>

@if($rows->isEmpty())
    <x-empty-state title="No low stock items" description="All filtered warehouse stocks are above reorder level." icon="package-check" />
@else
    <div class="card overflow-hidden">
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Warehouse</th>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>On hand</th>
                        <th>Reorder</th>
                        <th>Max</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php
                            $reorder = (int) ($row->product?->reorder_level ?? 0);
                            $status = $row->quantity <= 0 ? 'Out of stock' : 'Low / reorder';
                        @endphp
                        <tr>
                            <td>{{ $row->warehouse?->name }}</td>
                            <td class="font-mono text-xs">{{ $row->product?->sku }}</td>
                            <td>{{ $row->product?->name }}</td>
                            <td class="{{ $row->quantity <= 0 ? 'text-rose-300' : 'text-amber-300' }}">{{ $row->quantity }}</td>
                            <td>{{ $reorder }}</td>
                            <td>{{ $row->product?->max_stock_level ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $row->quantity <= 0 ? 'bg-rose-400/15 text-rose-300' : 'bg-amber-400/15 text-amber-300' }}">{{ $status }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
<div class="mt-4">{{ $rows->links() }}</div>
@endsection
