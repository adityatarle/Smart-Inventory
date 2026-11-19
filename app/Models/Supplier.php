<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    public function matchingPurchaseOrders()
    {
        return PurchaseOrder::query()
            ->where(function ($q) {
                $q->where('supplier_name', $this->name)
                    ->orWhere('supplier_name', $this->company_name)
                    ->orWhere('supplier_name', $this->code);
            })
            ->latest();
    }
}
