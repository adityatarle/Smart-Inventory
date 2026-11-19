<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function __construct(
        private readonly ActivityLogger $logger,
        private readonly SupplierService $suppliers,
    ) {
    }

    public function createDraft(array $header, array $items): PurchaseOrder
    {
        $this->assertItems($items);
        $supplier = Supplier::query()->findOrFail($header['supplier_id']);

        return DB::transaction(function () use ($header, $items, $supplier) {
            $totals = $this->calculateTotals($items);

            $order = PurchaseOrder::create([
                'order_number' => DocumentNumber::make('PO'),
                'warehouse_id' => $header['warehouse_id'],
                'ordered_by' => Auth::id(),
                'supplier_id' => $supplier->id,
                'supplier_name' => $this->suppliers->displayName($supplier),
                'order_date' => $header['order_date'],
                'expected_delivery_date' => $header['expected_delivery_date'] ?? null,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'notes' => $header['notes'] ?? null,
                'terms' => $header['terms'] ?? null,
            ]);

            $this->syncItems($order, $items);
            $this->logger->log('purchase_order.created', "Created PO {$order->order_number}", 'purchase_orders', $order);

            return $order->load(['items.product', 'supplier', 'warehouse']);
        });
    }

    public function updateDraft(PurchaseOrder $order, array $header, array $items): PurchaseOrder
    {
        $this->assertDraftEditable($order);
        $this->assertItems($items);
        $supplier = Supplier::query()->findOrFail($header['supplier_id']);

        return DB::transaction(function () use ($order, $header, $items, $supplier) {
            $order = PurchaseOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->assertDraftEditable($order);

            $totals = $this->calculateTotals($items);

            $order->update([
                'warehouse_id' => $header['warehouse_id'],
                'supplier_id' => $supplier->id,
                'supplier_name' => $this->suppliers->displayName($supplier),
                'order_date' => $header['order_date'],
                'expected_delivery_date' => $header['expected_delivery_date'] ?? null,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'notes' => $header['notes'] ?? null,
                'terms' => $header['terms'] ?? null,
            ]);

            $order->items()->delete();
            $this->syncItems($order, $items);
            $this->logger->log('purchase_order.updated', "Updated PO {$order->order_number}", 'purchase_orders', $order);

            return $order->load(['items.product', 'supplier', 'warehouse']);
        });
    }

    public function submit(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order) {
            $order = PurchaseOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status === PurchaseOrder::STATUS_PENDING_APPROVAL) {
                throw ValidationException::withMessages(['order' => 'Purchase order is already pending approval.']);
            }

            if (! $order->isDraft() && $order->status !== PurchaseOrder::STATUS_REJECTED) {
                throw ValidationException::withMessages(['order' => 'Only draft or rejected purchase orders can be submitted.']);
            }

            $order->load('items');
            if ($order->items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Add at least one line item before submitting.']);
            }

            $order->update([
                'status' => PurchaseOrder::STATUS_PENDING_APPROVAL,
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
                'ordered_at' => $order->ordered_at ?? now(),
                'rejection_reason' => null,
                'rejected_by' => null,
                'rejected_at' => null,
            ]);

            $this->logger->log('purchase_order.submitted', "Submitted PO {$order->order_number}", 'purchase_orders', $order);

            return $order->fresh(['items.product', 'supplier', 'warehouse', 'submitter']);
        });
    }

    public function approve(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order) {
            $order = PurchaseOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $order->isPendingApproval()) {
                throw ValidationException::withMessages(['order' => 'Only pending purchase orders can be approved.']);
            }

            $order->update([
                'status' => PurchaseOrder::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'ordered_at' => $order->ordered_at ?? now(),
            ]);

            $this->logger->log('purchase_order.approved', "Approved PO {$order->order_number}", 'purchase_orders', $order);

            return $order->fresh(['approver', 'supplier', 'items.product']);
        });
    }

    public function reject(PurchaseOrder $order, string $reason): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $reason) {
            $order = PurchaseOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! $order->isPendingApproval()) {
                throw ValidationException::withMessages(['order' => 'Only pending purchase orders can be rejected.']);
            }

            if (trim($reason) === '') {
                throw ValidationException::withMessages(['rejection_reason' => 'Rejection reason is required.']);
            }

            $order->update([
                'status' => PurchaseOrder::STATUS_REJECTED,
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->logger->log('purchase_order.rejected', "Rejected PO {$order->order_number}", 'purchase_orders', $order);

            return $order->fresh();
        });
    }

    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        return DB::transaction(function () use ($order) {
            $order = PurchaseOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $order->load('items');

            if (in_array($order->status, [PurchaseOrder::STATUS_CANCELLED, PurchaseOrder::STATUS_FULLY_RECEIVED], true)) {
                throw ValidationException::withMessages(['order' => 'This purchase order cannot be cancelled.']);
            }

            $received = (int) $order->items->sum('received_quantity');
            if ($received > 0) {
                throw ValidationException::withMessages([
                    'order' => 'Partially received purchase orders cannot be cancelled. Received stock is preserved.',
                ]);
            }

            if (! in_array($order->status, [
                PurchaseOrder::STATUS_DRAFT,
                PurchaseOrder::STATUS_PENDING_APPROVAL,
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_REJECTED,
            ], true)) {
                throw ValidationException::withMessages(['order' => 'This purchase order cannot be cancelled in its current status.']);
            }

            $order->update([
                'status' => PurchaseOrder::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            $this->logger->log('purchase_order.cancelled', "Cancelled PO {$order->order_number}", 'purchase_orders', $order);

            return $order->fresh();
        });
    }

    public function deleteDraft(PurchaseOrder $order): void
    {
        if (! $order->isDraft()) {
            throw ValidationException::withMessages(['order' => 'Only draft purchase orders can be deleted.']);
        }

        DB::transaction(function () use ($order) {
            $number = $order->order_number;
            $order->items()->delete();
            $order->delete();
            $this->logger->log('purchase_order.deleted', "Deleted draft PO {$number}", 'purchase_orders');
        });
    }

    public function calculateTotals(array $items): array
    {
        $subtotal = 0.0;
        $discount = 0.0;
        $tax = 0.0;
        $total = 0.0;

        foreach ($items as $item) {
            $line = $this->lineAmounts($item);
            $subtotal += $line['line_subtotal'];
            $discount += $line['discount_amount'];
            $tax += $line['tax_amount'];
            $total += $line['line_total'];
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discount, 2),
            'tax_amount' => round($tax, 2),
            'total' => round($total, 2),
        ];
    }

    public function lineAmounts(array $item): array
    {
        $qty = (int) $item['quantity'];
        $unit = (float) $item['unit_cost'];
        $discPct = (float) ($item['discount_percent'] ?? 0);
        $taxPct = (float) ($item['tax_percent'] ?? 0);

        $gross = $qty * $unit;
        $discountAmount = round($gross * ($discPct / 100), 2);
        $lineSubtotal = round($gross - $discountAmount, 2);
        $taxAmount = round($lineSubtotal * ($taxPct / 100), 2);
        $lineTotal = round($lineSubtotal + $taxAmount, 2);

        return [
            'line_subtotal' => $lineSubtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'line_total' => $lineTotal,
        ];
    }

    private function syncItems(PurchaseOrder $order, array $items): void
    {
        foreach ($items as $item) {
            $amounts = $this->lineAmounts($item);
            PurchaseOrderItem::create([
                'purchase_order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => (int) $item['quantity'],
                'received_quantity' => 0,
                'rejected_quantity' => 0,
                'unit_cost' => (float) $item['unit_cost'],
                'discount_percent' => (float) ($item['discount_percent'] ?? 0),
                'tax_percent' => (float) ($item['tax_percent'] ?? 0),
                'line_subtotal' => $amounts['line_subtotal'],
                'line_total' => $amounts['line_total'],
            ]);
        }
    }

    private function assertItems(array $items): void
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'At least one line item is required.']);
        }
    }

    private function assertDraftEditable(PurchaseOrder $order): void
    {
        if (! in_array($order->status, [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_REJECTED], true)) {
            throw ValidationException::withMessages(['order' => 'Only draft or rejected purchase orders can be edited.']);
        }
    }
}
