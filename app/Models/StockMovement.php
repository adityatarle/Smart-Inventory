<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class StockMovement extends Model
{
    public const STATUS_POSTED = 'posted';

    /** @var bool Allow mutations only for migrations/maintenance scripts */
    public static bool $allowMutation = false;

    protected $fillable = [
        'document_number',
        'transaction_date',
        'warehouse_id',
        'product_id',
        'user_id',
        'type',
        'direction',
        'status',
        'quantity',
        'quantity_in',
        'quantity_out',
        'unit_cost',
        'total_cost',
        'balance_after',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'quantity' => 'integer',
            'quantity_in' => 'integer',
            'quantity_out' => 'integer',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
            'balance_after' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            if (! static::$allowMutation) {
                throw new LogicException('Stock ledger entries are immutable and cannot be updated.');
            }
        });

        static::deleting(function () {
            if (! static::$allowMutation) {
                throw new LogicException('Stock ledger entries are immutable and cannot be deleted.');
            }
        });
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($term) {
            $inner->where('document_number', 'like', "%{$term}%")
                ->orWhere('notes', 'like', "%{$term}%")
                ->orWhere('type', 'like', "%{$term}%")
                ->orWhereHas('product', function (Builder $product) use ($term) {
                    $product->where('name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%");
                });
        });
    }
}
