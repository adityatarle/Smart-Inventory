@extends('layouts.app')

@section('title', 'Customers')
@section('heading', 'Customers')
@section('subheading', 'Customer master for the sales module')

@section('content')
@if(auth()->user()->canOperateOrders())
<div class="mb-5 flex justify-end">
    <a href="{{ route('customers.create') }}" class="btn-primary">Add customer</a>
</div>
@endif

<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Sales Person</th>
                    <th>Partner</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td class="font-mono text-xs">{{ $customer->code }}</td>
                        <td class="font-medium">{{ $customer->name }}</td>
                        <td>{{ $customer->company ?: '—' }}</td>
                        <td>{{ $customer->salesPerson?->name ?: '—' }}</td>
                        <td>{{ $customer->partner?->name ?: '—' }}</td>
                        <td><span class="badge {{ $customer->is_active ? 'bg-teal-400/15 text-teal-300' : 'bg-rose-400/15 text-rose-300' }}">{{ $customer->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="space-x-3 text-right">
                            <a href="{{ route('customers.show', $customer) }}" class="text-sky-300">View</a>
                            @if(auth()->user()->canOperateOrders())
                                <a href="{{ route('customers.edit', $customer) }}" class="text-teal-300">Edit</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-muted">No customers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $customers->links() }}</div>
@endsection
