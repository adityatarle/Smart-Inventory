@extends('layouts.app')
@section('title', 'Supplier-wise Purchase')
@section('heading', 'Supplier-wise Purchase Report')
@section('breadcrumb', 'SmartStock / Purchase / Reports / Supplier')
@section('content')
<a href="{{ route('purchase.reports') }}" class="btn-secondary mb-4">Hub</a>
<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead><tr><th>Supplier</th><th>Orders</th><th>Ordered value</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->supplier?->name ?: '—' }}</td>
                        <td>{{ $row->orders }}</td>
                        <td>${{ number_format($row->ordered_value, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-6 text-center text-ss-muted">No data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
