<?php

namespace App\Http\Controllers\Sales\Concerns;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait ScopesSalesByCustomer
{
    protected function linkedCustomer(): ?Customer
    {
        $user = Auth::user();

        if (! $user || ! $user->isCustomer()) {
            return null;
        }

        return Customer::query()->where('user_id', $user->id)->first();
    }

    protected function scopeForCustomerRole(Builder $query, string $customerColumn = 'customer_id'): Builder
    {
        $customer = $this->linkedCustomer();

        if (Auth::user()?->isCustomer()) {
            return $query->where($customerColumn, $customer?->id ?? 0);
        }

        return $query;
    }
}
