<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase_orders.receive')
            || $this->user()?->hasPermission('purchase_orders.manage')
            || $this->user()?->canManageInventory()
            || $this->user()?->isAdmin()
            || false;
    }

    public function rules(): array
    {
        return [
            'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'receipt_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchase_order_item_id' => ['required', 'integer', 'exists:purchase_order_items,id'],
            'lines.*.received_quantity' => ['nullable', 'integer', 'min:0'],
            'lines.*.rejected_quantity' => ['nullable', 'integer', 'min:0'],
            'lines.*.accepted_quantity' => ['nullable', 'integer', 'min:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.batch_reference' => ['nullable', 'string', 'max:100'],
            'lines.*.remarks' => ['nullable', 'string'],
        ];
    }
}
