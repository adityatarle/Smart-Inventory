@extends('layouts.app')
@section('title', 'Purchase Orders')
@section('heading', 'Purchase Orders')
@section('subheading', 'Procurement documents and receiving status')
@section('breadcrumb', 'SmartStock / Purchase / Orders')

@section('content')
<div class="ss-page-toolbar">
    <form method="GET" class="w-full">
        <div class="grid w-full gap-2 sm:grid-cols-5">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Order # or supplier" class="input">
            <select name="supplier_id" class="input">
                <option value="">All suppliers</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
            <select name="warehouse_id" class="input">
                <option value="">All warehouses</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected(request('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <select name="status" class="input">
                <option value="">All statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_',' ', ucfirst($status)) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary">Filter</button>
        </div>
    </form>
    <a href="{{ route('purchase-orders.create') }}" class="btn-primary w-full justify-center sm:w-auto">New PO</a>
</div>

@if($orders->isEmpty())
    <x-empty-state title="No purchase orders" description="Create a draft PO, submit it for approval, then receive via GRN." icon="clipboard-list" />
@else
    <div class="card overflow-hidden">
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Supplier</th>
                        <th>Warehouse</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td class="font-mono text-xs">{{ $order->order_number }}</td>
                            <td>
                                @if($order->supplier)
                                    <a href="{{ route('suppliers.show', $order->supplier) }}" class="text-sky-300">{{ $order->supplierDisplayName() }}</a>
                                @else
                                    {{ $order->supplierDisplayName() }}
                                @endif
                            </td>
                            <td>{{ $order->warehouse?->name }}</td>
                            <td>{{ optional($order->order_date)->format('Y-m-d') }}</td>
                            <td><span class="badge bg-sky-400/15 text-sky-300">{{ $order->statusLabel() }}</span></td>
                            <td>${{ number_format($order->total, 2) }}</td>
                            <td class="text-right"><a href="{{ route('purchase-orders.show', $order) }}" class="text-teal-300">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
