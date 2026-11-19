<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\WarehouseStock;
use Illuminate\Database\Eloquent\Model;

/**
 * Backward-compatible adapter used by purchase/sales modules.
 * All writes go through InventoryService.
 */
class StockService
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    public function adjust(
        int $warehouseId,
        int $productId,
        int $quantityChange,
        string $type,
        ?Model $reference = null,
        ?int $userId = null,
        ?string $notes = null
    ): WarehouseStock {
        $movement = $this->inventory->post(
            $warehouseId,
            $productId,
            $quantityChange,
            $type,
            null,
            $reference,
            $userId,
            $notes
        );

        return WarehouseStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->with(['product', 'warehouse'])
            ->firstOrFail();
    }

    public function ensureAvailable(int $warehouseId, int $productId, int $quantity): void
    {
        $this->inventory->ensureAvailable($warehouseId, $productId, $quantity);
    }
}
