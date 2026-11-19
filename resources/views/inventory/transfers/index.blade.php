@extends('layouts.app')
@section('title', 'Warehouse Transfers')
@section('heading', 'Warehouse Transfers')
@section('subheading', 'Dispatch → in transit → receive (partial or full)')
@section('breadcrumb', 'SmartStock / Inventory / Transfers')

@section('content')
@php $canManage = auth()->user()->hasPermission('inventory.transfer') || auth()->user()->hasPermission('inventory.manage') || auth()->user()->canManageInventory(); @endphp

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
                @foreach(['draft','in_transit','partially_received','completed','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary">Filter</button>
        </div>
    </form>
    @if($canManage)
        <a href="{{ route('stock-transfers.create') }}" class="btn-primary w-full justify-center sm:w-auto">New transfer</a>
    @endif
</div>

@if($transfers->isEmpty())
    <x-empty-state title="No transfers" description="Create a transfer to move stock between warehouses." icon="arrow-left-right" />
@else
    <div class="card overflow-hidden">
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Date</th>
                        <th>Lines</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfers as $transfer)
                        <tr>
                            <td class="font-mono text-xs">{{ $transfer->document_number }}</td>
                            <td>{{ $transfer->fromWarehouse?->name }}</td>
                            <td>{{ $transfer->toWarehouse?->name }}</td>
                            <td>{{ $transfer->transaction_date?->format('Y-m-d') }}</td>
                            <td>{{ $transfer->items_count }}</td>
                            <td>
                                <span class="badge {{ in_array($transfer->status, ['completed']) ? 'bg-teal-400/15 text-teal-300' : (in_array($transfer->status, ['cancelled']) ? 'bg-rose-400/15 text-rose-300' : (in_array($transfer->status, ['in_transit','partially_received']) ? 'bg-sky-400/15 text-sky-300' : 'bg-amber-400/15 text-amber-300')) }}">
                                    {{ str_replace('_', ' ', ucfirst($transfer->status)) }}
                                </span>
                            </td>
                            <td class="text-right"><a href="{{ route('stock-transfers.show', $transfer) }}" class="text-sky-300">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
<div class="mt-4">{{ $transfers->links() }}</div>
@endsection
