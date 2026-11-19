<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAlert extends Model
{
    public const TYPE_LOW = 'low_stock';
    public const TYPE_OUT = 'out_of_stock';
    public const TYPE_REORDER = 'reorder';
    public const TYPE_EXCESS = 'excess';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'type',
        'severity',
        'title',
        'message',
        'quantity',
        'threshold',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'threshold' => 'integer',
            'resolved_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    public function isOpen(): bool
    {
        return $this->resolved_at === null;
    }

    public function resolve(): void
    {
        if ($this->isOpen()) {
            $this->update(['resolved_at' => now()]);
        }
    }
}
