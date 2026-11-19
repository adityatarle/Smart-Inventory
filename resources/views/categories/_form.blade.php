<form method="POST" action="{{ $action }}" class="card max-w-xl space-y-4 p-6">
    @csrf
    @if(!empty($method)) @method($method) @endif
    <div>
        <label class="label">Name</label>
        <input name="name" class="input" value="{{ old('name', $category->name ?? '') }}" required>
    </div>
    <div>
        <label class="label">Description</label>
        <textarea name="description" rows="3" class="input">{{ old('description', $category->description ?? '') }}</textarea>
    </div>
    <div class="flex gap-3">
        <button class="btn-primary">Save</button>
        <a href="{{ route('categories.index') }}" class="btn-secondary">Cancel</a>
    </div>
</form>
