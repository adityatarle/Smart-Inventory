<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ScopesSalesByCustomer;
use App\Models\Customer;
use App\Models\SalesPartner;
use App\Models\SalesPerson;
use App\Models\User;
use App\Support\DocumentNumber;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ScopesSalesByCustomer;

    public function index()
    {
        $customers = $this->scopeForCustomerRole(Customer::query()->with(['partner', 'salesPerson']), 'id')
            ->latest()
            ->paginate(12);

        return view('sales.customers.index', compact('customers'));
    }

    public function create()
    {
        abort_unless(auth()->user()->canOperateOrders(), 403);

        return view('sales.customers.create', $this->formData());
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canOperateOrders(), 403);

        Customer::create($this->validated($request) + [
            'code' => DocumentNumber::make('CUS'),
        ]);

        return redirect()->route('customers.index')->with('success', 'Customer created.');
    }

    public function show(Customer $customer)
    {
        $this->authorizeCustomer($customer);
        $customer->load(['partner', 'salesPerson', 'user', 'quotations', 'salesOrders', 'invoices', 'blanketOrders']);

        return view('sales.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        abort_unless(auth()->user()->canOperateOrders(), 403);

        return view('sales.customers.edit', array_merge($this->formData(), compact('customer')));
    }

    public function update(Request $request, Customer $customer)
    {
        abort_unless(auth()->user()->canOperateOrders(), 403);
        $customer->update($this->validated($request, $customer));

        return redirect()->route('customers.index')->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        abort_unless(auth()->user()->canManageInventory(), 403);
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }

    private function formData(): array
    {
        return [
            'partners' => SalesPartner::where('is_active', true)->orderBy('name')->get(),
            'persons' => SalesPerson::where('is_active', true)->orderBy('name')->get(),
            'users' => User::where('role', User::ROLE_CUSTOMER)->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, ?Customer $customer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'billing_address' => ['nullable', 'string'],
            'shipping_address' => ['nullable', 'string'],
            'user_id' => ['nullable', 'exists:users,id'],
            'sales_partner_id' => ['nullable', 'exists:sales_partners,id'],
            'sales_person_id' => ['nullable', 'exists:sales_persons,id'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }

    private function authorizeCustomer(Customer $customer): void
    {
        $linked = $this->linkedCustomer();
        if (auth()->user()->isCustomer() && $linked?->id !== $customer->id) {
            abort(403);
        }
    }
}
