<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('products.create')
            || $this->user()?->hasPermission('products.manage')
            || $this->user()?->canManageInventory()
            || false;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'product_code' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit' => ['required', 'string', 'max:30'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sell_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'max_stock_level' => ['nullable', 'integer', 'min:0', 'gte:reorder_level'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_stock_level.gte' => 'Maximum stock level must be greater than or equal to minimum stock level.',
            'image.max' => 'Product image must not exceed 2MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'remove_image' => $this->boolean('remove_image'),
            'sku' => filled($this->sku) ? strtoupper(trim((string) $this->sku)) : $this->sku,
            'barcode' => filled($this->barcode) ? trim((string) $this->barcode) : null,
            'product_code' => filled($this->product_code) ? trim((string) $this->product_code) : null,
        ]);
    }
}
