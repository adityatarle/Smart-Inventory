<?php

namespace App\Services;

use App\Models\StockIssue;
use App\Models\StockIssueItem;
use App\Models\StockMovement;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class StockOutService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly ActivityLogger $logger,
    ) {
    }

    public function createDraft(array $header, array $items): StockIssue
    {
        $this->assertItems($items);

        return DB::transaction(function () use ($header, $items) {
            $issue = StockIssue::create([
                'document_number' => DocumentNumber::make('ISS'),
                'warehouse_id' => $header['warehouse_id'],
                'status' => StockIssue::STATUS_DRAFT,
                'reference_number' => $header['reference_number'] ?? null,
                'transaction_date' => $header['transaction_date'],
                'remarks' => $header['remarks'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->syncItems($issue, $items);
            $this->logger->log('stock_out.created', "Created stock issue {$issue->document_number}", 'inventory', $issue);

            return $issue->load(['items.product', 'warehouse']);
        });
    }

    public function updateDraft(StockIssue $issue, array $header, array $items): StockIssue
    {
        $this->assertDraft($issue);
        $this->assertItems($items);

        return DB::transaction(function () use ($issue, $header, $items) {
            $issue = StockIssue::query()->whereKey($issue->id)->lockForUpdate()->firstOrFail();
            $this->assertDraft($issue);

            $issue->update([
                'warehouse_id' => $header['warehouse_id'],
                'reference_number' => $header['reference_number'] ?? null,
                'transaction_date' => $header['transaction_date'],
                'remarks' => $header['remarks'] ?? null,
            ]);

            $issue->items()->delete();
            $this->syncItems($issue, $items);
            $this->logger->log('stock_out.updated', "Updated stock issue {$issue->document_number}", 'inventory', $issue);

            return $issue->load(['items.product', 'warehouse']);
        });
    }

    public function submit(StockIssue $issue): StockIssue
    {
        return DB::transaction(function () use ($issue) {
            $issue = StockIssue::query()->whereKey($issue->id)->lockForUpdate()->firstOrFail();

            if ($issue->status === StockIssue::STATUS_SUBMITTED) {
                throw ValidationException::withMessages([
                    'issue' => 'This issue was already submitted. Duplicate posting is not allowed.',
                ]);
            }

            $this->assertDraft($issue);
            $issue->load('items');

            if ($issue->items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Add at least one product before submitting.']);
            }

            // Pre-validate availability under locks via InventoryService::issue
            foreach ($issue->items as $item) {
                try {
                    $this->inventory->issue(
                        (int) $issue->warehouse_id,
                        (int) $item->product_id,
                        (int) $item->quantity,
                        $issue,
                        Auth::id(),
                        'Stock issue '.$issue->document_number,
                        $issue->document_number,
                        $issue->transaction_date
                    );
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['items' => $e->getMessage()]);
                }
            }

            $issue->update([
                'status' => StockIssue::STATUS_SUBMITTED,
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
            ]);

            $this->logger->log('stock_out.submitted', "Submitted stock issue {$issue->document_number}", 'inventory', $issue);

            return $issue->fresh(['items.product', 'warehouse', 'creator', 'submitter']);
        });
    }

    public function cancel(StockIssue $issue): StockIssue
    {
        return DB::transaction(function () use ($issue) {
            $issue = StockIssue::query()->whereKey($issue->id)->lockForUpdate()->firstOrFail();

            if ($issue->status === StockIssue::STATUS_CANCELLED) {
                throw ValidationException::withMessages(['issue' => 'Issue is already cancelled.']);
            }

            if ($issue->isDraft()) {
                $issue->update([
                    'status' => StockIssue::STATUS_CANCELLED,
                    'cancelled_at' => now(),
                ]);
                $this->logger->log('stock_out.cancelled', "Cancelled draft issue {$issue->document_number}", 'inventory', $issue);

                return $issue->fresh();
            }

            if (! $issue->isSubmitted()) {
                throw ValidationException::withMessages(['issue' => 'Only draft or submitted issues can be cancelled.']);
            }

            $movements = StockMovement::query()
                ->where('reference_type', $issue->getMorphClass())
                ->where('reference_id', $issue->id)
                ->where('type', 'out')
                ->get();

            foreach ($movements as $movement) {
                try {
                    $this->inventory->reverse($movement, 'Cancellation of '.$issue->document_number, Auth::id());
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['issue' => $e->getMessage()]);
                }
            }

            $issue->update([
                'status' => StockIssue::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            $this->logger->log('stock_out.cancelled', "Cancelled submitted issue {$issue->document_number}", 'inventory', $issue);

            return $issue->fresh();
        });
    }

    public function deleteDraft(StockIssue $issue): void
    {
        $this->assertDraft($issue);

        DB::transaction(function () use ($issue) {
            $number = $issue->document_number;
            $issue->items()->delete();
            $issue->delete();
            $this->logger->log('stock_out.deleted', "Deleted draft issue {$number}", 'inventory');
        });
    }

    private function syncItems(StockIssue $issue, array $items): void
    {
        foreach ($items as $item) {
            StockIssueItem::create([
                'stock_issue_id' => $issue->id,
                'product_id' => $item['product_id'],
                'quantity' => (int) $item['quantity'],
            ]);
        }
    }

    private function assertItems(array $items): void
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'At least one line item is required.']);
        }
    }

    private function assertDraft(StockIssue $issue): void
    {
        if (! $issue->isDraft()) {
            throw ValidationException::withMessages([
                'issue' => 'Only draft issues can be modified.',
            ]);
        }
    }
}
