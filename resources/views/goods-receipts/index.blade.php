@extends('layouts.app')
@section('title', 'Goods Receipts')
@section('heading', 'Goods Receipt Notes')
@section('subheading', 'Receive against approved purchase orders')
@section('breadcrumb', 'SmartStock / Purchase / GRN')

@section('content')
<div class="ss-page-toolbar">
    <form method="GET" class="w-full">
        <div class="grid w-full gap-2 sm:grid-cols-3">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="GRN or PO number" class="input">
            <select name="status" class="input">
                <option value="">All statuses</option>
                @foreach(['draft','submitted','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="btn-secondary">Filter</button>
        </div>
    </form>
    <a href="{{ route('goods-receipts.create') }}" class="btn-primary w-full justify-center sm:w-auto">New GRN</a>
</div>

@if($receipts->isEmpty())
    <x-empty-state title="No goods receipts" description="Create a GRN from an approved purchase order." icon="package-check" />
@else
    <div class="card overflow-hidden">
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>GRN</th>
                        <th>PO</th>
                        <th>Supplier</th>
                        <th>Warehouse</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receipts as $receipt)
                        <tr>
                            <td class="font-mono text-xs">{{ $receipt->document_number }}</td>
                            <td class="font-mono text-xs">{{ $receipt->purchaseOrder?->order_number }}</td>
                            <td>{{ $receipt->supplier?->name ?: $receipt->purchaseOrder?->supplier_name }}</td>
                            <td>{{ $receipt->warehouse?->name }}</td>
                            <td>{{ optional($receipt->receipt_date)->format('Y-m-d') }}</td>
                            <td><span class="badge bg-sky-400/15 text-sky-300">{{ ucfirst($receipt->status) }}</span></td>
                            <td class="text-right"><a href="{{ route('goods-receipts.show', $receipt) }}" class="text-teal-300">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
<div class="mt-4">{{ $receipts->links() }}</div>
@endsection
