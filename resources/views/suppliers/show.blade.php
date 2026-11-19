@extends('layouts.app')
@section('title', $supplier->name)
@section('heading', $supplier->name)
@section('subheading', $supplier->code)
@section('breadcrumb', 'SmartStock / Suppliers / Details')

@section('content')
@php
    $canManage = auth()->user()->hasPermission('suppliers.manage')
        || auth()->user()->hasPermission('suppliers.update')
        || auth()->user()->canManageInventory();
@endphp

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <span class="badge {{ $supplier->is_active ? 'bg-teal-400/15 text-teal-300' : 'bg-rose-400/15 text-rose-300' }}">
        {{ $supplier->is_active ? 'Active' : 'Inactive' }}
    </span>
    <div class="flex flex-col gap-2 sm:flex-row">
        @if($canManage)
            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn-primary w-full justify-center sm:w-auto">Edit</a>
            <x-confirm-delete :action="route('suppliers.destroy', $supplier)" title="Delete supplier?" :message="count($blockers) ? implode(' ', $blockers) : 'Delete this supplier?'" class="w-full sm:w-auto" buttonClass="btn-danger w-full justify-center" />
        @endif
        <a href="{{ route('suppliers.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Back</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="ss-card space-y-3 p-5 lg:col-span-1">
        <h2 class="font-semibold">Supplier profile</h2>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Company</span><span>{{ $supplier->company_name ?: '—' }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Contact</span><span>{{ $supplier->contact_person ?: '—' }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Email</span><span>{{ $supplier->email ?: '—' }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Phone</span><span>{{ $supplier->phone ?: '—' }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">GST</span><span>{{ $supplier->gst_number ?: '—' }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Payment terms</span><span>{{ $supplier->payment_terms ?: '—' }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Location</span><span>{{ collect([$supplier->city, $supplier->state, $supplier->pincode])->filter()->implode(', ') ?: '—' }}</span></div>
        <p class="pt-2 text-sm text-ss-muted">{{ $supplier->billing_address ?: 'No billing address.' }}</p>
        <p class="text-sm text-ss-muted">{{ $supplier->notes ?: '' }}</p>
    </div>

    <div class="ss-card overflow-hidden lg:col-span-2">
        <div class="border-b border-white/10 px-5 py-4 font-semibold">Purchase history</div>
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseOrders as $order)
                        <tr>
                            <td class="font-mono text-xs">{{ $order->order_number }}</td>
                            <td class="capitalize">{{ $order->status }}</td>
                            <td>${{ number_format($order->total, 2) }}</td>
                            <td>{{ $order->created_at?->format('Y-m-d') }}</td>
                            <td class="text-right"><a href="{{ route('purchase-orders.show', $order) }}" class="text-sky-300">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-ss-muted">No matching purchase orders yet. PO supplier FK arrives in Phase 4.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $purchaseOrders->links() }}</div>
    </div>
</div>
@endsection
