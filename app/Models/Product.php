<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'sku',
        'product_code',
        'barcode',
        'name',
        'description',
        'image_path',
        'unit',
        'cost_price',
        'sell_price',
        'reorder_level',
        'max_stock_level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sell_price' => 'decimal:2',
            'reorder_level' => 'integer',
            'max_stock_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function salesOrderItems(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function totalStock(): int
    {
        return (int) $this->stocks()->sum('quantity');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function isLowStock(?int $totalQty = null): bool
    {
        $qty = $totalQty ?? $this->totalStock();

        return $qty <= (int) $this->reorder_level;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($term) {
            $inner->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhere('barcode', 'like', "%{$term}%")
                ->orWhere('product_code', 'like', "%{$term}%");
        });
    }

    public function scopeActive(Builder $query, ?string $status): Builder
    {
        if ($status === 'active') {
            return $query->where('is_active', true);
        }

        if ($status === 'inactive') {
            return $query->where('is_active', false);
        }

        return $query;
    }
}
