<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\WarehouseStock;
use App\Support\DocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    /**
     * Post an immutable ledger entry and update warehouse balance.
     * Positive quantity = stock in, negative = stock out.
     */
    public function post(
        int $warehouseId,
        int $productId,
        int $quantityChange,
        string $type,
        ?float $unitCost = null,
        ?Model $reference = null,
        ?int $userId = null,
        ?string $notes = null,
        ?string $documentNumber = null,
        ?\DateTimeInterface $transactionDate = null,
        bool $allowNegative = false,
    ): StockMovement {
        if ($quantityChange === 0) {
            throw new InvalidArgumentException('Quantity change cannot be zero.');
        }

        return DB::transaction(function () use (
            $warehouseId,
            $productId,
            $quantityChange,
            $type,
            $unitCost,
            $reference,
            $userId,
            $notes,
            $documentNumber,
            $transactionDate,
            $allowNegative
        ) {
            $stock = WarehouseStock::query()->firstOrCreate(
                [
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                ],
                [
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'average_cost' => 0,
                ]
            );

            $stock = WarehouseStock::query()
                ->whereKey($stock->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldQty = (int) $stock->quantity;
            $oldAvg = (float) $stock->average_cost;
            $newQty = $oldQty + $quantityChange;

            if (! $allowNegative && $newQty < 0) {
                $product = Product::find($productId);
                throw new InvalidArgumentException(
                    'Insufficient stock for '.($product?->name ?? 'product').". Available: {$oldQty}."
                );
            }

            $isInbound = $quantityChange > 0;
            $absQty = abs($quantityChange);

            if ($isInbound) {
                $cost = $unitCost ?? $this->fallbackUnitCost($productId, $oldAvg);
                $newAvg = $oldQty <= 0
                    ? $cost
                    : (($oldQty * $oldAvg) + ($absQty * $cost)) / ($oldQty + $absQty);
            } else {
                $cost = $unitCost ?? $oldAvg;
                $newAvg = $newQty <= 0 ? 0.0 : $oldAvg;
            }

            $stock->update([
                'quantity' => $newQty,
                'average_cost' => round($newAvg, 4),
            ]);

            $movement = StockMovement::create([
                'document_number' => $documentNumber ?: DocumentNumber::make('MOV'),
                'transaction_date' => $transactionDate ?: now(),
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'user_id' => $userId ?? Auth::id(),
                'type' => $type,
                'direction' => $isInbound ? 'in' : 'out',
                'status' => StockMovement::STATUS_POSTED,
                'quantity' => $quantityChange,
                'quantity_in' => $isInbound ? $absQty : 0,
                'quantity_out' => $isInbound ? 0 : $absQty,
                'unit_cost' => round($cost, 4),
                'total_cost' => round($absQty * $cost, 4),
                'balance_after' => $newQty,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'notes' => $notes,
            ]);

            $this->logger->log(
                'inventory.posted',
                sprintf(
                    'Posted %s %s qty %s for product #%d in warehouse #%d (%s)',
                    $type,
                    $isInbound ? 'IN' : 'OUT',
                    $absQty,
                    $productId,
                    $warehouseId,
                    $movement->document_number
                ),
                'inventory',
                $movement,
                [
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'quantity_change' => $quantityChange,
                    'balance_after' => $newQty,
                    'unit_cost' => $cost,
                ]
            );

            // Keep stock alerts in sync without queues/Redis.
            app(StockAlertService::class)->syncForStock($warehouseId, $productId);

            return $movement->fresh(['product', 'warehouse', 'user']);
        });
    }

    public function receive(
        int $warehouseId,
        int $productId,
        int $quantity,
        ?float $unitCost = null,
        ?Model $reference = null,
        ?int $userId = null,
        ?string $notes = null,
        ?string $documentNumber = null,
        ?\DateTimeInterface $transactionDate = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Receive quantity must be positive.');
        }

        return $this->post(
            $warehouseId,
            $productId,
            $quantity,
            'in',
            $unitCost,
            $reference,
            $userId,
            $notes,
            $documentNumber,
            $transactionDate
        );
    }

    public function issue(
        int $warehouseId,
        int $productId,
        int $quantity,
        ?Model $reference = null,
        ?int $userId = null,
        ?string $notes = null,
        ?string $documentNumber = null,
        ?\DateTimeInterface $transactionDate = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Issue quantity must be positive.');
        }

        $this->ensureAvailable($warehouseId, $productId, $quantity);

        return $this->post(
            $warehouseId,
            $productId,
            -$quantity,
            'out',
            null,
            $reference,
            $userId,
            $notes,
            $documentNumber,
            $transactionDate
        );
    }

    public function transferOut(
        int $warehouseId,
        int $productId,
        int $quantity,
        ?Model $reference = null,
        ?int $userId = null,
        ?string $notes = null,
        ?string $documentNumber = null,
        ?\DateTimeInterface $transactionDate = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Transfer-out quantity must be positive.');
        }

        $this->ensureAvailable($warehouseId, $productId, $quantity);

        return $this->post(
            $warehouseId,
            $productId,
            -$quantity,
            'transfer_out',
            null,
            $reference,
            $userId,
            $notes,
            $documentNumber,
            $transactionDate
        );
    }

    public function transferIn(
        int $warehouseId,
        int $productId,
        int $quantity,
        ?float $unitCost = null,
        ?Model $reference = null,
        ?int $userId = null,
        ?string $notes = null,
        ?string $documentNumber = null,
        ?\DateTimeInterface $transactionDate = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Transfer-in quantity must be positive.');
        }

        return $this->post(
            $warehouseId,
            $productId,
            $quantity,
            'transfer_in',
            $unitCost,
            $reference,
            $userId,
            $notes,
            $documentNumber,
            $transactionDate
        );
    }

    /**
     * Post a physical-count adjustment. Positive change increases stock; negative decreases.
     */
    public function adjust(
        int $warehouseId,
        int $productId,
        int $quantityChange,
        ?float $unitCost = null,
        ?Model $reference = null,
        ?int $userId = null,
        ?string $notes = null,
        ?string $documentNumber = null,
        ?\DateTimeInterface $transactionDate = null,
    ): StockMovement {
        if ($quantityChange === 0) {
            throw new InvalidArgumentException('Adjustment quantity change cannot be zero.');
        }

        return $this->post(
            $warehouseId,
            $productId,
            $quantityChange,
            'adjust',
            $unitCost,
            $reference,
            $userId,
            $notes,
            $documentNumber,
            $transactionDate
        );
    }

    /**
     * Create a reversing ledger entry for a posted movement.
     */
    public function reverse(StockMovement $movement, ?string $notes = null, ?int $userId = null): StockMovement
    {
        if ($movement->status !== StockMovement::STATUS_POSTED) {
            throw new InvalidArgumentException('Only posted ledger entries can be reversed.');
        }

        if ($movement->type === 'reversal') {
            throw new InvalidArgumentException('Cannot reverse a reversal entry.');
        }

        return $this->post(
            (int) $movement->warehouse_id,
            (int) $movement->product_id,
            -1 * (int) $movement->quantity,
            'reversal',
            (float) $movement->unit_cost,
            $movement,
            $userId,
            $notes ?: ('Reversal of '.$movement->document_number),
            DocumentNumber::make('REV'),
            now()
        );
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

    public function inventoryValue(?int $warehouseId = null): float
    {
        $query = WarehouseStock::query();
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return (float) $query->selectRaw('COALESCE(SUM(quantity * average_cost), 0) as value')->value('value');
    }

    private function fallbackUnitCost(int $productId, float $currentAvg): float
    {
        if ($currentAvg > 0) {
            return $currentAvg;
        }

        return (float) (Product::query()->whereKey($productId)->value('cost_price') ?? 0);
    }
}
