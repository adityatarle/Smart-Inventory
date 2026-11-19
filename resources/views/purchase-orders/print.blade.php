<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $purchaseOrder->order_number }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; color: #111; margin: 24px; }
        h1 { margin: 0 0 4px; font-size: 22px; }
        .muted { color: #555; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 13px; }
        th { background: #f5f5f5; }
        .meta { margin-top: 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 13px; }
        .totals { margin-top: 16px; text-align: right; font-size: 14px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print / Save as PDF</button>
    <h1>Purchase Order {{ $purchaseOrder->order_number }}</h1>
    <div class="muted">{{ $purchaseOrder->statusLabel() }} · {{ optional($purchaseOrder->order_date)->format('Y-m-d') }}</div>
    <div class="meta">
        <div>
            <strong>Supplier</strong><br>
            {{ $purchaseOrder->supplierDisplayName() }}<br>
            {{ $purchaseOrder->supplier?->email }}<br>
            {{ $purchaseOrder->supplier?->phone }}
        </div>
        <div>
            <strong>Warehouse</strong><br>{{ $purchaseOrder->warehouse?->name }}<br>
            <strong>Created by</strong><br>{{ $purchaseOrder->orderedBy?->name }}<br>
            <strong>Approved by</strong><br>{{ $purchaseOrder->approver?->name ?: '—' }}
        </div>
    </div>
    <table>
        <thead>
            <tr><th>SKU</th><th>Product</th><th>Qty</th><th>Unit</th><th>Disc%</th><th>Tax%</th><th>Total</th></tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $item)
                <tr>
                    <td>{{ $item->product?->sku }}</td>
                    <td>{{ $item->product?->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_cost, 4) }}</td>
                    <td>{{ number_format($item->discount_percent, 2) }}</td>
                    <td>{{ number_format($item->tax_percent, 2) }}</td>
                    <td>{{ number_format($item->lineTotal(), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="totals">
        <div>Subtotal: {{ number_format($purchaseOrder->subtotal, 2) }}</div>
        <div>Discount: {{ number_format($purchaseOrder->discount_amount, 2) }}</div>
        <div>Tax: {{ number_format($purchaseOrder->tax_amount, 2) }}</div>
        <div><strong>Grand total: {{ number_format($purchaseOrder->total, 2) }}</strong></div>
    </div>
    @if($purchaseOrder->terms)
        <p class="muted" style="margin-top:24px; white-space:pre-line;">{{ $purchaseOrder->terms }}</p>
    @endif
</body>
</html>
