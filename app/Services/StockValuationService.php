<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StockValuationService
{
    /**
     * Current WAC snapshot from warehouse_stocks.
     */
    public function currentSnapshot(?int $warehouseId = null, ?int $productId = null): Collection
    {
        return WarehouseStock::query()
            ->with(['product:id,name,sku,unit,cost_price', 'warehouse:id,name,code'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->get()
            ->map(function (WarehouseStock $stock) {
                $qty = (int) $stock->quantity;
                $avg = (float) $stock->average_cost;

                return [
                    'warehouse_id' => $stock->warehouse_id,
                    'warehouse' => $stock->warehouse?->name,
                    'product_id' => $stock->product_id,
                    'sku' => $stock->product?->sku,
                    'product' => $stock->product?->name,
                    'unit' => $stock->product?->unit,
                    'closing_qty' => $qty,
                    'average_unit_cost' => round($avg, 4),
                    'closing_value' => round($qty * $avg, 4),
                ];
            });
    }

    /**
     * Period valuation: opening / received / issued / closing with WAC closing value.
     *
     * @return array{rows: Collection, totals: array<string, float|int>}
     */
    public function periodReport(?int $warehouseId, ?int $productId, Carbon $from, Carbon $to): array
    {
        $keys = WarehouseStock::query()
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->with(['product:id,name,sku,unit', 'warehouse:id,name'])
            ->get();

        // Also include pairs that only have historical movements in range
        $movementPairs = StockMovement::query()
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->whereDate('transaction_date', '<=', $to)
            ->select('warehouse_id', 'product_id')
            ->distinct()
            ->get();

        $pairMap = [];
        foreach ($keys as $stock) {
            $pairMap[$stock->warehouse_id.':'.$stock->product_id] = $stock;
        }

        $rows = collect();

        foreach ($movementPairs as $pair) {
            $key = $pair->warehouse_id.':'.$pair->product_id;
            $stock = $pairMap[$key] ?? null;

            $openingQty = (int) StockMovement::query()
                ->where('warehouse_id', $pair->warehouse_id)
                ->where('product_id', $pair->product_id)
                ->where('transaction_date', '<', $from->copy()->startOfDay())
                ->sum('quantity');

            $period = StockMovement::query()
                ->where('warehouse_id', $pair->warehouse_id)
                ->where('product_id', $pair->product_id)
                ->whereDate('transaction_date', '>=', $from)
                ->whereDate('transaction_date', '<=', $to)
                ->selectRaw('COALESCE(SUM(quantity_in),0) as qty_in, COALESCE(SUM(quantity_out),0) as qty_out,
                    COALESCE(SUM(quantity_in * unit_cost),0) as value_in, COALESCE(SUM(quantity_out * unit_cost),0) as value_out')
                ->first();

            $receivedQty = (int) ($period->qty_in ?? 0);
            $issuedQty = (int) ($period->qty_out ?? 0);
            $receivedValue = round((float) ($period->value_in ?? 0), 4);
            $issuedValue = round((float) ($period->value_out ?? 0), 4);

            $closingQty = $openingQty + $receivedQty - $issuedQty;

            // Opening value approximation: use earliest period inbound cost or current avg
            $avgCost = (float) ($stock?->average_cost ?? 0);
            if ($avgCost <= 0 && $receivedQty > 0) {
                $avgCost = $receivedValue / $receivedQty;
            }

            $lastBefore = StockMovement::query()
                ->where('warehouse_id', $pair->warehouse_id)
                ->where('product_id', $pair->product_id)
                ->where('transaction_date', '<', $from->copy()->startOfDay())
                ->where('quantity_in', '>', 0)
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->value('unit_cost');

            $openingUnitCost = $lastBefore !== null ? (float) $lastBefore : $avgCost;
            $openingValue = round($openingQty * $openingUnitCost, 4);
            $closingValue = round($closingQty * $avgCost, 4);

            $product = $stock?->product;
            $warehouse = $stock?->warehouse;

            if (! $product) {
                $product = \App\Models\Product::find($pair->product_id);
            }
            if (! $warehouse) {
                $warehouse = \App\Models\Warehouse::find($pair->warehouse_id);
            }

            $rows->push([
                'warehouse_id' => $pair->warehouse_id,
                'warehouse' => $warehouse?->name,
                'product_id' => $pair->product_id,
                'sku' => $product?->sku,
                'product' => $product?->name,
                'opening_qty' => $openingQty,
                'opening_value' => $openingValue,
                'received_qty' => $receivedQty,
                'received_value' => $receivedValue,
                'issued_qty' => $issuedQty,
                'issued_value' => $issuedValue,
                'closing_qty' => $closingQty,
                'average_unit_cost' => round($avgCost, 4),
                'closing_value' => $closingValue,
            ]);
        }

        $rows = $rows->sortBy(['warehouse', 'sku'])->values();

        $totals = [
            'opening_qty' => (int) $rows->sum('opening_qty'),
            'opening_value' => round((float) $rows->sum('opening_value'), 4),
            'received_qty' => (int) $rows->sum('received_qty'),
            'received_value' => round((float) $rows->sum('received_value'), 4),
            'issued_qty' => (int) $rows->sum('issued_qty'),
            'issued_value' => round((float) $rows->sum('issued_value'), 4),
            'closing_qty' => (int) $rows->sum('closing_qty'),
            'closing_value' => round((float) $rows->sum('closing_value'), 4),
        ];

        return ['rows' => $rows, 'totals' => $totals];
    }

    public function totalInventoryValue(?int $warehouseId = null): float
    {
        $query = WarehouseStock::query();
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (float) $query->selectRaw('COALESCE(SUM(quantity * average_cost), 0) as value')->value('value');
    }
}
