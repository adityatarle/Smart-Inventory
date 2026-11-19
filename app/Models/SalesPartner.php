<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesPartner extends Model
{
    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'territory',
        'commission_rate',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function salesPersons(): HasMany
    {
        return $this->hasMany(SalesPerson::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
