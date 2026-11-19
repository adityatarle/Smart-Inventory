@extends('layouts.app')
@section('title', 'Sales Partners')
@section('heading', 'Sales Partners')
@section('subheading', 'Channel and reseller partners')

@section('content')
<div class="mb-5 flex justify-end"><a href="{{ route('sales-partners.create') }}" class="btn-primary">Add partner</a></div>
<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead><tr><th>Code</th><th>Name</th><th>Territory</th><th>Commission</th><th>Customers</th><th>Persons</th><th></th></tr></thead>
            <tbody>
                @foreach($partners as $partner)
                    <tr>
                        <td class="font-mono text-xs">{{ $partner->code }}</td>
                        <td class="font-medium">{{ $partner->name }}</td>
                        <td>{{ $partner->territory ?: '—' }}</td>
                        <td>{{ number_format($partner->commission_rate, 2) }}%</td>
                        <td>{{ $partner->customers_count }}</td>
                        <td>{{ $partner->sales_persons_count }}</td>
                        <td class="space-x-3 text-right">
                            <a href="{{ route('sales-partners.edit', $partner) }}" class="text-teal-300">Edit</a>
                            <form action="{{ route('sales-partners.destroy', $partner) }}" method="POST" class="inline" onsubmit="return confirm('Delete partner?')">@csrf @method('DELETE')<button class="text-rose-300">Delete</button></form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $partners->links() }}</div>
@endsection
