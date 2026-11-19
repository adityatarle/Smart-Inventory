<form method="POST" action="{{ $action }}" class="card max-w-3xl space-y-4 p-6">
    @csrf
    @if(!empty($method)) @method($method) @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label">Name</label>
            <input name="name" class="input" value="{{ old('name', $customer->name ?? '') }}" required>
        </div>
        <div>
            <label class="label">Company</label>
            <input name="company" class="input" value="{{ old('company', $customer->company ?? '') }}">
        </div>
        <div>
            <label class="label">Email</label>
            <input type="email" name="email" class="input" value="{{ old('email', $customer->email ?? '') }}">
        </div>
        <div>
            <label class="label">Phone</label>
            <input name="phone" class="input" value="{{ old('phone', $customer->phone ?? '') }}">
        </div>
        <div>
            <label class="label">Sales person</label>
            <select name="sales_person_id" class="input">
                <option value="">—</option>
                @foreach($persons as $person)
                    <option value="{{ $person->id }}" @selected(old('sales_person_id', $customer->sales_person_id ?? '') == $person->id)>{{ $person->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Sales partner</label>
            <select name="sales_partner_id" class="input">
                <option value="">—</option>
                @foreach($partners as $partner)
                    <option value="{{ $partner->id }}" @selected(old('sales_partner_id', $customer->sales_partner_id ?? '') == $partner->id)>{{ $partner->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Linked login user</label>
            <select name="user_id" class="input">
                <option value="">—</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(old('user_id', $customer->user_id ?? '') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Credit limit</label>
            <input type="number" step="0.01" min="0" name="credit_limit" class="input" value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}">
        </div>
    </div>
    <div>
        <label class="label">Billing address</label>
        <textarea name="billing_address" rows="2" class="input">{{ old('billing_address', $customer->billing_address ?? '') }}</textarea>
    </div>
    <div>
        <label class="label">Shipping address</label>
        <textarea name="shipping_address" rows="2" class="input">{{ old('shipping_address', $customer->shipping_address ?? '') }}</textarea>
    </div>
    <div>
        <label class="label">Notes</label>
        <textarea name="notes" rows="2" class="input">{{ old('notes', $customer->notes ?? '') }}</textarea>
    </div>
    <label class="flex items-center gap-2 text-sm text-muted">
        <input type="checkbox" name="is_active" value="1" class="rounded border-line bg-slate-900 text-teal-400" @checked(old('is_active', $customer->is_active ?? true))>
        Active customer
    </label>
    <div class="flex gap-3">
        <button class="btn-primary">Save</button>
        <a href="{{ route('customers.index') }}" class="btn-secondary">Cancel</a>
    </div>
</form>
