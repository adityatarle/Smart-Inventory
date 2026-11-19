<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\WarehouseStock;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StockTransferService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly ActivityLogger $logger,
    ) {
    }

    public function createDraft(array $header, array $items): StockTransfer
    {
        $this->assertHeader($header);
        $this->assertItems($items);

        return DB::transaction(function () use ($header, $items) {
            $transfer = StockTransfer::create([
                'document_number' => DocumentNumber::make('TRF'),
                'from_warehouse_id' => $header['from_warehouse_id'],
                'to_warehouse_id' => $header['to_warehouse_id'],
                'status' => StockTransfer::STATUS_DRAFT,
                'reference_number' => $header['reference_number'] ?? null,
                'transaction_date' => $header['transaction_date'],
                'remarks' => $header['remarks'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->syncItems($transfer, $items);
            $this->logger->log('stock_transfer.created', "Created transfer {$transfer->document_number}", 'inventory', $transfer);

            return $transfer->load(['items.product', 'fromWarehouse', 'toWarehouse']);
        });
    }

    public function updateDraft(StockTransfer $transfer, array $header, array $items): StockTransfer
    {
        $this->assertDraft($transfer);
        $this->assertHeader($header);
        $this->assertItems($items);

        return DB::transaction(function () use ($transfer, $header, $items) {
            $transfer = StockTransfer::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($transfer);

            $transfer->update([
                'from_warehouse_id' => $header['from_warehouse_id'],
                'to_warehouse_id' => $header['to_warehouse_id'],
                'reference_number' => $header['reference_number'] ?? null,
                'transaction_date' => $header['transaction_date'],
                'remarks' => $header['remarks'] ?? null,
            ]);

            $transfer->items()->delete();
            $this->syncItems($transfer, $items);
            $this->logger->log('stock_transfer.updated', "Updated transfer {$transfer->document_number}", 'inventory', $transfer);

            return $transfer->load(['items.product', 'fromWarehouse', 'toWarehouse']);
        });
    }

    public function submit(StockTransfer $transfer): StockTransfer
    {
        return DB::transaction(function () use ($transfer) {
            $transfer = StockTransfer::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if ($transfer->status !== StockTransfer::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'transfer' => 'Only draft transfers can be submitted. Duplicate dispatch is not allowed.',
                ]);
            }

            $transfer->load('items');

            if ($transfer->items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Add at least one product before submitting.']);
            }

            foreach ($transfer->items as $item) {
                $stock = WarehouseStock::query()
                    ->where('warehouse_id', $transfer->from_warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                $unitCost = (float) ($stock?->average_cost ?? 0);
                $item->update(['unit_cost' => $unitCost]);

                try {
                    $this->inventory->transferOut(
                        (int) $transfer->from_warehouse_id,
                        (int) $item->product_id,
                        (int) $item->quantity,
                        $transfer,
                        Auth::id(),
                        'Transfer dispatch '.$transfer->document_number,
                        $transfer->document_number,
                        $transfer->transaction_date
                    );
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['items' => $e->getMessage()]);
                }
            }

            $transfer->update([
                'status' => StockTransfer::STATUS_IN_TRANSIT,
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
            ]);

            $this->logger->log('stock_transfer.submitted', "Dispatched transfer {$transfer->document_number}", 'inventory', $transfer);

            return $transfer->fresh(['items.product', 'fromWarehouse', 'toWarehouse', 'creator', 'submitter']);
        });
    }

    /**
     * @param  array<int, array{item_id:int, quantity:int}>  $receipts
     */
    public function receive(StockTransfer $transfer, array $receipts): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $receipts) {
            $transfer = StockTransfer::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if (! $transfer->isOpenForReceive()) {
                throw ValidationException::withMessages([
                    'transfer' => 'This transfer cannot receive stock (status: '.$transfer->status.').',
                ]);
            }

            $transfer->load('items');
            $receivedAny = false;

            foreach ($receipts as $row) {
                $qty = (int) ($row['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                /** @var StockTransferItem|null $item */
                $item = $transfer->items->firstWhere('id', (int) $row['item_id']);
                if (! $item) {
                    throw ValidationException::withMessages(['items' => 'Invalid transfer line.']);
                }

                $remaining = $item->quantityInTransit();
                if ($qty > $remaining) {
                    throw ValidationException::withMessages([
                        'items' => "Cannot receive {$qty} for product #{$item->product_id}. In transit: {$remaining}.",
                    ]);
                }

                try {
                    $this->inventory->transferIn(
                        (int) $transfer->to_warehouse_id,
                        (int) $item->product_id,
                        $qty,
                        (float) $item->unit_cost,
                        $transfer,
                        Auth::id(),
                        'Transfer receive '.$transfer->document_number,
                        $transfer->document_number,
                        now()
                    );
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['items' => $e->getMessage()]);
                }

                $item->update(['quantity_received' => $item->quantity_received + $qty]);
                $receivedAny = true;
            }

            if (! $receivedAny) {
                throw ValidationException::withMessages(['items' => 'Enter at least one quantity to receive.']);
            }

            $transfer->load('items');
            $inTransit = $transfer->totalInTransit();

            $transfer->update([
                'status' => $inTransit === 0
                    ? StockTransfer::STATUS_COMPLETED
                    : StockTransfer::STATUS_PARTIALLY_RECEIVED,
                'completed_at' => $inTransit === 0 ? now() : null,
            ]);

            $this->logger->log('stock_transfer.received', "Received against transfer {$transfer->document_number}", 'inventory', $transfer);

            return $transfer->fresh(['items.product', 'fromWarehouse', 'toWarehouse', 'creator', 'submitter']);
        });
    }

    public function cancel(StockTransfer $transfer): StockTransfer
    {
        return DB::transaction(function () use ($transfer) {
            $transfer = StockTransfer::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if ($transfer->status === StockTransfer::STATUS_CANCELLED) {
                throw ValidationException::withMessages(['transfer' => 'Transfer is already cancelled.']);
            }

            if ($transfer->status === StockTransfer::STATUS_COMPLETED) {
                throw ValidationException::withMessages(['transfer' => 'Completed transfers cannot be cancelled.']);
            }

            if ($transfer->isDraft()) {
                $transfer->update([
                    'status' => StockTransfer::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                ]);
                $this->logger->log('stock_transfer.cancelled', "Cancelled draft transfer {$transfer->document_number}", 'inventory', $transfer);

                return $transfer->fresh();
            }

            $transfer->load('items');

            // Return remaining in-transit quantity to source warehouse.
            foreach ($transfer->items as $item) {
                $remaining = $item->quantityInTransit();
                if ($remaining <= 0) {
                    continue;
                }

                try {
                    $this->inventory->transferIn(
                        (int) $transfer->from_warehouse_id,
                        (int) $item->product_id,
                        $remaining,
                        (float) $item->unit_cost,
                        $transfer,
                        Auth::id(),
                        'Transfer cancel return '.$transfer->document_number,
                        DocumentNumber::make('REV'),
                        now()
                    );
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['transfer' => $e->getMessage()]);
                }

                // Mark remaining as no longer in transit (returned, not received at dest).
                $item->update(['quantity' => $item->quantity_received]);
            }

            $transfer->update([
                'status' => StockTransfer::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            $this->logger->log('stock_transfer.cancelled', "Cancelled transfer {$transfer->document_number} (returned in-transit stock)", 'inventory', $transfer);

            return $transfer->fresh(['items.product', 'fromWarehouse', 'toWarehouse']);
        });
    }

    public function deleteDraft(StockTransfer $transfer): void
    {
        $this->assertDraft($transfer);

        DB::transaction(function () use ($transfer) {
            $number = $transfer->document_number;
            $transfer->items()->delete();
            $transfer->delete();
            $this->logger->log('stock_transfer.deleted', "Deleted draft transfer {$number}", 'inventory');
        });
    }

    private function syncItems(StockTransfer $transfer, array $items): void
    {
        foreach ($items as $item) {
            StockTransferItem::create([
                'stock_transfer_id' => $transfer->id,
                'product_id' => $item['product_id'],
                'quantity' => (int) $item['quantity'],
                'quantity_received' => 0,
                'unit_cost' => 0,
            ]);
        }
    }

    private function assertHeader(array $header): void
    {
        if ((int) $header['from_warehouse_id'] === (int) $header['to_warehouse_id']) {
            throw ValidationException::withMessages([
                'to_warehouse_id' => 'Source and destination warehouses must be different.',
            ]);
        }
    }

    private function assertItems(array $items): void
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'At least one line item is required.']);
        }
    }

    private function assertDraft(StockTransfer $transfer): void
    {
        if (! $transfer->isDraft()) {
            throw ValidationException::withMessages([
                'transfer' => 'Only draft transfers can be modified.',
            ]);
        }
    }
}
