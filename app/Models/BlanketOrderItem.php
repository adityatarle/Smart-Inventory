<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlanketOrderItem extends Model
{
    protected $fillable = [
        'blanket_order_id',
        'product_id',
        'agreed_quantity',
        'ordered_quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
        ];
    }

    public function blanketOrder(): BelongsTo
    {
        return $this->belongsTo(BlanketOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function remainingQuantity(): int
    {
        return max(0, $this->agreed_quantity - $this->ordered_quantity);
    }

    public function lineTotal(): float
    {
        return (float) $this->agreed_quantity * (float) $this->unit_price;
    }
}
