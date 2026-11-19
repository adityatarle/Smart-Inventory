<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'document_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'reference_number',
        'transaction_date',
        'remarks',
        'created_by',
        'submitted_by',
        'submitted_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isOpenForReceive(): bool
    {
        return in_array($this->status, [self::STATUS_IN_TRANSIT, self::STATUS_PARTIALLY_RECEIVED], true);
    }

    public function totalDispatched(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function totalReceived(): int
    {
        return (int) $this->items->sum('quantity_received');
    }

    public function totalInTransit(): int
    {
        return $this->totalDispatched() - $this->totalReceived();
    }
}
