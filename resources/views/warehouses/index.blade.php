@extends('layouts.app')

@section('title', 'Warehouses')
@section('heading', 'Warehouses')
@section('subheading', 'Manage multiple warehouse locations')

@section('content')
<div class="mb-5 flex justify-end">
    <a href="{{ route('warehouses.create') }}" class="btn-primary">Add warehouse</a>
</div>

<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>City</th>
                    <th>Stock SKUs</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($warehouses as $warehouse)
                    <tr>
                        <td class="font-medium">{{ $warehouse->name }}</td>
                        <td>{{ $warehouse->code }}</td>
                        <td>{{ $warehouse->city ?: '—' }}</td>
                        <td>{{ $warehouse->stocks_count }}</td>
                        <td>
                            <span class="badge {{ $warehouse->is_active ? 'bg-teal-400/15 text-teal-300' : 'bg-rose-400/15 text-rose-300' }}">
                                {{ $warehouse->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="space-x-3 text-right">
                            <a href="{{ route('warehouses.edit', $warehouse) }}" class="text-teal-300">Edit</a>
                            <form action="{{ route('warehouses.destroy', $warehouse) }}" method="POST" class="inline" onsubmit="return confirm('Delete warehouse?')">
                                @csrf @method('DELETE')
                                <button class="text-rose-300">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $warehouses->links() }}</div>
@endsection
