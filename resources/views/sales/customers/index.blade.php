@extends('layouts.app')
@section('title', 'Customers')
@section('heading', 'Customers')
@section('subheading', 'Customer master data and sales relationships')
@section('breadcrumb', 'SmartStock / Customers')

@section('content')
@php
    $canManage = auth()->user()->hasPermission('customers.manage')
        || auth()->user()->hasPermission('customers.create')
        || auth()->user()->canOperateOrders();
@endphp

<div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-kpi-card label="Total Customers" :value="number_format($stats['total'])" icon="users" tone="primary" />
    <x-kpi-card label="Active" :value="number_format($stats['active'])" icon="check-circle" tone="success" />
    <x-kpi-card label="Inactive" :value="number_format($stats['inactive'])" icon="pause-circle" tone="secondary" />
    <x-kpi-card label="Credit Limits" :value="'$'.number_format($stats['credit'], 0)" icon="wallet" tone="info" />
</div>

<div class="ss-page-toolbar">
    <form method="GET" class="w-full">
        <div class="grid w-full gap-2 sm:grid-cols-3">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, code, GST" class="input">
            <select name="status" class="input">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <div class="flex gap-2">
                <button class="btn-secondary w-full justify-center">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('customers.index') }}" class="btn-secondary shrink-0 px-3">Clear</a>
                @endif
            </div>
        </div>
    </form>
    @if($canManage)
        <a href="{{ route('customers.create') }}" class="btn-primary w-full justify-center sm:w-auto">Add customer</a>
    @endif
</div>

@if($customers->isEmpty())
    <x-empty-state title="No customers found" description="Create customers to start sales workflows." icon="users">
        <x-slot:action>
            @if($canManage)
                <a href="{{ route('customers.create') }}" class="btn-primary">Add customer</a>
            @endif
        </x-slot:action>
    </x-empty-state>
@else
    <div class="space-y-3 md:hidden">
        @foreach($customers as $customer)
            <div class="ss-card p-4" x-data="{ open: false }">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="truncate font-semibold">{{ $customer->name }}</div>
                        <div class="mt-1 text-xs text-ss-muted">{{ $customer->code }} · {{ $customer->company ?: '—' }}</div>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-ss-muted hover:bg-white/5" @click="open = !open"><i data-lucide="more-vertical" class="h-4 w-4"></i></button>
                </div>
                <div x-show="open" x-cloak class="mt-3 flex gap-3 border-t border-white/10 pt-3 text-sm">
                    <a href="{{ route('customers.show', $customer) }}" class="font-semibold text-sky-300">View</a>
                    @if($canManage)
                        <a href="{{ route('customers.edit', $customer) }}" class="font-semibold text-teal-300">Edit</a>
                        <x-confirm-delete :action="route('customers.destroy', $customer)" title="Delete customer?" :message="'Delete '.$customer->name.'?'" buttonClass="font-semibold text-rose-300" />
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="card hidden overflow-hidden md:block">
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Company</th>
                        <th>City</th>
                        <th>Sales Person</th>
                        <th>Credit</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                        <tr>
                            <td class="font-mono text-xs">{{ $customer->code }}</td>
                            <td class="font-medium">{{ $customer->name }}</td>
                            <td>{{ $customer->company ?: '—' }}</td>
                            <td>{{ $customer->city ?: '—' }}</td>
                            <td>{{ $customer->salesPerson?->name ?: '—' }}</td>
                            <td>${{ number_format($customer->credit_limit, 2) }}</td>
                            <td>
                                <span class="badge {{ $customer->is_active ? 'bg-teal-400/15 text-teal-300' : 'bg-rose-400/15 text-rose-300' }}">
                                    {{ $customer->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right" x-data="{ open: false }">
                                <div class="relative inline-block">
                                    <button type="button" class="rounded-lg border border-white/10 px-2.5 py-1.5 text-ss-muted hover:text-ss-text" @click="open = !open" @click.outside="open = false">
                                        <i data-lucide="more-horizontal" class="h-4 w-4"></i>
                                    </button>
                                    <div x-show="open" x-cloak class="absolute right-0 z-20 mt-2 w-36 rounded-xl border border-white/10 bg-ss-card p-1 shadow-xl">
                                        <a href="{{ route('customers.show', $customer) }}" class="ss-nav-item">View</a>
                                        @if($canManage)
                                            <a href="{{ route('customers.edit', $customer) }}" class="ss-nav-item">Edit</a>
                                            <x-confirm-delete :action="route('customers.destroy', $customer)" title="Delete customer?" :message="'Delete '.$customer->name.'?'" class="block w-full" buttonClass="ss-nav-item w-full text-rose-300" />
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="mt-4 overflow-x-auto">{{ $customers->links() }}</div>
@endsection
