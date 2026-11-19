@php
    $receipt = $receipt ?? null;
    $existingItems = old('items', $receipt?->items?->map(fn ($i) => [
        'product_id' => $i->product_id,
        'quantity' => $i->quantity,
        'unit_cost' => (float) $i->unit_cost,
    ])->values()->all() ?? [['product_id' => '', 'quantity' => 1, 'unit_cost' => 0]]);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5"
      x-data="{
        items: @js($existingItems),
        products: @js($products->map(fn ($p) => ['id' => $p->id, 'label' => $p->sku.' — '.$p->name, 'cost' => (float) $p->cost_price])),
        addRow() { this.items.push({ product_id: '', quantity: 1, unit_cost: 0 }) },
        removeRow(i) { if (this.items.length > 1) this.items.splice(i, 1) },
        onProduct(i) {
          const p = this.products.find(x => String(x.id) === String(this.items[i].product_id));
          if (p) this.items[i].unit_cost = p.cost;
        }
      }">
    @csrf
    @if(!empty($method)) @method($method) @endif

    <div class="ss-card space-y-4 p-4 sm:p-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Warehouse <span class="text-ss-danger">*</span></label>
                <select name="warehouse_id" class="input" required>
                    <option value="">Select warehouse</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $receipt->warehouse_id ?? '') == $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Transaction date <span class="text-ss-danger">*</span></label>
                <input type="date" name="transaction_date" class="input" value="{{ old('transaction_date', optional($receipt->transaction_date ?? now())->format('Y-m-d')) }}" required>
            </div>
            <div>
                <label class="label">Reference number</label>
                <input name="reference_number" class="input" value="{{ old('reference_number', $receipt->reference_number ?? '') }}">
            </div>
            <div>
                <label class="label">Remarks</label>
                <input name="remarks" class="input" value="{{ old('remarks', $receipt->remarks ?? '') }}">
            </div>
        </div>
    </div>

    <div class="ss-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-white/10 px-4 py-3 sm:px-5">
            <h2 class="font-semibold">Products</h2>
            <button type="button" class="btn-secondary" @click="addRow()">Add line</button>
        </div>
        <div class="space-y-3 p-4 sm:p-5">
            <template x-for="(item, index) in items" :key="index">
                <div class="grid gap-3 rounded-xl border border-white/10 bg-ss-card-2 p-3 sm:grid-cols-12">
                    <div class="sm:col-span-6">
                        <label class="label">Product</label>
                        <select class="input" x-model="item.product_id" @change="onProduct(index)" :name="`items[${index}][product_id]`" required>
                            <option value="">Select product</option>
                            <template x-for="p in products" :key="p.id">
                                <option :value="p.id" x-text="p.label" :selected="String(item.product_id) === String(p.id)"></option>
                            </template>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Qty</label>
                        <input type="number" min="1" class="input" x-model.number="item.quantity" :name="`items[${index}][quantity]`" required>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="label">Unit cost</label>
                        <input type="number" step="0.0001" min="0" class="input" x-model.number="item.unit_cost" :name="`items[${index}][unit_cost]`" required>
                    </div>
                    <div class="flex items-end sm:col-span-1">
                        <button type="button" class="btn-danger w-full justify-center px-3" @click="removeRow(index)">×</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
        <button name="submit" value="0" class="btn-secondary w-full justify-center sm:w-auto">Save draft</button>
        <button name="submit" value="1" class="btn-primary w-full justify-center sm:w-auto">Save & submit</button>
        <a href="{{ route('stock-receipts.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Cancel</a>
    </div>
</form>
