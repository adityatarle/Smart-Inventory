<form method="POST" action="{{ $action }}" class="card max-w-xl space-y-4 p-6">
    @csrf
    @if(!empty($method)) @method($method) @endif
    <div><label class="label">Name</label><input name="name" class="input" value="{{ old('name', $salesPerson->name ?? '') }}" required></div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="label">Email</label><input type="email" name="email" class="input" value="{{ old('email', $salesPerson->email ?? '') }}"></div>
        <div><label class="label">Phone</label><input name="phone" class="input" value="{{ old('phone', $salesPerson->phone ?? '') }}"></div>
        <div>
            <label class="label">Sales partner</label>
            <select name="sales_partner_id" class="input">
                <option value="">—</option>
                @foreach($partners as $partner)
                    <option value="{{ $partner->id }}" @selected(old('sales_partner_id', $salesPerson->sales_partner_id ?? '') == $partner->id)>{{ $partner->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Linked user</label>
            <select name="user_id" class="input">
                <option value="">—</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(old('user_id', $salesPerson->user_id ?? '') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="label">Commission %</label><input type="number" step="0.01" min="0" max="100" name="commission_rate" class="input" value="{{ old('commission_rate', $salesPerson->commission_rate ?? 0) }}"></div>
    </div>
    <div><label class="label">Notes</label><textarea name="notes" rows="2" class="input">{{ old('notes', $salesPerson->notes ?? '') }}</textarea></div>
    <label class="flex items-center gap-2 text-sm text-muted"><input type="checkbox" name="is_active" value="1" class="rounded border-line bg-slate-900 text-teal-400" @checked(old('is_active', $salesPerson->is_active ?? true))> Active</label>
    <div class="flex gap-3"><button class="btn-primary">Save</button><a href="{{ route('sales-persons.index') }}" class="btn-secondary">Cancel</a></div>
</form>
