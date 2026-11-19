@extends('layouts.app')
@section('title', 'Purchase Dashboard')
@section('heading', 'Purchase Dashboard')
@section('subheading', 'Live procurement KPIs from purchase orders and GRNs')
@section('breadcrumb', 'SmartStock / Purchase / Dashboard')

@section('content')
<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach([
        ['Total POs', $stats['total'], 'clipboard-list'],
        ['Pending approval', $stats['pending_approval'], 'hourglass'],
        ['Approved', $stats['approved'], 'badge-check'],
        ['Pending delivery', $stats['pending_delivery'], 'truck'],
        ['Partially received', $stats['partial'], 'package-open'],
        ['Fully received', $stats['fully_received'], 'package-check'],
        ['Monthly PO value', '$'.number_format($stats['monthly_value'], 2), 'wallet'],
        ['GRNs this month', $stats['grns_month'], 'file-input'],
    ] as [$label, $value, $icon])
        <div class="ss-card p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs text-ss-muted">{{ $label }}</p>
                    <p class="mt-1 text-xl font-semibold">{{ $value }}</p>
                </div>
                <i data-lucide="{{ $icon }}" class="h-5 w-5 text-ss-primary"></i>
            </div>
        </div>
    @endforeach
</div>

<div class="mb-6 flex flex-wrap gap-2">
    <a href="{{ route('purchase-orders.create') }}" class="btn-primary">New PO</a>
    <a href="{{ route('goods-receipts.create') }}" class="btn-secondary">New GRN</a>
    <a href="{{ route('purchase.reports') }}" class="btn-secondary">Reports</a>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="ss-card overflow-hidden">
        <div class="border-b border-white/10 px-5 py-4 font-semibold">Pending approvals</div>
        <div class="table-wrap border-0">
            <table class="data">
                <thead><tr><th>PO</th><th>Supplier</th><th>Total</th><th></th></tr></thead>
                <tbody>
                    @forelse($pendingApprovals as $order)
                        <tr>
                            <td class="font-mono text-xs">{{ $order->order_number }}</td>
                            <td>{{ $order->supplierDisplayName() }}</td>
                            <td>${{ number_format($order->total, 2) }}</td>
                            <td class="text-right"><a href="{{ route('purchase-orders.show', $order) }}" class="text-sky-300">Review</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-ss-muted">No pending approvals.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="ss-card overflow-hidden">
        <div class="border-b border-white/10 px-5 py-4 font-semibold">Supplier-wise purchase value</div>
        <div class="table-wrap border-0">
            <table class="data">
                <thead><tr><th>Supplier</th><th>Orders</th><th>Value</th></tr></thead>
                <tbody>
                    @forelse($supplierWise as $row)
                        <tr>
                            <td>{{ $row->supplier?->name ?: '—' }}</td>
                            <td>{{ $row->orders }}</td>
                            <td>${{ number_format($row->value, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-ss-muted">No supplier totals yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-6 ss-card overflow-hidden">
    <div class="border-b border-white/10 px-5 py-4 font-semibold">Recent purchase orders</div>
    <div class="table-wrap border-0">
        <table class="data">
            <thead><tr><th>PO</th><th>Supplier</th><th>Status</th><th>Total</th><th></th></tr></thead>
            <tbody>
                @foreach($recentOrders as $order)
                    <tr>
                        <td class="font-mono text-xs">{{ $order->order_number }}</td>
                        <td>{{ $order->supplierDisplayName() }}</td>
                        <td>{{ $order->statusLabel() }}</td>
                        <td>${{ number_format($order->total, 2) }}</td>
                        <td class="text-right"><a href="{{ route('purchase-orders.show', $order) }}" class="text-sky-300">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
