@extends('layouts.app')
@section('title', 'Stock Adjustments')
@section('heading', 'Stock Adjustments')
@section('subheading', 'Physical counts, damage, wastage, and corrections')
@section('breadcrumb', 'SmartStock / Inventory / Adjustments')

@section('content')
@php $canManage = auth()->user()->hasPermission('inventory.adjust') || auth()->user()->hasPermission('inventory.manage') || auth()->user()->canManageInventory(); @endphp

<div class="ss-page-toolbar">
    <form method="GET" class="w-full">
        <div class="grid w-full gap-2 sm:grid-cols-4">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Document or reference" class="input">
            <select name="warehouse_id" class="input">
                <option value="">All warehouses</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected(request('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <select name="status" class="input">
                <option value="">All statuses</option>
                @foreach(['draft','submitted','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary">Filter</button>
        </div>
    </form>
    @if($canManage)
        <a href="{{ route('stock-adjustments.create') }}" class="btn-primary w-full justify-center sm:w-auto">New adjustment</a>
    @endif
</div>

@if($adjustments->isEmpty())
    <x-empty-state title="No adjustments" description="Create a physical count adjustment, then submit to post the ledger difference." icon="sliders-horizontal" />
@else
    <div class="card overflow-hidden">
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Warehouse</th>
                        <th>Date</th>
                        <th>Lines</th>
                        <th>Status</th>
                        <th>Created by</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($adjustments as $adjustment)
                        <tr>
                            <td class="font-mono text-xs">{{ $adjustment->document_number }}</td>
                            <td>{{ $adjustment->warehouse?->name }}</td>
                            <td>{{ $adjustment->transaction_date?->format('Y-m-d') }}</td>
                            <td>{{ $adjustment->items_count }}</td>
                            <td>
                                <span class="badge {{ $adjustment->status === 'submitted' ? 'bg-teal-400/15 text-teal-300' : ($adjustment->status === 'cancelled' ? 'bg-rose-400/15 text-rose-300' : 'bg-amber-400/15 text-amber-300') }}">
                                    {{ ucfirst($adjustment->status) }}
                                </span>
                            </td>
                            <td>{{ $adjustment->creator?->name }}</td>
                            <td class="text-right"><a href="{{ route('stock-adjustments.show', $adjustment) }}" class="text-sky-300">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
<div class="mt-4">{{ $adjustments->links() }}</div>
@endsection
