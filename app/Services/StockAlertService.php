<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Product;
use App\Models\StockAlert;
use App\Models\User;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;

class StockAlertService
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    /**
     * Re-evaluate alerts for a single warehouse/product pair after a stock post.
     */
    public function syncForStock(int $warehouseId, int $productId): void
    {
        $stock = WarehouseStock::query()
            ->with('product')
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->first();

        $qty = (int) ($stock?->quantity ?? 0);
        $product = $stock?->product ?? Product::find($productId);
        if (! $product) {
            return;
        }

        $reorder = (int) $product->reorder_level;
        $max = $product->max_stock_level !== null ? (int) $product->max_stock_level : null;

        $this->upsertCondition(
            $warehouseId,
            $productId,
            StockAlert::TYPE_OUT,
            $qty <= 0,
            'critical',
            'Out of stock',
            "{$product->name} is out of stock.",
            $qty,
            0
        );

        $this->upsertCondition(
            $warehouseId,
            $productId,
            StockAlert::TYPE_LOW,
            $qty > 0 && $qty <= $reorder,
            'warning',
            'Low stock',
            "{$product->name} is at or below reorder level ({$reorder}). Available: {$qty}.",
            $qty,
            $reorder
        );

        $this->upsertCondition(
            $warehouseId,
            $productId,
            StockAlert::TYPE_REORDER,
            $qty > 0 && $qty <= $reorder,
            'warning',
            'Reorder suggested',
            "Reorder {$product->name}. On hand {$qty}, reorder level {$reorder}.",
            $qty,
            $reorder
        );

        $this->upsertCondition(
            $warehouseId,
            $productId,
            StockAlert::TYPE_EXCESS,
            $max !== null && $max > 0 && $qty > $max,
            'info',
            'Excess stock',
            "{$product->name} exceeds max stock level ({$max}). On hand: {$qty}.",
            $qty,
            $max
        );
    }

    public function scanAll(?int $warehouseId = null): int
    {
        $touched = 0;

        WarehouseStock::query()
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->orderBy('id')
            ->chunkById(100, function ($stocks) use (&$touched) {
                foreach ($stocks as $stock) {
                    $this->syncForStock((int) $stock->warehouse_id, (int) $stock->product_id);
                    $touched++;
                }
            });

        // Also scan products with zero rows? Skip — only known warehouse stocks.

        $this->logger->log('stock_alerts.scanned', "Scanned {$touched} stock rows for alerts", 'inventory');

        return $touched;
    }

    public function openCounts(): array
    {
        $rows = StockAlert::query()
            ->open()
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        return [
            'low_stock' => (int) ($rows[StockAlert::TYPE_LOW] ?? 0),
            'out_of_stock' => (int) ($rows[StockAlert::TYPE_OUT] ?? 0),
            'reorder' => (int) ($rows[StockAlert::TYPE_REORDER] ?? 0),
            'excess' => (int) ($rows[StockAlert::TYPE_EXCESS] ?? 0),
            'total' => (int) $rows->sum(),
        ];
    }

    private function upsertCondition(
        int $warehouseId,
        int $productId,
        string $type,
        bool $active,
        string $severity,
        string $title,
        string $message,
        int $quantity,
        ?int $threshold,
    ): void {
        $open = StockAlert::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('type', $type)
            ->open()
            ->first();

        if ($active) {
            if ($open) {
                $open->update([
                    'severity' => $severity,
                    'title' => $title,
                    'message' => $message,
                    'quantity' => $quantity,
                    'threshold' => $threshold,
                ]);

                return;
            }

            $alert = StockAlert::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'type' => $type,
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
                'quantity' => $quantity,
                'threshold' => $threshold,
            ]);

            $this->notifyManagers($alert);

            return;
        }

        if ($open) {
            $open->resolve();
        }
    }

    private function notifyManagers(StockAlert $alert): void
    {
        $users = User::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereIn('role', ['admin', 'manager', 'super_admin', 'storekeeper'])
                    ->orWhereHas('roles', fn ($r) => $r->whereIn('slug', ['super_admin', 'admin', 'manager', 'storekeeper']));
            })
            ->limit(20)
            ->get(['id']);

        foreach ($users as $user) {
            AppNotification::create([
                'user_id' => $user->id,
                'title' => $alert->title,
                'message' => $alert->message,
                'type' => $alert->severity === 'critical' ? 'danger' : $alert->severity,
                'link' => '/inventory/alerts',
            ]);
        }
    }
}
