@extends('layouts.app')
@section('title', 'Stock In')
@section('heading', 'Stock In')
@section('subheading', 'Receive stock into warehouses (draft → submit)')
@section('breadcrumb', 'SmartStock / Inventory / Stock In')

@section('content')
@php $canManage = auth()->user()->hasPermission('inventory.receive') || auth()->user()->hasPermission('inventory.manage') || auth()->user()->canManageInventory(); @endphp

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
        <a href="{{ route('stock-receipts.create') }}" class="btn-primary w-full justify-center sm:w-auto">New stock in</a>
    @endif
</div>

@if($receipts->isEmpty())
    <x-empty-state title="No stock receipts" description="Create a draft receipt, then submit to post inventory." icon="arrow-down-to-line" />
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
                    @foreach($receipts as $receipt)
                        <tr>
                            <td class="font-mono text-xs">{{ $receipt->document_number }}</td>
                            <td>{{ $receipt->warehouse?->name }}</td>
                            <td>{{ $receipt->transaction_date?->format('Y-m-d') }}</td>
                            <td>{{ $receipt->items_count }}</td>
                            <td>
                                <span class="badge {{ $receipt->status === 'submitted' ? 'bg-teal-400/15 text-teal-300' : ($receipt->status === 'cancelled' ? 'bg-rose-400/15 text-rose-300' : 'bg-amber-400/15 text-amber-300') }}">
                                    {{ ucfirst($receipt->status) }}
                                </span>
                            </td>
                            <td>{{ $receipt->creator?->name }}</td>
                            <td class="text-right"><a href="{{ route('stock-receipts.show', $receipt) }}" class="text-sky-300">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
<div class="mt-4">{{ $receipts->links() }}</div>
@endsection
