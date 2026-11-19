<form method="POST" action="{{ $action }}" class="card w-full max-w-4xl space-y-5 p-4 sm:p-6">
    @csrf
    @if(!empty($method)) @method($method) @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label">Customer name <span class="text-ss-danger">*</span></label>
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
            <label class="label">GST number</label>
            <input name="gst_number" class="input" value="{{ old('gst_number', $customer->gst_number ?? '') }}">
        </div>
        <div>
            <label class="label">Payment terms</label>
            <input name="payment_terms" class="input" value="{{ old('payment_terms', $customer->payment_terms ?? '') }}" placeholder="e.g. Net 15">
        </div>
        <div>
            <label class="label">City</label>
            <input name="city" class="input" value="{{ old('city', $customer->city ?? '') }}">
        </div>
        <div>
            <label class="label">State</label>
            <input name="state" class="input" value="{{ old('state', $customer->state ?? '') }}">
        </div>
        <div>
            <label class="label">Pincode</label>
            <input name="pincode" class="input" value="{{ old('pincode', $customer->pincode ?? '') }}">
        </div>
        <div>
            <label class="label">Credit limit</label>
            <input type="number" step="0.01" min="0" name="credit_limit" class="input" value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}">
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
        <div class="sm:col-span-2">
            <label class="label">Linked login user</label>
            <select name="user_id" class="input">
                <option value="">—</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(old('user_id', $customer->user_id ?? '') == $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
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

    <label class="flex items-center gap-2 text-sm text-ss-muted">
        <input type="checkbox" name="is_active" value="1" class="rounded border-white/20 bg-[#12111F] text-ss-primary" @checked(old('is_active', $customer->is_active ?? true))>
        Active customer
    </label>

    <div class="flex flex-col gap-3 sm:flex-row">
        <button class="btn-primary w-full justify-center sm:w-auto">Save customer</button>
        <a href="{{ isset($customer) && $customer?->id ? route('customers.show', $customer) : route('customers.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Cancel</a>
    </div>
</form>
