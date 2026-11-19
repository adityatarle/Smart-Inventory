@extends('layouts.app')
@section('title', $customer->name)
@section('heading', $customer->name)
@section('subheading', $customer->code)

@section('content')
<div class="mb-5 flex gap-3">
    @if(auth()->user()->canOperateOrders())
        <a href="{{ route('customers.edit', $customer) }}" class="btn-primary">Edit</a>
    @endif
    <a href="{{ route('customers.index') }}" class="btn-secondary">Back</a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="card space-y-3 p-6">
        <div class="flex justify-between text-sm"><span class="text-muted">Company</span><span>{{ $customer->company ?: '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Email</span><span>{{ $customer->email ?: '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Phone</span><span>{{ $customer->phone ?: '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Sales person</span><span>{{ $customer->salesPerson?->name ?: '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Partner</span><span>{{ $customer->partner?->name ?: '—' }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-muted">Credit limit</span><span>${{ number_format($customer->credit_limit, 2) }}</span></div>
    </div>
    <div class="card space-y-4 p-6 lg:col-span-2">
        <div>
            <h2 class="mb-2 font-semibold">Recent quotations</h2>
            @forelse($customer->quotations->take(5) as $q)
                <a href="{{ route('quotations.show', $q) }}" class="mb-2 block rounded-xl border border-line px-3 py-2 text-sm hover:border-teal-400/30">{{ $q->quotation_number }} · {{ $q->status }}</a>
            @empty
                <p class="text-sm text-muted">None</p>
            @endforelse
        </div>
        <div>
            <h2 class="mb-2 font-semibold">Recent sales orders</h2>
            @forelse($customer->salesOrders->take(5) as $o)
                <a href="{{ route('sales-orders.show', $o) }}" class="mb-2 block rounded-xl border border-line px-3 py-2 text-sm hover:border-teal-400/30">{{ $o->order_number }} · {{ $o->status }}</a>
            @empty
                <p class="text-sm text-muted">None</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
