@extends('layouts.app')

@section('title', 'Live Stock')
@section('heading', 'Real-time Stock Tracking')
@section('subheading', 'Auto-refreshes every 5 seconds')

@section('content')
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <form method="GET" class="flex gap-2">
        <select name="warehouse_id" class="input max-w-xs" onchange="this.form.submit()">
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected($warehouseId == $warehouse->id)>{{ $warehouse->name }}</option>
            @endforeach
        </select>
    </form>
    <div class="flex items-center gap-3">
        <span id="live-indicator" class="badge bg-teal-400/15 text-teal-300">Live · updating…</span>
        @if(auth()->user()->canManageInventory())
            <a href="{{ route('stock.adjust') }}" class="btn-primary">Adjust stock</a>
        @endif
    </div>
</div>

<div class="grid gap-6 xl:grid-cols-3">
    <div class="card overflow-hidden xl:col-span-2">
        <div class="border-b border-line px-5 py-4 font-semibold">Current levels</div>
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Available</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody id="stock-body">
                    @foreach($stocks as $stock)
                        <tr class="{{ $stock->quantity <= $stock->product?->reorder_level ? 'bg-amber-400/5' : '' }}">
                            <td class="font-mono text-xs">{{ $stock->product?->sku }}</td>
                            <td>{{ $stock->product?->name }}</td>
                            <td class="font-semibold">{{ $stock->quantity }} {{ $stock->product?->unit }}</td>
                            <td>{{ $stock->availableQuantity() }}</td>
                            <td class="text-muted">{{ $stock->updated_at?->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $stocks->links() }}</div>
    </div>

    <div class="card p-5">
        <h2 class="mb-4 font-semibold">Latest movements</h2>
        <div id="movement-list" class="space-y-3">
            @foreach($movements as $movement)
                <div class="rounded-xl border border-line bg-panel-2/50 px-3 py-2.5">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium">{{ $movement->product?->name }}</div>
                        <div class="text-sm font-semibold {{ $movement->quantity >= 0 ? 'text-teal-300' : 'text-rose-300' }}">
                            {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                        </div>
                    </div>
                    <div class="mt-1 text-xs text-muted">{{ strtoupper($movement->type) }} · {{ $movement->created_at?->diffForHumans() }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const warehouseId = @json($warehouseId);
    const stockBody = document.getElementById('stock-body');
    const movementList = document.getElementById('movement-list');
    const indicator = document.getElementById('live-indicator');
    const url = @json(route('stock.live')) + (warehouseId ? `?warehouse_id=${warehouseId}` : '');

    async function refresh() {
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Failed');
            const data = await res.json();

            stockBody.innerHTML = data.stocks.map(stock => `
                <tr class="${stock.is_low ? 'bg-amber-400/5' : ''}">
                    <td class="font-mono text-xs">${stock.sku ?? ''}</td>
                    <td>${stock.product ?? ''}</td>
                    <td class="font-semibold">${stock.quantity} ${stock.unit ?? ''}</td>
                    <td>${stock.available}</td>
                    <td class="text-muted">${stock.updated_at ?? ''}</td>
                </tr>
            `).join('') || `<tr><td colspan="5" class="py-8 text-center text-muted">No stock rows.</td></tr>`;

            movementList.innerHTML = data.movements.map(m => `
                <div class="rounded-xl border border-line bg-panel-2/50 px-3 py-2.5">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium">${m.product ?? ''}</div>
                        <div class="text-sm font-semibold ${m.quantity >= 0 ? 'text-teal-300' : 'text-rose-300'}">
                            ${m.quantity > 0 ? '+' : ''}${m.quantity}
                        </div>
                    </div>
                    <div class="mt-1 text-xs text-muted">${(m.type || '').toUpperCase()} · ${m.created_at ?? ''}</div>
                </div>
            `).join('') || `<p class="text-sm text-muted">No movements yet.</p>`;

            indicator.textContent = `Live · ${data.refreshed_at}`;
            indicator.className = 'badge bg-teal-400/15 text-teal-300';
        } catch (e) {
            indicator.textContent = 'Live · connection issue';
            indicator.className = 'badge bg-rose-400/15 text-rose-300';
        }
    }

    setInterval(refresh, 5000);
})();
</script>
@endpush
