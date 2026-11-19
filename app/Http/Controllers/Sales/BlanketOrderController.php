<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ScopesSalesByCustomer;
use App\Models\BlanketOrder;
use App\Models\BlanketOrderItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesPartner;
use App\Models\SalesPerson;
use App\Models\Warehouse;
use App\Support\DocumentNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BlanketOrderController extends Controller
{
    use ScopesSalesByCustomer;

    public function index()
    {
        $orders = $this->scopeForCustomerRole(
            BlanketOrder::query()->with(['customer', 'warehouse', 'salesPerson'])
        )->latest()->paginate(12);

        return view('sales.blankets.index', compact('orders'));
    }

    public function create()
    {
        abort_unless(auth()->user()->canOperateOrders(), 403);

        return view('sales.blankets.create', $this->formData());
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canOperateOrders(), 403);
        $data = $this->validated($request);

        $order = DB::transaction(function () use ($data) {
            $customer = Customer::findOrFail($data['customer_id']);
            $total = collect($data['items'])->sum(fn ($i) => $i['agreed_quantity'] * $i['unit_price']);

            $order = BlanketOrder::create([
                'blanket_number' => DocumentNumber::make('BO'),
                'customer_id' => $customer->id,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'sales_person_id' => $data['sales_person_id'] ?? $customer->sales_person_id,
                'sales_partner_id' => $data['sales_partner_id'] ?? $customer->sales_partner_id,
                'created_by' => Auth::id(),
                'status' => 'active',
                'start_date' => $data['start_date'] ?? now()->toDateString(),
                'end_date' => $data['end_date'] ?? now()->addYear()->toDateString(),
                'total_value' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                BlanketOrderItem::create([
                    'blanket_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'agreed_quantity' => $item['agreed_quantity'],
                    'ordered_quantity' => 0,
                    'unit_price' => $item['unit_price'],
                ]);
            }

            return $order;
        });

        return redirect()->route('blanket-orders.show', $order)->with('success', 'Blanket order created.');
    }

    public function show(BlanketOrder $blanketOrder)
    {
        $this->authorizeRecord($blanketOrder->customer_id);
        $blanketOrder->load(['customer', 'warehouse', 'salesPerson', 'partner', 'createdBy', 'items.product', 'salesOrders']);

        return view('sales.blankets.show', compact('blanketOrder'));
    }

    public function cancel(BlanketOrder $blanketOrder)
    {
        abort_unless(auth()->user()->canOperateOrders(), 403);

        if ($blanketOrder->status === 'completed') {
            return back()->withErrors(['status' => 'Completed blanket orders cannot be cancelled.']);
        }

        $blanketOrder->update(['status' => 'cancelled']);

        return back()->with('success', 'Blanket order cancelled.');
    }

    private function formData(): array
    {
        return [
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'persons' => SalesPerson::where('is_active', true)->orderBy('name')->get(),
            'partners' => SalesPartner::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'sales_person_id' => ['nullable', 'exists:sales_persons,id'],
            'sales_partner_id' => ['nullable', 'exists:sales_partners,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.agreed_quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function authorizeRecord(?int $customerId): void
    {
        $linked = $this->linkedCustomer();
        if (auth()->user()->isCustomer() && $linked?->id !== $customerId) {
            abort(403);
        }
    }
}
