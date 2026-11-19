@extends('layouts.app')
@section('title', 'Purchase Reports')
@section('heading', 'Purchase Reports')
@section('subheading', 'Registers and analytical summaries')
@section('breadcrumb', 'SmartStock / Purchase / Reports')

@section('content')
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @foreach([
        ['PO Register', 'All purchase orders with filters and CSV export', 'purchase.reports.register', 'clipboard-list'],
        ['Pending POs', 'Pending approval, approved, and partial receipts', 'purchase.reports.pending', 'hourglass'],
        ['GRN Register', 'Goods receipt history', 'purchase.reports.grn', 'package-check'],
        ['Supplier-wise', 'Purchase value by supplier', 'purchase.reports.supplier', 'truck'],
        ['Product-wise', 'Ordered vs received by product', 'purchase.reports.product', 'package'],
        ['Purchase Summary', 'Status counts and totals', 'purchase.reports.summary', 'chart-column'],
    ] as [$title, $desc, $route, $icon])
        <a href="{{ route($route) }}" class="ss-card block p-5 transition hover:border-ss-primary/40">
            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-ss-primary/15 text-ss-primary">
                <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
            </div>
            <h2 class="font-semibold">{{ $title }}</h2>
            <p class="mt-1 text-sm text-ss-muted">{{ $desc }}</p>
        </a>
    @endforeach
</div>
<p class="mt-6 text-sm text-ss-muted">Purchase returns report is unavailable until a returns module is implemented. Outstanding payables appear when payments exist.</p>
@endsection
