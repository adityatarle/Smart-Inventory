<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'company',
        'gst_number',
        'billing_address',
        'shipping_address',
        'city',
        'state',
        'pincode',
        'user_id',
        'sales_partner_id',
        'sales_person_id',
        'credit_limit',
        'payment_terms',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(SalesPartner::class, 'sales_partner_id');
    }

    public function salesPerson(): BelongsTo
    {
        return $this->belongsTo(SalesPerson::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function blanketOrders(): HasMany
    {
        return $this->hasMany(BlanketOrder::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($term) {
            $inner->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('company', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('gst_number', 'like', "%{$term}%");
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
