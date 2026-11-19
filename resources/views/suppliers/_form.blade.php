<form method="POST" action="{{ $action }}" class="card w-full max-w-4xl space-y-5 p-4 sm:p-6">
    @csrf
    @if(!empty($method)) @method($method) @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label">Supplier name <span class="text-ss-danger">*</span></label>
            <input name="name" class="input" value="{{ old('name', $supplier->name ?? '') }}" required>
        </div>
        <div>
            <label class="label">Supplier code <span class="text-ss-danger">*</span></label>
            <input name="code" class="input" value="{{ old('code', $supplier->code ?? ($suggestedCode ?? '')) }}" required>
        </div>
        <div>
            <label class="label">Company name</label>
            <input name="company_name" class="input" value="{{ old('company_name', $supplier->company_name ?? '') }}">
        </div>
        <div>
            <label class="label">Contact person</label>
            <input name="contact_person" class="input" value="{{ old('contact_person', $supplier->contact_person ?? '') }}">
        </div>
        <div>
            <label class="label">Email</label>
            <input type="email" name="email" class="input" value="{{ old('email', $supplier->email ?? '') }}">
        </div>
        <div>
            <label class="label">Phone</label>
            <input name="phone" class="input" value="{{ old('phone', $supplier->phone ?? '') }}">
        </div>
        <div>
            <label class="label">GST number</label>
            <input name="gst_number" class="input" value="{{ old('gst_number', $supplier->gst_number ?? '') }}">
        </div>
        <div>
            <label class="label">Payment terms</label>
            <input name="payment_terms" class="input" value="{{ old('payment_terms', $supplier->payment_terms ?? '') }}" placeholder="e.g. Net 30">
        </div>
        <div>
            <label class="label">City</label>
            <input name="city" class="input" value="{{ old('city', $supplier->city ?? '') }}">
        </div>
        <div>
            <label class="label">State</label>
            <input name="state" class="input" value="{{ old('state', $supplier->state ?? '') }}">
        </div>
        <div>
            <label class="label">Pincode</label>
            <input name="pincode" class="input" value="{{ old('pincode', $supplier->pincode ?? '') }}">
        </div>
    </div>

    <div>
        <label class="label">Billing address</label>
        <textarea name="billing_address" rows="2" class="input">{{ old('billing_address', $supplier->billing_address ?? '') }}</textarea>
    </div>
    <div>
        <label class="label">Notes</label>
        <textarea name="notes" rows="2" class="input">{{ old('notes', $supplier->notes ?? '') }}</textarea>
    </div>

    <label class="flex items-center gap-2 text-sm text-ss-muted">
        <input type="checkbox" name="is_active" value="1" class="rounded border-white/20 bg-[#12111F] text-ss-primary" @checked(old('is_active', $supplier->is_active ?? true))>
        Active supplier
    </label>

    <div class="flex flex-col gap-3 sm:flex-row">
        <button class="btn-primary w-full justify-center sm:w-auto">Save supplier</button>
        <a href="{{ isset($supplier) && $supplier?->id ? route('suppliers.show', $supplier) : route('suppliers.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Cancel</a>
    </div>
</form>
