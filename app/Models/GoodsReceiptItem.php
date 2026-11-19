<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'product_id',
        'ordered_quantity',
        'previously_received',
        'received_quantity',
        'rejected_quantity',
        'accepted_quantity',
        'unit_cost',
        'batch_reference',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'ordered_quantity' => 'integer',
            'previously_received' => 'integer',
            'received_quantity' => 'integer',
            'rejected_quantity' => 'integer',
            'accepted_quantity' => 'integer',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
