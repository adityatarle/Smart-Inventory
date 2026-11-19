<form method="POST" action="{{ $action }}" class="card max-w-3xl space-y-4 p-6">
    @csrf
    @if(!empty($method)) @method($method) @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label">SKU</label>
            <input name="sku" class="input" value="{{ old('sku', $product->sku ?? '') }}" required>
        </div>
        <div>
            <label class="label">Name</label>
            <input name="name" class="input" value="{{ old('name', $product->name ?? '') }}" required>
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
            <label class="label">Unit</label>
            <input name="unit" class="input" value="{{ old('unit', $product->unit ?? 'pcs') }}" required>
        </div>
        <div>
            <label class="label">Cost price</label>
            <input type="number" step="0.01" min="0" name="cost_price" class="input" value="{{ old('cost_price', $product->cost_price ?? 0) }}" required>
        </div>
        <div>
            <label class="label">Sell price</label>
            <input type="number" step="0.01" min="0" name="sell_price" class="input" value="{{ old('sell_price', $product->sell_price ?? 0) }}" required>
        </div>
        <div>
            <label class="label">Reorder level</label>
            <input type="number" min="0" name="reorder_level" class="input" value="{{ old('reorder_level', $product->reorder_level ?? 10) }}" required>
        </div>
    </div>
    <div>
        <label class="label">Description</label>
        <textarea name="description" rows="3" class="input">{{ old('description', $product->description ?? '') }}</textarea>
    </div>
    <label class="flex items-center gap-2 text-sm text-muted">
        <input type="checkbox" name="is_active" value="1" class="rounded border-line bg-slate-900 text-teal-400" @checked(old('is_active', $product->is_active ?? true))>
        Active product
    </label>
    <div class="flex gap-3">
        <button class="btn-primary">Save</button>
        <a href="{{ route('products.index') }}" class="btn-secondary">Cancel</a>
    </div>
</form>
