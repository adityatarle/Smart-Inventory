<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StockInService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly ActivityLogger $logger,
    ) {
    }

    public function createDraft(array $header, array $items): StockReceipt
    {
        $this->assertItems($items);

        return DB::transaction(function () use ($header, $items) {
            $receipt = StockReceipt::create([
                'document_number' => DocumentNumber::make('RCV'),
                'warehouse_id' => $header['warehouse_id'],
                'status' => StockReceipt::STATUS_DRAFT,
                'reference_number' => $header['reference_number'] ?? null,
                'transaction_date' => $header['transaction_date'],
                'remarks' => $header['remarks'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->syncItems($receipt, $items);
            $this->logger->log('stock_in.created', "Created stock receipt {$receipt->document_number}", 'inventory', $receipt);

            return $receipt->load(['items.product', 'warehouse']);
        });
    }

    public function updateDraft(StockReceipt $receipt, array $header, array $items): StockReceipt
    {
        $this->assertDraft($receipt);
        $this->assertItems($items);

        return DB::transaction(function () use ($receipt, $header, $items) {
            $receipt = StockReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($receipt);

            $receipt->update([
                'warehouse_id' => $header['warehouse_id'],
                'reference_number' => $header['reference_number'] ?? null,
                'transaction_date' => $header['transaction_date'],
                'remarks' => $header['remarks'] ?? null,
            ]);

            $receipt->items()->delete();
            $this->syncItems($receipt, $items);
            $this->logger->log('stock_in.updated', "Updated stock receipt {$receipt->document_number}", 'inventory', $receipt);

            return $receipt->load(['items.product', 'warehouse']);
        });
    }

    public function submit(StockReceipt $receipt): StockReceipt
    {
        return DB::transaction(function () use ($receipt) {
            $receipt = StockReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            if ($receipt->status === StockReceipt::STATUS_SUBMITTED) {
                throw ValidationException::withMessages([
                    'receipt' => 'This receipt was already submitted. Duplicate posting is not allowed.',
                ]);
            }

            $this->assertDraft($receipt);
            $receipt->load('items');

            if ($receipt->items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Add at least one product before submitting.']);
            }

            foreach ($receipt->items as $item) {
                $this->inventory->receive(
                    (int) $receipt->warehouse_id,
                    (int) $item->product_id,
                    (int) $item->quantity,
                    (float) $item->unit_cost,
                    $receipt,
                    Auth::id(),
                    'Stock receipt '.$receipt->document_number,
                    $receipt->document_number,
                    $receipt->transaction_date
                );
            }

            $receipt->update([
                'status' => StockReceipt::STATUS_SUBMITTED,
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
            ]);

            $this->logger->log('stock_in.submitted', "Submitted stock receipt {$receipt->document_number}", 'inventory', $receipt);

            return $receipt->fresh(['items.product', 'warehouse', 'creator', 'submitter']);
        });
    }

    public function cancel(StockReceipt $receipt): StockReceipt
    {
        return DB::transaction(function () use ($receipt) {
            $receipt = StockReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            if ($receipt->status === StockReceipt::STATUS_CANCELLED) {
                throw ValidationException::withMessages(['receipt' => 'Receipt is already cancelled.']);
            }

            if ($receipt->isDraft()) {
                $receipt->update([
                    'status' => StockReceipt::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                ]);
                $this->logger->log('stock_in.cancelled', "Cancelled draft receipt {$receipt->document_number}", 'inventory', $receipt);

                return $receipt->fresh();
            }

            if (! $receipt->isSubmitted()) {
                throw ValidationException::withMessages(['receipt' => 'Only draft or submitted receipts can be cancelled.']);
            }

            $movements = StockMovement::query()
                ->where('reference_type', $receipt->getMorphClass())
                ->where('reference_id', $receipt->id)
                ->where('type', 'in')
                ->get();

            foreach ($movements as $movement) {
                try {
                    $this->inventory->reverse($movement, 'Cancellation of '.$receipt->document_number, Auth::id());
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['receipt' => $e->getMessage()]);
                }
            }

            $receipt->update([
                'status' => StockReceipt::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            $this->logger->log('stock_in.cancelled', "Cancelled submitted receipt {$receipt->document_number}", 'inventory', $receipt);

            return $receipt->fresh();
        });
    }

    public function deleteDraft(StockReceipt $receipt): void
    {
        $this->assertDraft($receipt);

        DB::transaction(function () use ($receipt) {
            $number = $receipt->document_number;
            $receipt->items()->delete();
            $receipt->delete();
            $this->logger->log('stock_in.deleted', "Deleted draft receipt {$number}", 'inventory');
        });
    }

    private function syncItems(StockReceipt $receipt, array $items): void
    {
        foreach ($items as $item) {
            StockReceiptItem::create([
                'stock_receipt_id' => $receipt->id,
                'product_id' => $item['product_id'],
                'quantity' => (int) $item['quantity'],
                'unit_cost' => (float) ($item['unit_cost'] ?? 0),
            ]);
        }
    }

    private function assertItems(array $items): void
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'At least one line item is required.']);
        }
    }

    private function assertDraft(StockReceipt $receipt): void
    {
        if (! $receipt->isDraft()) {
            throw ValidationException::withMessages([
                'receipt' => 'Only draft receipts can be modified.',
            ]);
        }
    }
}
