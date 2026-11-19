<?php

namespace App\Services;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\WarehouseStock;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StockAdjustmentService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly ActivityLogger $logger,
    ) {
    }

    public function createDraft(array $header, array $items): StockAdjustment
    {
        $this->assertItems($items);

        return DB::transaction(function () use ($header, $items) {
            $adjustment = StockAdjustment::create([
                'document_number' => DocumentNumber::make('ADJ'),
                'warehouse_id' => $header['warehouse_id'],
                'status' => StockAdjustment::STATUS_DRAFT,
                'reference_number' => $header['reference_number'] ?? null,
                'transaction_date' => $header['transaction_date'],
                'remarks' => $header['remarks'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->syncItems($adjustment, $items);
            $this->logger->log('stock_adjust.created', "Created stock adjustment {$adjustment->document_number}", 'inventory', $adjustment);

            return $adjustment->load(['items.product', 'warehouse']);
        });
    }

    public function updateDraft(StockAdjustment $adjustment, array $header, array $items): StockAdjustment
    {
        $this->assertDraft($adjustment);
        $this->assertItems($items);

        return DB::transaction(function () use ($adjustment, $header, $items) {
            $adjustment = StockAdjustment::query()->whereKey($adjustment->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($adjustment);

            $adjustment->update([
                'warehouse_id' => $header['warehouse_id'],
                'reference_number' => $header['reference_number'] ?? null,
                'transaction_date' => $header['transaction_date'],
                'remarks' => $header['remarks'] ?? null,
            ]);

            $adjustment->items()->delete();
            $this->syncItems($adjustment, $items);
            $this->logger->log('stock_adjust.updated', "Updated stock adjustment {$adjustment->document_number}", 'inventory', $adjustment);

            return $adjustment->load(['items.product', 'warehouse']);
        });
    }

    public function submit(StockAdjustment $adjustment): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment) {
            $adjustment = StockAdjustment::query()->whereKey($adjustment->id)->lockForUpdate()->firstOrFail();

            if ($adjustment->status === StockAdjustment::STATUS_SUBMITTED) {
                throw ValidationException::withMessages([
                    'adjustment' => 'This adjustment was already submitted. Duplicate posting is not allowed.',
                ]);
            }

            $this->assertDraft($adjustment);
            $adjustment->load('items');

            if ($adjustment->items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Add at least one product before submitting.']);
            }

            $posted = 0;

            foreach ($adjustment->items as $item) {
                $stock = WarehouseStock::query()->firstOrCreate(
                    [
                        'warehouse_id' => $adjustment->warehouse_id,
                        'product_id' => $item->product_id,
                    ],
                    [
                        'quantity' => 0,
                        'reserved_quantity' => 0,
                        'average_cost' => 0,
                    ]
                );

                $stock = WarehouseStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
                $systemQty = (int) $stock->quantity;
                $physicalQty = (int) $item->physical_quantity;
                $difference = $physicalQty - $systemQty;

                $item->update([
                    'system_quantity' => $systemQty,
                    'difference' => $difference,
                ]);

                if ($difference === 0) {
                    continue;
                }

                $reasonLabel = StockAdjustment::REASONS[$item->reason] ?? $item->reason;

                try {
                    $this->inventory->adjust(
                        (int) $adjustment->warehouse_id,
                        (int) $item->product_id,
                        $difference,
                        $difference > 0 ? (float) ($item->unit_cost ?? 0) : null,
                        $adjustment,
                        Auth::id(),
                        "{$reasonLabel} · {$adjustment->document_number}",
                        $adjustment->document_number,
                        $adjustment->transaction_date
                    );
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['items' => $e->getMessage()]);
                }

                $posted++;
            }

            if ($posted === 0) {
                throw ValidationException::withMessages([
                    'items' => 'No quantity differences to post. Physical counts match system stock.',
                ]);
            }

            $adjustment->update([
                'status' => StockAdjustment::STATUS_SUBMITTED,
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
            ]);

            $this->logger->log('stock_adjust.submitted', "Submitted stock adjustment {$adjustment->document_number}", 'inventory', $adjustment);

            return $adjustment->fresh(['items.product', 'warehouse', 'creator', 'submitter']);
        });
    }

    public function cancel(StockAdjustment $adjustment): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment) {
            $adjustment = StockAdjustment::query()->whereKey($adjustment->id)->lockForUpdate()->firstOrFail();

            if ($adjustment->status === StockAdjustment::STATUS_CANCELLED) {
                throw ValidationException::withMessages(['adjustment' => 'Adjustment is already cancelled.']);
            }

            if ($adjustment->isDraft()) {
                $adjustment->update([
                    'status' => StockAdjustment::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                ]);
                $this->logger->log('stock_adjust.cancelled', "Cancelled draft adjustment {$adjustment->document_number}", 'inventory', $adjustment);

                return $adjustment->fresh();
            }

            if (! $adjustment->isSubmitted()) {
                throw ValidationException::withMessages(['adjustment' => 'Only draft or submitted adjustments can be cancelled.']);
            }

            $movements = StockMovement::query()
                ->where('reference_type', $adjustment->getMorphClass())
                ->where('reference_id', $adjustment->id)
                ->where('type', 'adjust')
                ->get();

            foreach ($movements as $movement) {
                try {
                    $this->inventory->reverse($movement, 'Cancellation of '.$adjustment->document_number, Auth::id());
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['adjustment' => $e->getMessage()]);
                }
            }

            $adjustment->update([
                'status' => StockAdjustment::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            $this->logger->log('stock_adjust.cancelled', "Cancelled submitted adjustment {$adjustment->document_number}", 'inventory', $adjustment);

            return $adjustment->fresh();
        });
    }

    public function deleteDraft(StockAdjustment $adjustment): void
    {
        $this->assertDraft($adjustment);

        DB::transaction(function () use ($adjustment) {
            $number = $adjustment->document_number;
            $adjustment->items()->delete();
            $adjustment->delete();
            $this->logger->log('stock_adjust.deleted', "Deleted draft adjustment {$number}", 'inventory');
        });
    }

    private function syncItems(StockAdjustment $adjustment, array $items): void
    {
        foreach ($items as $item) {
            $systemQty = (int) WarehouseStock::query()
                ->where('warehouse_id', $adjustment->warehouse_id)
                ->where('product_id', $item['product_id'])
                ->value('quantity');

            $physicalQty = (int) $item['physical_quantity'];

            StockAdjustmentItem::create([
                'stock_adjustment_id' => $adjustment->id,
                'product_id' => $item['product_id'],
                'system_quantity' => $systemQty,
                'physical_quantity' => $physicalQty,
                'difference' => $physicalQty - $systemQty,
                'reason' => $item['reason'],
                'unit_cost' => isset($item['unit_cost']) ? (float) $item['unit_cost'] : null,
            ]);
        }
    }

    private function assertItems(array $items): void
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'At least one line item is required.']);
        }
    }

    private function assertDraft(StockAdjustment $adjustment): void
    {
        if (! $adjustment->isDraft()) {
            throw ValidationException::withMessages([
                'adjustment' => 'Only draft adjustments can be modified.',
            ]);
        }
    }
}
