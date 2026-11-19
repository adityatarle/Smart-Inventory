<form method="POST" action="{{ $action }}" class="card w-full max-w-3xl space-y-5 p-4 sm:p-6">
    @csrf
    @if(!empty($method)) @method($method) @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label">Warehouse name <span class="text-ss-danger">*</span></label>
            <input name="name" class="input" value="{{ old('name', $warehouse->name ?? '') }}" required>
        </div>
        <div>
            <label class="label">Warehouse code <span class="text-ss-danger">*</span></label>
            <input name="code" class="input" value="{{ old('code', $warehouse->code ?? ($suggestedCode ?? '')) }}" required>
        </div>
        <div>
            <label class="label">Contact person</label>
            <input name="contact_person" class="input" value="{{ old('contact_person', $warehouse->contact_person ?? '') }}">
        </div>
        <div>
            <label class="label">Phone</label>
            <input name="phone" class="input" value="{{ old('phone', $warehouse->phone ?? '') }}">
        </div>
        <div>
            <label class="label">Email</label>
            <input type="email" name="email" class="input" value="{{ old('email', $warehouse->email ?? '') }}">
        </div>
        <div>
            <label class="label">City</label>
            <input name="city" class="input" value="{{ old('city', $warehouse->city ?? '') }}">
        </div>
    </div>

    <div>
        <label class="label">Address</label>
        <input name="address" class="input" value="{{ old('address', $warehouse->address ?? '') }}">
    </div>

    <label class="flex items-center gap-2 text-sm text-ss-muted">
        <input type="checkbox" name="is_active" value="1" class="rounded border-white/20 bg-[#12111F] text-ss-primary" @checked(old('is_active', $warehouse->is_active ?? true))>
        Active warehouse
    </label>

    <div class="flex flex-col gap-3 sm:flex-row">
        <button class="btn-primary w-full justify-center sm:w-auto">Save warehouse</button>
        <a href="{{ isset($warehouse) && $warehouse?->id ? route('warehouses.show', $warehouse) : route('warehouses.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Cancel</a>
    </div>
</form>
