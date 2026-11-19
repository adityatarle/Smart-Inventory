<?php

namespace App\Services;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Support\AppSettings;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class GoodsReceiptService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly ActivityLogger $logger,
    ) {
    }

    public function createDraft(PurchaseOrder $order, array $header, array $lines): GoodsReceipt
    {
        $this->assertReceivable($order);

        return DB::transaction(function () use ($order, $header, $lines) {
            $order = PurchaseOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->assertReceivable($order);
            $order->load('items');

            $receipt = GoodsReceipt::create([
                'document_number' => DocumentNumber::make('GRN'),
                'purchase_order_id' => $order->id,
                'supplier_id' => $order->supplier_id,
                'warehouse_id' => $header['warehouse_id'] ?? $order->warehouse_id,
                'status' => GoodsReceipt::STATUS_DRAFT,
                'receipt_date' => $header['receipt_date'],
                'remarks' => $header['remarks'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->syncLines($receipt, $order, $lines);
            $this->logger->log('grn.created', "Created GRN {$receipt->document_number}", 'purchase_orders', $receipt);

            return $receipt->load(['items.product', 'purchaseOrder', 'warehouse', 'supplier']);
        });
    }

    public function updateDraft(GoodsReceipt $receipt, array $header, array $lines): GoodsReceipt
    {
        $this->assertDraft($receipt);

        return DB::transaction(function () use ($receipt, $header, $lines) {
            $receipt = GoodsReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($receipt);

            $order = PurchaseOrder::query()->whereKey($receipt->purchase_order_id)->lockForUpdate()->firstOrFail();
            $this->assertReceivable($order);
            $order->load('items');

            $receipt->update([
                'warehouse_id' => $header['warehouse_id'] ?? $order->warehouse_id,
                'receipt_date' => $header['receipt_date'],
                'remarks' => $header['remarks'] ?? null,
            ]);

            $receipt->items()->delete();
            $this->syncLines($receipt, $order, $lines);
            $this->logger->log('grn.updated', "Updated GRN {$receipt->document_number}", 'purchase_orders', $receipt);

            return $receipt->load(['items.product', 'purchaseOrder', 'warehouse']);
        });
    }

    public function submit(GoodsReceipt $receipt): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt) {
            $receipt = GoodsReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            if ($receipt->status === GoodsReceipt::STATUS_SUBMITTED) {
                throw ValidationException::withMessages([
                    'receipt' => 'This GRN was already submitted. Duplicate posting is not allowed.',
                ]);
            }

            $this->assertDraft($receipt);

            $order = PurchaseOrder::query()->whereKey($receipt->purchase_order_id)->lockForUpdate()->firstOrFail();
            $this->assertReceivable($order);
            $order->load('items');
            $receipt->load('items');

            if ($receipt->items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Add at least one receipt line.']);
            }

            $tolerance = AppSettings::receiveTolerancePercent();
            $posted = 0;

            foreach ($receipt->items as $line) {
                /** @var PurchaseOrderItem $poItem */
                $poItem = PurchaseOrderItem::query()->whereKey($line->purchase_order_item_id)->lockForUpdate()->firstOrFail();

                $accepted = (int) $line->accepted_quantity;
                $rejected = (int) $line->rejected_quantity;
                $received = (int) $line->received_quantity;

                if ($received !== ($accepted + $rejected)) {
                    throw ValidationException::withMessages([
                        'items' => 'Received quantity must equal accepted + rejected for each line.',
                    ]);
                }

                if ($accepted < 0 || $rejected < 0 || $received < 0) {
                    throw ValidationException::withMessages(['items' => 'Quantities cannot be negative.']);
                }

                if ($accepted === 0 && $rejected === 0) {
                    continue;
                }

                $remaining = $poItem->remainingQuantity();
                $maxAllowed = (int) floor($remaining * (1 + ($tolerance / 100)));
                if ($maxAllowed < $remaining) {
                    $maxAllowed = $remaining;
                }

                if ($accepted > $maxAllowed) {
                    throw ValidationException::withMessages([
                        'items' => "Over-receiving rejected for product #{$poItem->product_id}. Remaining {$remaining}, tolerance {$tolerance}%, attempted accepted {$accepted}.",
                    ]);
                }

                if ($accepted > 0) {
                    try {
                        $this->inventory->receive(
                            (int) $receipt->warehouse_id,
                            (int) $line->product_id,
                            $accepted,
                            (float) $line->unit_cost,
                            $receipt,
                            Auth::id(),
                            'GRN '.$receipt->document_number.' / PO '.$order->order_number,
                            $receipt->document_number,
                            $receipt->receipt_date
                        );
                    } catch (InvalidArgumentException $e) {
                        throw ValidationException::withMessages(['items' => $e->getMessage()]);
                    }
                    $posted++;
                }

                $poItem->update([
                    'received_quantity' => (int) $poItem->received_quantity + $accepted,
                    'rejected_quantity' => (int) $poItem->rejected_quantity + $rejected,
                ]);
            }

            if ($posted === 0 && $receipt->items->sum('rejected_quantity') === 0) {
                throw ValidationException::withMessages(['items' => 'Enter accepted or rejected quantities before submitting.']);
            }

            $receipt->update([
                'status' => GoodsReceipt::STATUS_SUBMITTED,
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
            ]);

            $order->refreshReceiveStatus();
            $this->logger->log('grn.submitted', "Submitted GRN {$receipt->document_number}", 'purchase_orders', $receipt);

            return $receipt->fresh(['items.product', 'purchaseOrder', 'warehouse', 'supplier', 'submitter']);
        });
    }

    public function cancelDraft(GoodsReceipt $receipt): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt) {
            $receipt = GoodsReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($receipt);

            $receipt->update([
                'status' => GoodsReceipt::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            $this->logger->log('grn.cancelled', "Cancelled draft GRN {$receipt->document_number}", 'purchase_orders', $receipt);

            return $receipt->fresh();
        });
    }

    public function deleteDraft(GoodsReceipt $receipt): void
    {
        $this->assertDraft($receipt);

        DB::transaction(function () use ($receipt) {
            $number = $receipt->document_number;
            $receipt->items()->delete();
            $receipt->delete();
            $this->logger->log('grn.deleted', "Deleted draft GRN {$number}", 'purchase_orders');
        });
    }

    private function syncLines(GoodsReceipt $receipt, PurchaseOrder $order, array $lines): void
    {
        $hasQty = false;

        foreach ($lines as $line) {
            $poItem = $order->items->firstWhere('id', (int) $line['purchase_order_item_id']);
            if (! $poItem) {
                throw ValidationException::withMessages(['items' => 'Invalid purchase order line.']);
            }

            $received = (int) ($line['received_quantity'] ?? 0);
            $rejected = (int) ($line['rejected_quantity'] ?? 0);
            $accepted = (int) ($line['accepted_quantity'] ?? max(0, $received - $rejected));

            if ($received === 0 && $rejected === 0 && $accepted === 0) {
                continue;
            }

            if ($received === 0) {
                $received = $accepted + $rejected;
            }

            $hasQty = true;

            GoodsReceiptItem::create([
                'goods_receipt_id' => $receipt->id,
                'purchase_order_item_id' => $poItem->id,
                'product_id' => $poItem->product_id,
                'ordered_quantity' => (int) $poItem->quantity,
                'previously_received' => (int) $poItem->received_quantity,
                'received_quantity' => $received,
                'rejected_quantity' => $rejected,
                'accepted_quantity' => $accepted,
                'unit_cost' => (float) ($line['unit_cost'] ?? $poItem->unit_cost),
                'batch_reference' => $line['batch_reference'] ?? null,
                'remarks' => $line['remarks'] ?? null,
            ]);
        }

        if (! $hasQty) {
            throw ValidationException::withMessages(['items' => 'Enter at least one received quantity.']);
        }
    }

    private function assertReceivable(PurchaseOrder $order): void
    {
        if (! $order->canReceiveGoods()) {
            throw ValidationException::withMessages([
                'order' => 'Goods can only be received against approved or partially received purchase orders.',
            ]);
        }
    }

    private function assertDraft(GoodsReceipt $receipt): void
    {
        if (! $receipt->isDraft()) {
            throw ValidationException::withMessages(['receipt' => 'Only draft GRNs can be modified.']);
        }
    }
}
