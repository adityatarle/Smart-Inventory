<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\WarehouseStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockService
{
    public function adjust(
        int $warehouseId,
        int $productId,
        int $quantityChange,
        string $type,
        ?Model $reference = null,
        ?int $userId = null,
        ?string $notes = null
    ): WarehouseStock {
        return DB::transaction(function () use ($warehouseId, $productId, $quantityChange, $type, $reference, $userId, $notes) {
            $stock = WarehouseStock::query()->firstOrCreate(
                [
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                ],
                [
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]
            );

            $stock = WarehouseStock::query()
                ->whereKey($stock->id)
                ->lockForUpdate()
                ->firstOrFail();

            $newQuantity = $stock->quantity + $quantityChange;

            if ($newQuantity < 0) {
                throw new InvalidArgumentException('Insufficient stock for this operation.');
            }

            $stock->update(['quantity' => $newQuantity]);

            StockMovement::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'user_id' => $userId,
                'type' => $type,
                'quantity' => $quantityChange,
                'balance_after' => $newQuantity,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'notes' => $notes,
            ]);

            return $stock->fresh(['product', 'warehouse']);
        });
    }

    public function ensureAvailable(int $warehouseId, int $productId, int $quantity): void
    {
        $stock = WarehouseStock::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->first();

        $available = $stock?->availableQuantity() ?? 0;

        if ($available < $quantity) {
            $product = Product::find($productId);
            throw new InvalidArgumentException(
                'Insufficient stock for '.($product?->name ?? 'product').". Available: {$available}."
            );
        }
    }
}
