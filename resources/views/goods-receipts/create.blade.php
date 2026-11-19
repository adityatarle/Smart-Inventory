@extends('layouts.app')
@section('title', 'New GRN')
@section('heading', 'New Goods Receipt')
@section('subheading', 'Partial or full receipt against an approved PO')
@section('breadcrumb', 'SmartStock / Purchase / GRN / Create')

@section('content')
@if(!$purchaseOrder)
    <div class="ss-card p-5">
        <label class="label">Select approved purchase order</label>
        <form method="GET" action="{{ route('goods-receipts.create') }}" class="mt-2 flex flex-col gap-3 sm:flex-row">
            <select name="purchase_order_id" class="input" required>
                <option value="">Choose PO</option>
                @foreach($openOrders as $order)
                    <option value="{{ $order->id }}">{{ $order->order_number }} — {{ $order->supplierDisplayName() }} ({{ $order->statusLabel() }})</option>
                @endforeach
            </select>
            <button class="btn-primary">Continue</button>
        </form>
        @if($openOrders->isEmpty())
            <p class="mt-3 text-sm text-ss-muted">No approved POs available for receiving.</p>
        @endif
    </div>
@else
    <form method="POST" action="{{ route('goods-receipts.store') }}" class="space-y-5"
          x-data="{
            lines: @js($purchaseOrder->items->map(fn ($i) => [
                'purchase_order_item_id' => $i->id,
                'label' => ($i->product?->sku.' — '.$i->product?->name),
                'ordered' => $i->quantity,
                'previously' => $i->received_quantity,
                'remaining' => $i->remainingQuantity(),
                'unit_cost' => (float) $i->unit_cost,
                'received_quantity' => $i->remainingQuantity(),
                'rejected_quantity' => 0,
                'accepted_quantity' => $i->remainingQuantity(),
                'batch_reference' => '',
                'remarks' => '',
            ])->values()),
            syncAccepted(i) {
              const r = Number(this.lines[i].received_quantity||0);
              const j = Number(this.lines[i].rejected_quantity||0);
              this.lines[i].accepted_quantity = Math.max(0, r - j);
            }
          }">
        @csrf
        <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">

        <div class="ss-card grid gap-4 p-5 sm:grid-cols-2">
            <div>
                <p class="text-xs text-ss-muted">Purchase order</p>
                <p class="font-semibold">{{ $purchaseOrder->order_number }}</p>
                <p class="text-sm text-ss-muted">{{ $purchaseOrder->supplierDisplayName() }}</p>
            </div>
            <div>
                <label class="label">Receiving warehouse</label>
                <select name="warehouse_id" class="input" required>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected(old('warehouse_id', $purchaseOrder->warehouse_id) == $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Receipt date</label>
                <input type="date" name="receipt_date" class="input" value="{{ old('receipt_date', now()->toDateString()) }}" required>
            </div>
            <div>
                <label class="label">Remarks</label>
                <input name="remarks" class="input" value="{{ old('remarks') }}">
            </div>
        </div>

        <div class="ss-card overflow-hidden">
            <div class="border-b border-white/10 px-5 py-4 font-semibold">Receipt lines</div>
            <div class="space-y-3 p-4">
                <template x-for="(line, index) in lines" :key="index">
                    <div class="grid gap-3 rounded-xl border border-white/10 bg-ss-card-2 p-3 lg:grid-cols-12">
                        <input type="hidden" :name="`lines[${index}][purchase_order_item_id]`" :value="line.purchase_order_item_id">
                        <div class="lg:col-span-3">
                            <label class="label">Product</label>
                            <div class="text-sm" x-text="line.label"></div>
                            <div class="text-xs text-ss-muted" x-text="`Ordered ${line.ordered} · Prev ${line.previously} · Remain ${line.remaining}`"></div>
                        </div>
                        <div class="lg:col-span-2">
                            <label class="label">Received</label>
                            <input type="number" min="0" class="input" x-model.number="line.received_quantity" @input="syncAccepted(index)" :name="`lines[${index}][received_quantity]`">
                        </div>
                        <div class="lg:col-span-2">
                            <label class="label">Rejected</label>
                            <input type="number" min="0" class="input" x-model.number="line.rejected_quantity" @input="syncAccepted(index)" :name="`lines[${index}][rejected_quantity]`">
                        </div>
                        <div class="lg:col-span-2">
                            <label class="label">Accepted</label>
                            <input type="number" min="0" class="input" x-model.number="line.accepted_quantity" :name="`lines[${index}][accepted_quantity]`">
                        </div>
                        <div class="lg:col-span-2">
                            <label class="label">Unit cost</label>
                            <input type="number" step="0.0001" min="0" class="input" x-model.number="line.unit_cost" :name="`lines[${index}][unit_cost]`">
                        </div>
                        <div class="lg:col-span-3">
                            <label class="label">Batch / reference</label>
                            <input class="input" x-model="line.batch_reference" :name="`lines[${index}][batch_reference]`">
                        </div>
                        <div class="lg:col-span-3">
                            <label class="label">Line remarks</label>
                            <input class="input" x-model="line.remarks" :name="`lines[${index}][remarks]`">
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <button name="submit" value="0" class="btn-secondary w-full justify-center sm:w-auto">Save draft</button>
            <button name="submit" value="1" class="btn-primary w-full justify-center sm:w-auto">Save & post to inventory</button>
            <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="btn-secondary w-full justify-center sm:w-auto">Back to PO</a>
        </div>
    </form>
@endif
@endsection
