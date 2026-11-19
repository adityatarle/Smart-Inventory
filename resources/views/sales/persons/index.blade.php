@extends('layouts.app')
@section('title', 'Sales Persons')
@section('heading', 'Sales Persons')
@section('subheading', 'Internal sellers assigned to customers and deals')

@section('content')
<div class="mb-5 flex justify-end"><a href="{{ route('sales-persons.create') }}" class="btn-primary">Add sales person</a></div>
<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead><tr><th>Code</th><th>Name</th><th>Email</th><th>Partner</th><th>Commission</th><th></th></tr></thead>
            <tbody>
                @foreach($persons as $person)
                    <tr>
                        <td class="font-mono text-xs">{{ $person->code }}</td>
                        <td class="font-medium">{{ $person->name }}</td>
                        <td>{{ $person->email ?: '—' }}</td>
                        <td>{{ $person->partner?->name ?: '—' }}</td>
                        <td>{{ number_format($person->commission_rate, 2) }}%</td>
                        <td class="space-x-3 text-right">
                            <a href="{{ route('sales-persons.edit', $person) }}" class="text-teal-300">Edit</a>
                            <form action="{{ route('sales-persons.destroy', $person) }}" method="POST" class="inline" onsubmit="return confirm('Delete sales person?')">@csrf @method('DELETE')<button class="text-rose-300">Delete</button></form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $persons->links() }}</div>
@endsection
