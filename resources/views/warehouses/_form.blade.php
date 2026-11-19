<form method="POST" action="{{ $action }}" class="card max-w-2xl space-y-4 p-6">
    @csrf
    @if(!empty($method)) @method($method) @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label">Name</label>
            <input name="name" class="input" value="{{ old('name', $warehouse->name ?? '') }}" required>
        </div>
        <div>
            <label class="label">Code</label>
            <input name="code" class="input" value="{{ old('code', $warehouse->code ?? '') }}" required>
        </div>
        <div>
            <label class="label">City</label>
            <input name="city" class="input" value="{{ old('city', $warehouse->city ?? '') }}">
        </div>
        <div>
            <label class="label">Phone</label>
            <input name="phone" class="input" value="{{ old('phone', $warehouse->phone ?? '') }}">
        </div>
    </div>
    <div>
        <label class="label">Address</label>
        <input name="address" class="input" value="{{ old('address', $warehouse->address ?? '') }}">
    </div>
    <label class="flex items-center gap-2 text-sm text-muted">
        <input type="checkbox" name="is_active" value="1" class="rounded border-line bg-slate-900 text-teal-400" @checked(old('is_active', $warehouse->is_active ?? true))>
        Active warehouse
    </label>
    <div class="flex gap-3">
        <button class="btn-primary">Save</button>
        <a href="{{ route('warehouses.index') }}" class="btn-secondary">Cancel</a>
    </div>
</form>
