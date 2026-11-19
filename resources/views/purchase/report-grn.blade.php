@extends('layouts.app')
@section('title', 'GRN Register')
@section('heading', 'Goods Receipt Register')
@section('breadcrumb', 'SmartStock / Purchase / Reports / GRN')
@section('content')
@include('purchase._report-filters')
<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead><tr><th>GRN</th><th>PO</th><th>Supplier</th><th>Warehouse</th><th>Date</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($receipts as $receipt)
                    <tr>
                        <td class="font-mono text-xs">{{ $receipt->document_number }}</td>
                        <td class="font-mono text-xs">{{ $receipt->purchaseOrder?->order_number }}</td>
                        <td>{{ $receipt->supplier?->name }}</td>
                        <td>{{ $receipt->warehouse?->name }}</td>
                        <td>{{ optional($receipt->receipt_date)->format('Y-m-d') }}</td>
                        <td>{{ ucfirst($receipt->status) }}</td>
                        <td class="text-right"><a href="{{ route('goods-receipts.show', $receipt) }}" class="text-sky-300">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-ss-muted">No GRNs.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $receipts->links() }}</div>
@endsection
