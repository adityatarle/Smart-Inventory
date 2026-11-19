@php
    $adjustment = $adjustment ?? null;
    $existingItems = old('items', $adjustment?->items?->map(fn ($i) => [
        'product_id' => $i->product_id,
        'physical_quantity' => $i->physical_quantity,
        'reason' => $i->reason,
        'unit_cost' => $i->unit_cost !== null ? (float) $i->unit_cost : null,
        'system_quantity' => $i->system_quantity,
    ])->values()->all() ?? [['product_id' => '', 'physical_quantity' => 0, 'reason' => 'count_variance', 'unit_cost' => null, 'system_quantity' => 0]]);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5"
      x-data="{
        items: @js($existingItems),
        products: @js($products->map(fn ($p) => ['id' => $p->id, 'label' => $p->sku.' — '.$p->name, 'cost' => (float) $p->cost_price])),
        reasons: @js($reasons),
        warehouseId: '{{ old('warehouse_id', $adjustment->warehouse_id ?? '') }}',
        addRow() { this.items.push({ product_id: '', physical_quantity: 0, reason: 'count_variance', unit_cost: null, system_quantity: 0 }) },
        removeRow(i) { if (this.items.length > 1) this.items.splice(i, 1) },
        diff(item) { return Number(item.physical_quantity || 0) - Number(item.system_quantity || 0) },
        async loadSystem(i) {
          if (!this.warehouseId || !this.items[i].product_id) return;
          const url = @js(route('stock-adjustments.system-quantity')) + `?warehouse_id=${this.warehouseId}&product_id=${this.items[i].product_id}`;
          const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
          if (!res.ok) return;
          const data = await res.json();
          this.items[i].system_quantity = data.system_quantity;
          if (this.items[i].unit_cost === null || this.items[i].unit_cost === '') {
            const p = this.products.find(x => String(x.id) === String(this.items[i].product_id));
            this.items[i].unit_cost = data.average_cost || (p ? p.cost : 0);
          }
        },
        async refreshAll() {
          for (let i = 0; i < this.items.length; i++) await this.loadSystem(i);
        }
      }"
      x-init="refreshAll()">
    @csrf
    @if(!empty($method)) @method($method) @endif

    <div class="ss-card space-y-4 p-4 sm:p-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Warehouse <span class="text-ss-danger">*</span></label>
                <select name="warehouse_id" class="input" x-model="warehouseId" @change="refreshAll()" required>
                    <option value="">Select warehouse</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $adjustment->warehouse_id ?? '') == $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Adjustment date <span class="text-ss-danger">*</span></label>
                <input type="date" name="transaction_date" class="input" value="{{ old('transaction_date', optional($adjustment->transaction_date ?? now())->format('Y-m-d')) }}" required>
            </div>
            <div>
                <label class="label">Reference</label>
                <input name="reference_number" class="input" value="{{ old('reference_number', $adjustment->reference_number ?? '') }}">
            </div>
            <div>
                <label class="label">Remarks</label>
                <input name="remarks" class="input" value="{{ old('remarks', $adjustment->remarks ?? '') }}">
            </div>
        </div>
    </div>

    <div class="ss-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-white/10 px-4 py-3 sm:px-5">
            <h2 class="font-semibold">Count lines</h2>
            <button type="button" class="btn-secondary" @click="addRow()">Add line</button>
        </div>
        <div class="space-y-3 p-4 sm:p-5">
            <template x-for="(item, index) in items" :key="index">
                <div class="grid gap-3 rounded-xl border border-white/10 bg-ss-card-2 p-3 sm:grid-cols-12">
                    <div class="sm:col-span-4">
                        <label class="label">Product</label>
                        <select class="input" x-model="item.product_id" @change="loadSystem(index)" :name="`items[${index}][product_id]`" required>
                            <option value="">Select product</option>
                            <template x-for="p in products" :key="p.id">
                                <option :value="p.id" x-text="p.label" :selected="String(item.product_id) === String(p.id)"></option>
                            </template>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">System qty</label>
                        <input type="number" class="input" x-model.number="item.system_quantity" readonly>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Physical qty</label>
                        <input type="number" min="0" class="input" x-model.number="item.physical_quantity" :name="`items[${index}][physical_quantity]`" required>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Difference</label>
                        <input type="text" class="input" :value="(diff(item) > 0 ? '+' : '') + diff(item)" readonly>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="label">Reason</label>
                        <select class="input" x-model="item.reason" :name="`items[${index}][reason]`" required>
                            <template x-for="(label, key) in reasons" :key="key">
                                <option :value="key" x-text="label" :selected="item.reason === key"></option>
                            </template>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Unit cost (+)</label>
                        <input type="number" step="0.0001" min="0" class="input" x-model.number="item.unit_cost" :name="`items[${index}][unit_cost]`">
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
        <a href="{{ route('stock-adjustments.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Cancel</a>
    </div>
</form>
