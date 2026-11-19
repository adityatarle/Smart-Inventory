<form method="POST" action="{{ $action }}" class="card max-w-xl space-y-4 p-6">
    @csrf
    @if(!empty($method)) @method($method) @endif
    <div><label class="label">Name</label><input name="name" class="input" value="{{ old('name', $salesPartner->name ?? '') }}" required></div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="label">Email</label><input type="email" name="email" class="input" value="{{ old('email', $salesPartner->email ?? '') }}"></div>
        <div><label class="label">Phone</label><input name="phone" class="input" value="{{ old('phone', $salesPartner->phone ?? '') }}"></div>
        <div><label class="label">Territory</label><input name="territory" class="input" value="{{ old('territory', $salesPartner->territory ?? '') }}"></div>
        <div><label class="label">Commission %</label><input type="number" step="0.01" min="0" max="100" name="commission_rate" class="input" value="{{ old('commission_rate', $salesPartner->commission_rate ?? 0) }}"></div>
    </div>
    <div><label class="label">Notes</label><textarea name="notes" rows="2" class="input">{{ old('notes', $salesPartner->notes ?? '') }}</textarea></div>
    <label class="flex items-center gap-2 text-sm text-muted"><input type="checkbox" name="is_active" value="1" class="rounded border-line bg-slate-900 text-teal-400" @checked(old('is_active', $salesPartner->is_active ?? true))> Active</label>
    <div class="flex gap-3"><button class="btn-primary">Save</button><a href="{{ route('sales-partners.index') }}" class="btn-secondary">Cancel</a></div>
</form>
