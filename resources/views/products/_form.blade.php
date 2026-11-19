@php
    $suggestedSku = $suggestedSku ?? null;
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="card w-full max-w-4xl space-y-5 p-4 sm:p-6">
    @csrf
    @if(!empty($method)) @method($method) @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label">Product name <span class="text-ss-danger">*</span></label>
            <input name="name" class="input" value="{{ old('name', $product->name ?? '') }}" required>
        </div>
        <div>
            <label class="label">SKU <span class="text-ss-danger">*</span></label>
            <div class="flex gap-2">
                <input id="sku" name="sku" class="input" value="{{ old('sku', $product->sku ?? $suggestedSku) }}" required>
            </div>
            <p class="mt-1 text-xs text-ss-muted">Must be unique across the catalog.</p>
        </div>
        <div>
            <label class="label">Product code</label>
            <input name="product_code" class="input" value="{{ old('product_code', $product->product_code ?? '') }}">
        </div>
        <div>
            <label class="label">Barcode</label>
            <input name="barcode" class="input" value="{{ old('barcode', $product->barcode ?? '') }}">
        </div>
        <div>
            <label class="label">Category</label>
            <select name="category_id" class="input">
                <option value="">Uncategorized</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? '') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Unit of measurement <span class="text-ss-danger">*</span></label>
            <input name="unit" class="input" value="{{ old('unit', $product->unit ?? 'pcs') }}" required>
        </div>
        <div>
            <label class="label">Purchase price <span class="text-ss-danger">*</span></label>
            <input type="number" step="0.01" min="0" name="cost_price" class="input" value="{{ old('cost_price', $product->cost_price ?? 0) }}" required>
        </div>
        <div>
            <label class="label">Selling price <span class="text-ss-danger">*</span></label>
            <input type="number" step="0.01" min="0" name="sell_price" class="input" value="{{ old('sell_price', $product->sell_price ?? 0) }}" required>
        </div>
        <div>
            <label class="label">Minimum stock level <span class="text-ss-danger">*</span></label>
            <input type="number" min="0" name="reorder_level" class="input" value="{{ old('reorder_level', $product->reorder_level ?? 10) }}" required>
        </div>
        <div>
            <label class="label">Maximum stock level</label>
            <input type="number" min="0" name="max_stock_level" class="input" value="{{ old('max_stock_level', $product->max_stock_level ?? '') }}">
        </div>
    </div>

    <div>
        <label class="label">Description</label>
        <textarea name="description" rows="3" class="input">{{ old('description', $product->description ?? '') }}</textarea>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label">Product image</label>
            <input type="file" name="image" accept="image/png,image/jpeg,image/webp" class="input">
            <p class="mt-1 text-xs text-ss-muted">JPG, PNG or WebP · max 2MB</p>
        </div>
        <div class="flex items-end">
            @if(!empty($product?->image_path))
                <div class="flex items-center gap-3">
                    <img src="{{ $product->imageUrl() }}" alt="" class="h-16 w-16 rounded-xl object-cover">
                    <label class="flex items-center gap-2 text-sm text-ss-muted">
                        <input type="checkbox" name="remove_image" value="1" class="rounded border-white/20 bg-[#12111F] text-ss-primary">
                        Remove current image
                    </label>
                </div>
            @endif
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm text-ss-muted">
        <input type="checkbox" name="is_active" value="1" class="rounded border-white/20 bg-[#12111F] text-ss-primary" @checked(old('is_active', $product->is_active ?? true))>
        Active product
    </label>

    <div class="flex flex-col gap-3 sm:flex-row">
        <button class="btn-primary w-full justify-center sm:w-auto">Save product</button>
        <a href="{{ route('products.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Cancel</a>
    </div>
</form>
