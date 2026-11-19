<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('suppliers.update')
            || $this->user()?->hasPermission('suppliers.manage')
            || $this->user()?->canManageInventory()
            || false;
    }

    public function rules(): array
    {
        $id = $this->route('supplier')?->id;

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('suppliers', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'gst_number' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'code' => filled($this->code) ? strtoupper(trim((string) $this->code)) : $this->code,
        ]);
    }
}
