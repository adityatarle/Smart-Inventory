@extends('layouts.app')
@section('title', 'New Blanket Order')
@section('heading', 'New Blanket Order')

@section('content')
<form method="POST" action="{{ route('blanket-orders.store') }}" class="space-y-6">
    @csrf
    <div class="card grid gap-4 p-6 sm:grid-cols-2">
        <div>
            <label class="label">Customer</label>
            <select name="customer_id" class="input" required>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Default warehouse</label>
            <select name="warehouse_id" class="input">
                <option value="">—</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Start date</label>
            <input type="date" name="start_date" class="input" value="{{ old('start_date', now()->toDateString()) }}">
        </div>
        <div>
            <label class="label">End date</label>
            <input type="date" name="end_date" class="input" value="{{ old('end_date', now()->addYear()->toDateString()) }}">
        </div>
        <div>
            <label class="label">Sales person</label>
            <select name="sales_person_id" class="input">
                <option value="">—</option>
                @foreach($persons as $person)
                    <option value="{{ $person->id }}">{{ $person->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Sales partner</label>
            <select name="sales_partner_id" class="input">
                <option value="">—</option>
                @foreach($partners as $partner)
                    <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="label">Notes</label>
            <textarea name="notes" rows="2" class="input">{{ old('notes') }}</textarea>
        </div>
    </div>
    <div class="card p-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold">Agreed quantities</h2>
            <button type="button" class="btn-secondary" onclick="addRow()">Add line</button>
        </div>
        <div id="items" class="space-y-3"></div>
    </div>
    <button class="btn-primary">Create blanket order</button>
</form>
@endsection

@push('scripts')
@include('sales.partials.line-items-script', ['products' => $products, 'qtyField' => 'agreed_quantity'])
@endpush
