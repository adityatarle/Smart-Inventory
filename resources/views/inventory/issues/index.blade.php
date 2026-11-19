@extends('layouts.app')
@section('title', 'Stock Out')
@section('heading', 'Stock Out')
@section('subheading', 'Issue stock from warehouses (draft → submit)')
@section('breadcrumb', 'SmartStock / Inventory / Stock Out')

@section('content')
@php $canManage = auth()->user()->hasPermission('inventory.issue') || auth()->user()->hasPermission('inventory.manage') || auth()->user()->canManageInventory(); @endphp

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
        <a href="{{ route('stock-issues.create') }}" class="btn-primary w-full justify-center sm:w-auto">New stock out</a>
    @endif
</div>

@if($issues->isEmpty())
    <x-empty-state title="No stock issues" description="Create a draft issue, then submit to deduct inventory." icon="arrow-up-from-line" />
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
                    @foreach($issues as $issue)
                        <tr>
                            <td class="font-mono text-xs">{{ $issue->document_number }}</td>
                            <td>{{ $issue->warehouse?->name }}</td>
                            <td>{{ $issue->transaction_date?->format('Y-m-d') }}</td>
                            <td>{{ $issue->items_count }}</td>
                            <td>
                                <span class="badge {{ $issue->status === 'submitted' ? 'bg-teal-400/15 text-teal-300' : ($issue->status === 'cancelled' ? 'bg-rose-400/15 text-rose-300' : 'bg-amber-400/15 text-amber-300') }}">
                                    {{ ucfirst($issue->status) }}
                                </span>
                            </td>
                            <td>{{ $issue->creator?->name }}</td>
                            <td class="text-right"><a href="{{ route('stock-issues.show', $issue) }}" class="text-sky-300">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
<div class="mt-4">{{ $issues->links() }}</div>
@endsection
