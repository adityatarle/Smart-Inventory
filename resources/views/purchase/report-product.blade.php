@extends('layouts.app')
@section('title', 'Product-wise Purchase')
@section('heading', 'Product-wise Purchase Report')
@section('breadcrumb', 'SmartStock / Purchase / Reports / Product')
@section('content')
<form method="GET" class="ss-page-toolbar mb-4">
    <select name="supplier_id" class="input">
        <option value="">All suppliers</option>
        @foreach($suppliers as $supplier)
            <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
        @endforeach
    </select>
    <input type="date" name="date_from" class="input" value="{{ request('date_from') }}">
    <input type="date" name="date_to" class="input" value="{{ request('date_to') }}">
    <button class="btn-secondary">Filter</button>
    <a href="{{ route('purchase.reports') }}" class="btn-secondary">Hub</a>
</form>
<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead><tr><th>SKU</th><th>Product</th><th>Ordered</th><th>Received</th><th>Value</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="font-mono text-xs">{{ $row->product?->sku }}</td>
                        <td>{{ $row->product?->name }}</td>
                        <td>{{ $row->ordered_qty }}</td>
                        <td>{{ $row->received_qty }}</td>
                        <td>${{ number_format($row->ordered_value, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-ss-muted">No data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
