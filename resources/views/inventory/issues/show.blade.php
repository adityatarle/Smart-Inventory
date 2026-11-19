@extends('layouts.app')
@section('title', $issue->document_number)
@section('heading', $issue->document_number)
@section('subheading', 'Stock Out · '.ucfirst($issue->status))
@section('breadcrumb', 'SmartStock / Inventory / Stock Out / Details')

@section('content')
@php $canManage = auth()->user()->hasPermission('inventory.issue') || auth()->user()->hasPermission('inventory.manage') || auth()->user()->canManageInventory(); @endphp

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
    <span class="badge {{ $issue->status === 'submitted' ? 'bg-teal-400/15 text-teal-300' : ($issue->status === 'cancelled' ? 'bg-rose-400/15 text-rose-300' : 'bg-amber-400/15 text-amber-300') }}">{{ ucfirst($issue->status) }}</span>
    <div class="flex flex-col gap-2 sm:flex-row">
        @if($canManage && $issue->isDraft())
            <a href="{{ route('stock-issues.edit', $issue) }}" class="btn-secondary w-full justify-center sm:w-auto">Edit</a>
            <form method="POST" action="{{ route('stock-issues.submit', $issue) }}">@csrf<button class="btn-primary w-full justify-center sm:w-auto">Submit</button></form>
            <x-confirm-delete :action="route('stock-issues.destroy', $issue)" title="Delete draft?" message="Delete this draft issue?" class="w-full sm:w-auto" buttonClass="btn-danger w-full justify-center" />
        @endif
        @if($canManage && in_array($issue->status, ['draft', 'submitted'], true))
            <form method="POST" action="{{ route('stock-issues.cancel', $issue) }}" onsubmit="return confirm('Cancel this issue? Submitted issues will reverse ledger entries.')">@csrf<button class="btn-secondary w-full justify-center sm:w-auto">Cancel document</button></form>
        @endif
        <a href="{{ route('stock-issues.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Back</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="ss-card space-y-3 p-5">
        <h2 class="font-semibold">Header</h2>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Warehouse</span><span>{{ $issue->warehouse?->name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Date</span><span>{{ $issue->transaction_date?->format('Y-m-d') }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Reference</span><span>{{ $issue->reference_number ?: '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Created by</span><span>{{ $issue->creator?->name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-ss-muted">Submitted by</span><span>{{ $issue->submitter?->name ?: '—' }}</span></div>
        <p class="text-sm text-ss-muted">{{ $issue->remarks ?: 'No remarks.' }}</p>
    </div>
    <div class="ss-card overflow-hidden lg:col-span-2">
        <div class="border-b border-white/10 px-5 py-4 font-semibold">Lines</div>
        <div class="table-wrap border-0">
            <table class="data">
                <thead><tr><th>SKU</th><th>Product</th><th>Qty</th></tr></thead>
                <tbody>
                    @foreach($issue->items as $item)
                        <tr>
                            <td class="font-mono text-xs">{{ $item->product?->sku }}</td>
                            <td>{{ $item->product?->name }}</td>
                            <td>{{ $item->quantity }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
