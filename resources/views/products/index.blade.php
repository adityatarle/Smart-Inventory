@extends('layouts.app')

@section('title', 'Products')
@section('heading', 'Products')
@section('subheading', 'Catalog and stock overview')

@section('content')
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="GET" class="flex gap-2">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search SKU or name" class="input max-w-xs">
        <button class="btn-secondary">Search</button>
    </form>
    @if(auth()->user()->canManageInventory())
        <a href="{{ route('products.create') }}" class="btn-primary">Add product</a>
    @endif
</div>

<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Sell price</th>
                    <th>Total stock</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                    <tr>
                        <td class="font-mono text-xs">{{ $product->sku }}</td>
                        <td class="font-medium">{{ $product->name }}</td>
                        <td>{{ $product->category?->name ?: '—' }}</td>
                        <td>${{ number_format($product->sell_price, 2) }}</td>
                        <td>
                            <span class="{{ ($product->total_qty ?? 0) <= $product->reorder_level ? 'text-amber-300' : 'text-teal-300' }}">
                                {{ $product->total_qty ?? 0 }} {{ $product->unit }}
                            </span>
                        </td>
                        <td class="space-x-3 text-right">
                            <a href="{{ route('products.show', $product) }}" class="text-sky-300">View</a>
                            @if(auth()->user()->canManageInventory())
                                <a href="{{ route('products.edit', $product) }}" class="text-teal-300">Edit</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $products->links() }}</div>
@endsection
