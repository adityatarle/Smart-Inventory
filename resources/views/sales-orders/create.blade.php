@extends('layouts.app')

@section('title', 'New Sales Order')
@section('heading', 'New Sales Order')

@section('content')
<form method="POST" action="{{ route('sales-orders.store') }}" class="space-y-6">
    @csrf
    <div class="card grid gap-4 p-6 sm:grid-cols-2">
        <div>
            <label class="label">Warehouse</label>
            <select name="warehouse_id" class="input" required>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>
        @if($isCustomer)
            <div>
                <label class="label">Customer</label>
                <input class="input" value="{{ auth()->user()->name }}" disabled>
                <input type="hidden" name="customer_name" value="{{ auth()->user()->name }}">
            </div>
        @else
            <div>
                <label class="label">Customer account (optional)</label>
                <select name="customer_id" id="customer_id" class="input">
                    <option value="">Walk-in / manual</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" data-name="{{ $customer->name }}">{{ $customer->name }} ({{ $customer->email }})</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="label">Customer name</label>
                <input name="customer_name" id="customer_name" class="input" value="{{ old('customer_name') }}" required>
            </div>
        @endif
        <div class="sm:col-span-2">
            <label class="label">Notes</label>
            <textarea name="notes" rows="2" class="input">{{ old('notes') }}</textarea>
        </div>
    </div>

    <div class="card p-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-semibold">Line items</h2>
            <button type="button" class="btn-secondary" onclick="addRow()">Add line</button>
        </div>
        <div id="items" class="space-y-3"></div>
    </div>

    <button class="btn-primary">Create sales order</button>
</form>
@endsection

@push('scripts')
<script>
const products = @json($products->map(fn($p) => ['id' => $p->id, 'label' => $p->sku.' — '.$p->name, 'price' => $p->sell_price]));
const itemsEl = document.getElementById('items');
let index = 0;

function addRow() {
    const options = products.map(p => `<option value="${p.id}">${p.label}</option>`).join('');
    const row = document.createElement('div');
    row.className = 'grid gap-3 rounded-xl border border-line bg-panel-2/40 p-3 sm:grid-cols-12';
    row.innerHTML = `
        <div class="sm:col-span-6">
            <label class="label">Product</label>
            <select name="items[${index}][product_id]" class="input product-select" required>${options}</select>
        </div>
        <div class="sm:col-span-2">
            <label class="label">Qty</label>
            <input type="number" min="1" name="items[${index}][quantity]" class="input" value="1" required>
        </div>
        <div class="sm:col-span-3">
            <label class="label">Unit price</label>
            <input type="number" step="0.01" min="0" name="items[${index}][unit_price]" class="input unit-price" required>
        </div>
        <div class="flex items-end sm:col-span-1">
            <button type="button" class="btn-danger w-full" onclick="this.closest('div.grid').remove()">×</button>
        </div>`;
    itemsEl.appendChild(row);
    const select = row.querySelector('.product-select');
    const priceInput = row.querySelector('.unit-price');
    select.addEventListener('change', () => {
        const found = products.find(p => String(p.id) === select.value);
        if (found) priceInput.value = found.price;
    });
    select.dispatchEvent(new Event('change'));
    index++;
}
addRow();

const customerSelect = document.getElementById('customer_id');
const customerName = document.getElementById('customer_name');
if (customerSelect && customerName) {
    customerSelect.addEventListener('change', () => {
        const option = customerSelect.selectedOptions[0];
        if (option?.dataset?.name) customerName.value = option.dataset.name;
    });
}
</script>
@endpush
