<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'code',
        'name',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'gst_number',
        'billing_address',
        'city',
        'state',
        'pincode',
        'payment_terms',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $inner) use ($term) {
            $inner->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orWhere('company_name', 'like', "%{$term}%")
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

    /**
     * Purchase history: prefer FK links, include unmatched legacy name matches.
     */
    public function purchaseHistoryQuery()
    {
        return PurchaseOrder::query()
            ->where(function ($q) {
                $q->where('supplier_id', $this->id)
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('supplier_id')
                            ->where(function ($names) {
                                $names->where('supplier_name', $this->name)
                                    ->orWhere('supplier_name', $this->company_name)
                                    ->orWhere('supplier_name', $this->code);
                            });
                    });
            })
            ->latest();
    }

    /** @deprecated Use purchaseHistoryQuery() / purchaseOrders() */
    public function matchingPurchaseOrders()
    {
        return $this->purchaseHistoryQuery();
    }
}
