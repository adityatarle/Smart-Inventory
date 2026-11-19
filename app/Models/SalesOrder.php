<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SalesOrder extends Model
{
    protected $fillable = [
        'order_number',
        'warehouse_id',
        'customer_id',
        'sales_person_id',
        'sales_partner_id',
        'quotation_id',
        'blanket_order_id',
        'created_by',
        'customer_name',
        'status',
        'total',
        'notes',
        'confirmed_at',
        'fulfilled_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesPerson(): BelongsTo
    {
        return $this->belongsTo(SalesPerson::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(SalesPartner::class, 'sales_partner_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function blanketOrder(): BelongsTo
    {
        return $this->belongsTo(BlanketOrder::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(SalesInvoice::class);
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }
}
