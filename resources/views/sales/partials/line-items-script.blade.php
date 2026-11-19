@php
    $qtyField = $qtyField ?? 'quantity';
@endphp
<script>
const products = @json($products->map(fn($p) => ['id' => $p->id, 'label' => $p->sku.' — '.$p->name, 'price' => $p->sell_price]));
const itemsEl = document.getElementById('items');
const qtyField = @json($qtyField);
let index = 0;

function addRow(preset = {}) {
    const options = products.map(p => `<option value="${p.id}" ${String(p.id) === String(preset.product_id || '') ? 'selected' : ''}>${p.label}</option>`).join('');
    const row = document.createElement('div');
    row.className = 'grid gap-3 rounded-xl border border-line bg-panel-2/40 p-3 sm:grid-cols-12';
    row.innerHTML = `
        <div class="sm:col-span-6">
            <label class="label">Product</label>
            <select name="items[${index}][product_id]" class="input product-select" required>${options}</select>
        </div>
        <div class="sm:col-span-2">
            <label class="label">Qty</label>
            <input type="number" min="1" name="items[${index}][${qtyField}]" class="input" value="${preset.quantity || 1}" required>
        </div>
        <div class="sm:col-span-3">
            <label class="label">Unit price</label>
            <input type="number" step="0.01" min="0" name="items[${index}][unit_price]" class="input unit-price" value="${preset.unit_price || ''}" required>
        </div>
        <div class="flex items-end sm:col-span-1">
            <button type="button" class="btn-danger w-full" onclick="this.closest('div.grid').remove()">×</button>
        </div>`;
    itemsEl.appendChild(row);
    const select = row.querySelector('.product-select');
    const priceInput = row.querySelector('.unit-price');
    select.addEventListener('change', () => {
        const found = products.find(p => String(p.id) === select.value);
        if (found && !priceInput.value) priceInput.value = found.price;
    });
    if (!priceInput.value) select.dispatchEvent(new Event('change'));
    index++;
}
addRow();
</script>
