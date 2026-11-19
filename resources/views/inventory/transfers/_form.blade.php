@php
    $transfer = $transfer ?? null;
    $existingItems = old('items', $transfer?->items?->map(fn ($i) => [
        'product_id' => $i->product_id,
        'quantity' => $i->quantity,
    ])->values()->all() ?? [['product_id' => '', 'quantity' => 1]]);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-5"
      x-data="{
        items: @js($existingItems),
        products: @js($products->map(fn ($p) => ['id' => $p->id, 'label' => $p->sku.' — '.$p->name])),
        fromWarehouseId: '{{ old('from_warehouse_id', $transfer->from_warehouse_id ?? '') }}',
        availability: {},
        addRow() { this.items.push({ product_id: '', quantity: 1 }) },
        removeRow(i) { if (this.items.length > 1) this.items.splice(i, 1) },
        async loadAvailability(i) {
          if (!this.fromWarehouseId || !this.items[i].product_id) return;
          const url = @js(route('stock-transfers.availability')) + `?warehouse_id=${this.fromWarehouseId}&product_id=${this.items[i].product_id}`;
          const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
          if (!res.ok) return;
          const data = await res.json();
          this.availability[i] = data.available;
        }
      }">
    @csrf
    @if(!empty($method)) @method($method) @endif

    <div class="ss-card space-y-4 p-4 sm:p-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">From warehouse <span class="text-ss-danger">*</span></label>
                <select name="from_warehouse_id" class="input" x-model="fromWarehouseId" required>
                    <option value="">Select source</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('from_warehouse_id', $transfer->from_warehouse_id ?? '') == $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">To warehouse <span class="text-ss-danger">*</span></label>
                <select name="to_warehouse_id" class="input" required>
                    <option value="">Select destination</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('to_warehouse_id', $transfer->to_warehouse_id ?? '') == $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Transfer date <span class="text-ss-danger">*</span></label>
                <input type="date" name="transaction_date" class="input" value="{{ old('transaction_date', optional($transfer->transaction_date ?? now())->format('Y-m-d')) }}" required>
            </div>
            <div>
                <label class="label">Reference</label>
                <input name="reference_number" class="input" value="{{ old('reference_number', $transfer->reference_number ?? '') }}">
            </div>
            <div class="sm:col-span-2">
                <label class="label">Remarks</label>
                <input name="remarks" class="input" value="{{ old('remarks', $transfer->remarks ?? '') }}">
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
                    <div class="sm:col-span-7">
                        <label class="label">Product</label>
                        <select class="input" x-model="item.product_id" @change="loadAvailability(index)" :name="`items[${index}][product_id]`" required>
                            <option value="">Select product</option>
                            <template x-for="p in products" :key="p.id">
                                <option :value="p.id" x-text="p.label" :selected="String(item.product_id) === String(p.id)"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-ss-muted" x-show="availability[index] !== undefined" x-text="`Available at source: ${availability[index] ?? 0}`"></p>
                    </div>
                    <div class="sm:col-span-4">
                        <label class="label">Transfer qty</label>
                        <input type="number" min="1" class="input" x-model.number="item.quantity" :name="`items[${index}][quantity]`" required>
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
        <button name="submit" value="1" class="btn-primary w-full justify-center sm:w-auto">Save & dispatch</button>
        <a href="{{ route('stock-transfers.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Cancel</a>
    </div>
</form>
