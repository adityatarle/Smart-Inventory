<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quotation extends Model
{
    protected $fillable = [
        'quotation_number',
        'customer_id',
        'warehouse_id',
        'sales_person_id',
        'sales_partner_id',
        'created_by',
        'status',
        'valid_until',
        'total',
        'notes',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'total' => 'decimal:2',
            'converted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function salesPerson(): BelongsTo
    {
        return $this->belongsTo(SalesPerson::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(SalesPartner::class, 'sales_partner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function salesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class);
    }
}
