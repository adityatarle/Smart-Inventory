@extends('layouts.app')

@section('title', 'Adjust Stock')
@section('heading', 'Manual Stock Adjustment')
@section('subheading', 'Use positive for inbound, negative for outbound')

@section('content')
<form method="POST" action="{{ route('stock.adjust.store') }}" class="card max-w-xl space-y-4 p-6">
    @csrf
    <div>
        <label class="label">Warehouse</label>
        <select name="warehouse_id" class="input" required>
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Product</label>
        <select name="product_id" class="input" required>
            @foreach($products as $product)
                <option value="{{ $product->id }}">{{ $product->sku }} — {{ $product->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Quantity change</label>
        <input type="number" name="quantity" class="input" required placeholder="e.g. 10 or -5">
    </div>
    <div>
        <label class="label">Notes</label>
        <textarea name="notes" rows="3" class="input"></textarea>
    </div>
    <div class="flex gap-3">
        <button class="btn-primary">Apply adjustment</button>
        <a href="{{ route('stock.index') }}" class="btn-secondary">Cancel</a>
    </div>
</form>
@endsection
