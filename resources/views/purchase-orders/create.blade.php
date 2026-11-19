@extends('layouts.app')

@section('title', 'New Purchase Order')
@section('heading', 'New Purchase Order')

@section('content')
<form method="POST" action="{{ route('purchase-orders.store') }}" class="space-y-6" id="po-form">
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
        <div>
            <label class="label">Supplier</label>
            <input name="supplier_name" class="input" value="{{ old('supplier_name') }}" required>
        </div>
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

    <button class="btn-primary">Create purchase order</button>
</form>
@endsection

@push('scripts')
<script>
const products = @json($products->map(fn($p) => ['id' => $p->id, 'label' => $p->sku.' — '.$p->name, 'cost' => $p->cost_price]));
const itemsEl = document.getElementById('items');
let index = 0;

function addRow(productId = '', qty = 1, cost = '') {
    const options = products.map(p => `<option value="${p.id}" ${String(p.id) === String(productId) ? 'selected' : ''}>${p.label}</option>`).join('');
    const row = document.createElement('div');
    row.className = 'grid gap-3 rounded-xl border border-line bg-panel-2/40 p-3 sm:grid-cols-12';
    row.innerHTML = `
        <div class="sm:col-span-6">
            <label class="label">Product</label>
            <select name="items[${index}][product_id]" class="input product-select" required>${options}</select>
        </div>
        <div class="sm:col-span-2">
            <label class="label">Qty</label>
            <input type="number" min="1" name="items[${index}][quantity]" class="input" value="${qty}" required>
        </div>
        <div class="sm:col-span-3">
            <label class="label">Unit cost</label>
            <input type="number" step="0.01" min="0" name="items[${index}][unit_cost]" class="input unit-cost" value="${cost}" required>
        </div>
        <div class="flex items-end sm:col-span-1">
            <button type="button" class="btn-danger w-full" onclick="this.closest('div.grid').remove()">×</button>
        </div>`;
    itemsEl.appendChild(row);
    const select = row.querySelector('.product-select');
    const costInput = row.querySelector('.unit-cost');
    select.addEventListener('change', () => {
        const found = products.find(p => String(p.id) === select.value);
        if (found && !costInput.value) costInput.value = found.cost;
    });
    select.dispatchEvent(new Event('change'));
    index++;
}
addRow();
</script>
@endpush
