@php
    $po = $purchaseOrder ?? null;
    $existingItems = old('items', $po?->items?->map(fn ($i) => [
        'product_id' => $i->product_id,
        'quantity' => $i->quantity,
        'unit_cost' => (float) $i->unit_cost,
        'discount_percent' => (float) $i->discount_percent,
        'tax_percent' => (float) $i->tax_percent,
    ])->values()->all() ?? [['product_id' => '', 'quantity' => 1, 'unit_cost' => 0, 'discount_percent' => 0, 'tax_percent' => 0]]);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5"
      x-data="{
        items: @js($existingItems),
        products: @js($products->map(fn ($p) => ['id' => $p->id, 'label' => $p->sku.' — '.$p->name, 'cost' => (float) $p->cost_price])),
        addRow() { this.items.push({ product_id: '', quantity: 1, unit_cost: 0, discount_percent: 0, tax_percent: 0 }) },
        removeRow(i) { if (this.items.length > 1) this.items.splice(i, 1) },
        onProduct(i) {
          const p = this.products.find(x => String(x.id) === String(this.items[i].product_id));
          if (p) this.items[i].unit_cost = p.cost;
        },
        lineTotal(item) {
          const gross = Number(item.quantity||0) * Number(item.unit_cost||0);
          const disc = gross * (Number(item.discount_percent||0)/100);
          const sub = gross - disc;
          const tax = sub * (Number(item.tax_percent||0)/100);
          return (sub + tax).toFixed(2);
        },
        grandTotal() {
          return this.items.reduce((s, item) => s + Number(this.lineTotal(item)), 0).toFixed(2);
        }
      }">
    @csrf
    @if(!empty($method)) @method($method) @endif

    <div class="ss-card space-y-4 p-4 sm:p-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="label">Supplier <span class="text-ss-danger">*</span></label>
                <select name="supplier_id" class="input" required>
                    <option value="">Select supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id', $po->supplier_id ?? '') == $supplier->id)>{{ $supplier->code }} — {{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Warehouse <span class="text-ss-danger">*</span></label>
                <select name="warehouse_id" class="input" required>
                    <option value="">Select warehouse</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $po->warehouse_id ?? '') == $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Order date <span class="text-ss-danger">*</span></label>
                <input type="date" name="order_date" class="input" value="{{ old('order_date', optional($po->order_date ?? now())->format('Y-m-d')) }}" required>
            </div>
            <div>
                <label class="label">Expected delivery</label>
                <input type="date" name="expected_delivery_date" class="input" value="{{ old('expected_delivery_date', optional($po->expected_delivery_date ?? null)?->format('Y-m-d')) }}">
            </div>
            <div class="sm:col-span-2">
                <label class="label">Notes</label>
                <input name="notes" class="input" value="{{ old('notes', $po->notes ?? '') }}">
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <label class="label">Terms & conditions</label>
                <textarea name="terms" rows="2" class="input">{{ old('terms', $po->terms ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="ss-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-white/10 px-4 py-3 sm:px-5">
            <h2 class="font-semibold">Line items</h2>
            <button type="button" class="btn-secondary" @click="addRow()">Add line</button>
        </div>
        <div class="space-y-3 p-4 sm:p-5">
            <template x-for="(item, index) in items" :key="index">
                <div class="grid gap-3 rounded-xl border border-white/10 bg-ss-card-2 p-3 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <label class="label">Product</label>
                        <select class="input" x-model="item.product_id" @change="onProduct(index)" :name="`items[${index}][product_id]`" required>
                            <option value="">Select</option>
                            <template x-for="p in products" :key="p.id">
                                <option :value="p.id" x-text="p.label" :selected="String(item.product_id) === String(p.id)"></option>
                            </template>
                        </select>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="label">Qty</label>
                        <input type="number" min="1" class="input" x-model.number="item.quantity" :name="`items[${index}][quantity]`" required>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="label">Unit price</label>
                        <input type="number" step="0.0001" min="0" class="input" x-model.number="item.unit_cost" :name="`items[${index}][unit_cost]`" required>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="label">Disc %</label>
                        <input type="number" step="0.01" min="0" max="100" class="input" x-model.number="item.discount_percent" :name="`items[${index}][discount_percent]`">
                    </div>
                    <div class="lg:col-span-1">
                        <label class="label">Tax %</label>
                        <input type="number" step="0.01" min="0" max="100" class="input" x-model.number="item.tax_percent" :name="`items[${index}][tax_percent]`">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="label">Line total</label>
                        <input type="text" class="input" :value="lineTotal(item)" readonly>
                    </div>
                    <div class="flex items-end lg:col-span-1">
                        <button type="button" class="btn-danger w-full justify-center px-3" @click="removeRow(index)">×</button>
                    </div>
                </div>
            </template>
            <div class="text-right text-sm font-semibold">Grand total: $<span x-text="grandTotal()"></span></div>
        </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
        <button name="submit" value="0" class="btn-secondary w-full justify-center sm:w-auto">Save draft</button>
        <button name="submit" value="1" class="btn-primary w-full justify-center sm:w-auto">Save & submit</button>
        <a href="{{ route('purchase-orders.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Cancel</a>
    </div>
</form>
