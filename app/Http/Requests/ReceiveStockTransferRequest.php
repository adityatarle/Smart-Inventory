<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('inventory.transfer')
            || $this->user()?->hasPermission('inventory.manage')
            || $this->user()?->canManageInventory()
            || false;
    }

    public function rules(): array
    {
        return [
            'receipts' => ['required', 'array', 'min:1'],
            'receipts.*.item_id' => ['required', 'integer', 'exists:stock_transfer_items,id'],
            'receipts.*.quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
