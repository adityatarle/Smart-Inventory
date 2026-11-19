<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PurchaseOrder extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';
    public const STATUS_FULLY_RECEIVED = 'fully_received';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';

    /** @deprecated legacy */
    public const STATUS_ORDERED = 'approved';
    /** @deprecated legacy */
    public const STATUS_RECEIVED = 'fully_received';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING_APPROVAL,
        self::STATUS_APPROVED,
        self::STATUS_PARTIALLY_RECEIVED,
        self::STATUS_FULLY_RECEIVED,
        self::STATUS_CANCELLED,
        self::STATUS_REJECTED,
    ];

    protected $fillable = [
        'order_number',
        'warehouse_id',
        'ordered_by',
        'supplier_id',
        'supplier_name',
        'order_date',
        'expected_delivery_date',
        'status',
        'total',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'notes',
        'terms',
        'ordered_at',
        'received_at',
        'cancelled_at',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'total' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'ordered_at' => 'datetime',
            'received_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function supplierDisplayName(): string
    {
        return $this->supplier?->name
            ?: $this->supplier?->company_name
            ?: (string) $this->supplier_name
            ?: '—';
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function isApproved(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_PARTIALLY_RECEIVED,
            self::STATUS_FULLY_RECEIVED,
        ], true);
    }

    public function canReceiveGoods(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_PARTIALLY_RECEIVED,
        ], true);
    }

    public function refreshReceiveStatus(): void
    {
        $this->load('items');
        $ordered = (int) $this->items->sum('quantity');
        $received = (int) $this->items->sum('received_quantity');

        if ($received <= 0) {
            if (in_array($this->status, [self::STATUS_PARTIALLY_RECEIVED, self::STATUS_FULLY_RECEIVED], true)) {
                $this->update(['status' => self::STATUS_APPROVED, 'received_at' => null]);
            }

            return;
        }

        if ($received >= $ordered) {
            $this->update([
                'status' => self::STATUS_FULLY_RECEIVED,
                'received_at' => $this->received_at ?? now(),
            ]);
        } else {
            $this->update([
                'status' => self::STATUS_PARTIALLY_RECEIVED,
                'received_at' => null,
            ]);
        }
    }

    public function statusLabel(): string
    {
        return str_replace('_', ' ', ucfirst($this->status));
    }
}
