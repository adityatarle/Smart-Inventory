<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ScopesSalesByCustomer;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\SalesPartner;
use App\Models\SalesPerson;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    use ScopesSalesByCustomer;

    public function __construct(private readonly CustomerService $customers)
    {
    }

    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $customers = $this->scopeForCustomerRole(
            Customer::query()->with(['partner', 'salesPerson']),
            'id'
        )
            ->search($request->get('search'))
            ->active($request->get('status'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $statsQuery = $this->scopeForCustomerRole(Customer::query(), 'id');

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('is_active', true)->count(),
            'inactive' => (clone $statsQuery)->where('is_active', false)->count(),
            'credit' => (float) (clone $statsQuery)->sum('credit_limit'),
        ];

        return view('sales.customers.index', compact('customers', 'stats'));
    }

    public function create()
    {
        abort_unless($this->canManage(), 403);

        return view('sales.customers.create', $this->formData());
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = $this->customers->create($request->validated());

        return redirect()->route('customers.show', $customer)->with('success', 'Customer created.');
    }

    public function show(Customer $customer)
    {
        abort_unless($this->canView(), 403);
        $this->authorizeCustomer($customer);

        $customer->load(['partner', 'salesPerson', 'user']);
        $customer->loadCount(['salesOrders', 'quotations', 'invoices', 'blanketOrders']);

        $salesOrders = $customer->salesOrders()->latest()->take(8)->get();
        $quotations = $customer->quotations()->latest()->take(5)->get();

        return view('sales.customers.show', [
            'customer' => $customer,
            'salesOrders' => $salesOrders,
            'quotations' => $quotations,
            'blockers' => $this->customers->deletionBlockers($customer),
        ]);
    }

    public function edit(Customer $customer)
    {
        abort_unless($this->canManage(), 403);
        $this->authorizeCustomer($customer);

        return view('sales.customers.edit', array_merge($this->formData(), compact('customer')));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $this->authorizeCustomer($customer);
        $this->customers->update($customer, $request->validated());

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        abort_unless(
            auth()->user()->hasPermission('customers.delete')
            || auth()->user()->hasPermission('customers.manage')
            || auth()->user()->canManageInventory(),
            403
        );

        try {
            $this->customers->delete($customer);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }

    private function formData(): array
    {
        return [
            'partners' => SalesPartner::where('is_active', true)->orderBy('name')->get(),
            'persons' => SalesPerson::where('is_active', true)->orderBy('name')->get(),
            'users' => User::query()
                ->where(function ($q) {
                    $q->where('role', 'customer')
                        ->orWhereDoesntHave('roles');
                })
                ->orderBy('name')
                ->get(),
        ];
    }

    private function authorizeCustomer(Customer $customer): void
    {
        $linked = $this->linkedCustomer();
        if (auth()->user()->isCustomer() && $linked?->id !== $customer->id) {
            abort(403);
        }
    }

    private function canView(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('customers.view')
            || $user->hasPermission('customers.manage')
            || $user->canOperateOrders()
            || $user->isCustomer();
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('customers.create')
            || $user->hasPermission('customers.update')
            || $user->hasPermission('customers.manage')
            || $user->canOperateOrders();
    }
}
