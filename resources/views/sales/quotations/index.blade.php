@extends('layouts.app')
@section('title', 'Quotations')
@section('heading', 'Quotations')
@section('subheading', 'Customer quotes before sales confirmation')

@section('content')
@if(auth()->user()->canOperateOrders())
<div class="mb-5 flex justify-end"><a href="{{ route('quotations.create') }}" class="btn-primary">New quotation</a></div>
@endif
<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead><tr><th>Number</th><th>Customer</th><th>Warehouse</th><th>Person</th><th>Status</th><th>Total</th><th></th></tr></thead>
            <tbody>
                @forelse($quotations as $quotation)
                    <tr>
                        <td class="font-medium">{{ $quotation->quotation_number }}</td>
                        <td>{{ $quotation->customer?->name }}</td>
                        <td>{{ $quotation->warehouse?->name }}</td>
                        <td>{{ $quotation->salesPerson?->name ?: '—' }}</td>
                        <td><span class="badge bg-sky-400/15 text-sky-300">{{ $quotation->status }}</span></td>
                        <td>${{ number_format($quotation->total, 2) }}</td>
                        <td class="text-right"><a href="{{ route('quotations.show', $quotation) }}" class="text-teal-300">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-muted">No quotations yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $quotations->links() }}</div>
@endsection
