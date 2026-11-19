@extends('layouts.app')
@section('title', $customer->name)
@section('heading', $customer->name)
@section('subheading', $customer->code)
@section('breadcrumb', 'SmartStock / Customers / Details')

@section('content')
@php
    $canManage = auth()->user()->hasPermission('customers.manage')
        || auth()->user()->hasPermission('customers.update')
        || auth()->user()->canOperateOrders();
@endphp

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex flex-wrap gap-2">
        <span class="badge {{ $customer->is_active ? 'bg-teal-400/15 text-teal-300' : 'bg-rose-400/15 text-rose-300' }}">
            {{ $customer->is_active ? 'Active' : 'Inactive' }}
        </span>
        <span class="badge bg-ss-primary/15 text-ss-primary">{{ $customer->sales_orders_count }} orders</span>
        <span class="badge bg-ss-secondary/15 text-ss-secondary">{{ $customer->quotations_count }} quotations</span>
    </div>
    <div class="flex flex-col gap-2 sm:flex-row">
        @if($canManage)
            <a href="{{ route('customers.edit', $customer) }}" class="btn-primary w-full justify-center sm:w-auto">Edit</a>
            <x-confirm-delete :action="route('customers.destroy', $customer)" title="Delete customer?" :message="count($blockers) ? implode(' ', $blockers) : 'Delete this customer?'" class="w-full sm:w-auto" buttonClass="btn-danger w-full justify-center" />
        @endif
        <a href="{{ route('customers.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Back</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="ss-card space-y-3 p-5">
        <h2 class="font-semibold">Customer profile</h2>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Company</span><span>{{ $customer->company ?: '—' }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Email</span><span>{{ $customer->email ?: '—' }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Phone</span><span>{{ $customer->phone ?: '—' }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">GST</span><span>{{ $customer->gst_number ?: '—' }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Credit limit</span><span>${{ number_format($customer->credit_limit, 2) }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Payment terms</span><span>{{ $customer->payment_terms ?: '—' }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Location</span><span>{{ collect([$customer->city, $customer->state, $customer->pincode])->filter()->implode(', ') ?: '—' }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Sales person</span><span>{{ $customer->salesPerson?->name ?: '—' }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Partner</span><span>{{ $customer->partner?->name ?: '—' }}</span></div>
        <p class="pt-2 text-sm text-ss-muted"><strong class="text-ss-text">Billing:</strong> {{ $customer->billing_address ?: '—' }}</p>
        <p class="text-sm text-ss-muted"><strong class="text-ss-text">Shipping:</strong> {{ $customer->shipping_address ?: '—' }}</p>
    </div>

    <div class="ss-card p-5 lg:col-span-2">
        <h2 class="mb-4 font-semibold">Recent sales orders</h2>
        <div class="space-y-3">
            @forelse($salesOrders as $order)
                <a href="{{ route('sales-orders.show', $order) }}" class="flex items-center justify-between rounded-xl border border-white/10 bg-ss-card-2 px-3 py-2 hover:border-ss-primary/30">
                    <div>
                        <div class="text-sm font-medium">{{ $order->order_number }}</div>
                        <div class="text-xs text-ss-muted capitalize">{{ $order->status }} · {{ $order->created_at?->diffForHumans() }}</div>
                    </div>
                    <div class="font-semibold">${{ number_format($order->total, 2) }}</div>
                </a>
            @empty
                <p class="text-sm text-ss-muted">No sales orders yet.</p>
            @endforelse
        </div>

        <h2 class="mb-4 mt-6 font-semibold">Recent quotations</h2>
        <div class="space-y-3">
            @forelse($quotations as $quotation)
                <a href="{{ route('quotations.show', $quotation) }}" class="flex items-center justify-between rounded-xl border border-white/10 bg-ss-card-2 px-3 py-2 hover:border-ss-primary/30">
                    <div>
                        <div class="text-sm font-medium">{{ $quotation->quotation_number }}</div>
                        <div class="text-xs text-ss-muted capitalize">{{ $quotation->status }}</div>
                    </div>
                    <div class="font-semibold">${{ number_format($quotation->total, 2) }}</div>
                </a>
            @empty
                <p class="text-sm text-ss-muted">No quotations yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
