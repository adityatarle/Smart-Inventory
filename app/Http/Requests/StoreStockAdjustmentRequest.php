<?php

namespace App\Http\Requests;

use App\Models\StockAdjustment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('inventory.adjust')
            || $this->user()?->hasPermission('inventory.manage')
            || $this->user()?->canManageInventory()
            || false;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'transaction_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id', 'distinct'],
            'items.*.physical_quantity' => ['required', 'integer', 'min:0'],
            'items.*.reason' => ['required', Rule::in(array_keys(StockAdjustment::REASONS))],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
