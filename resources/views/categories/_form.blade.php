<form method="POST" action="{{ $action }}" class="card w-full max-w-2xl space-y-5 p-4 sm:p-6">
    @csrf
    @if(!empty($method)) @method($method) @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label class="label">Category name <span class="text-ss-danger">*</span></label>
            <input name="name" class="input" value="{{ old('name', $category->name ?? '') }}" required>
        </div>
        <div class="sm:col-span-2">
            <label class="label">Parent category</label>
            <select name="parent_id" class="input">
                <option value="">None (top-level)</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id ?? '') == $parent->id)>
                        {{ $parent->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="label">Description</label>
        <textarea name="description" rows="3" class="input">{{ old('description', $category->description ?? '') }}</textarea>
    </div>

    <label class="flex items-center gap-2 text-sm text-ss-muted">
        <input type="checkbox" name="is_active" value="1" class="rounded border-white/20 bg-[#12111F] text-ss-primary" @checked(old('is_active', $category->is_active ?? true))>
        Active category
    </label>

    <div class="flex flex-col gap-3 sm:flex-row">
        <button class="btn-primary w-full justify-center sm:w-auto">Save category</button>
        <a href="{{ isset($category) && $category?->id ? route('categories.show', $category) : route('categories.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Cancel</a>
    </div>
</form>
