<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ScopesSalesByCustomer;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesPartner;
use App\Models\SalesPerson;
use App\Models\Warehouse;
use App\Support\DocumentNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    use ScopesSalesByCustomer;

    public function index()
    {
        $quotations = $this->scopeForCustomerRole(
            Quotation::query()->with(['customer', 'warehouse', 'salesPerson'])
        )->latest()->paginate(12);

        return view('sales.quotations.index', compact('quotations'));
    }

    public function create()
    {
        abort_unless(auth()->user()->canOperateOrders(), 403);

        return view('sales.quotations.create', $this->formData());
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canOperateOrders(), 403);
        $data = $this->validated($request);

        $quotation = DB::transaction(function () use ($data) {
            $customer = Customer::findOrFail($data['customer_id']);
            $total = collect($data['items'])->sum(fn ($i) => $i['quantity'] * $i['unit_price']);

            $quotation = Quotation::create([
                'quotation_number' => DocumentNumber::make('QT'),
                'customer_id' => $customer->id,
                'warehouse_id' => $data['warehouse_id'],
                'sales_person_id' => $data['sales_person_id'] ?? $customer->sales_person_id,
                'sales_partner_id' => $data['sales_partner_id'] ?? $customer->sales_partner_id,
                'created_by' => Auth::id(),
                'status' => $data['status'] ?? 'sent',
                'valid_until' => $data['valid_until'] ?? now()->addDays(14)->toDateString(),
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            return $quotation;
        });

        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation created.');
    }

    public function show(Quotation $quotation)
    {
        $this->authorizeRecord($quotation->customer_id);
        $quotation->load(['customer', 'warehouse', 'salesPerson', 'partner', 'createdBy', 'items.product', 'salesOrder']);

        return view('sales.quotations.show', compact('quotation'));
    }

    public function updateStatus(Request $request, Quotation $quotation)
    {
        abort_unless(auth()->user()->canOperateOrders(), 403);

        $data = $request->validate([
            'status' => ['required', 'in:draft,sent,accepted,rejected,expired'],
        ]);

        if ($quotation->status === 'converted') {
            return back()->withErrors(['status' => 'Converted quotations cannot change status.']);
        }

        $quotation->update(['status' => $data['status']]);

        return back()->with('success', 'Quotation status updated.');
    }

    public function convert(Quotation $quotation)
    {
        abort_unless(auth()->user()->canOperateOrders(), 403);

        if ($quotation->status === 'converted') {
            return back()->withErrors(['status' => 'Already converted.']);
        }

        if (! in_array($quotation->status, ['sent', 'accepted', 'draft'], true)) {
            return back()->withErrors(['status' => 'Only draft/sent/accepted quotations can convert.']);
        }

        $order = DB::transaction(function () use ($quotation) {
            $quotation->load(['items', 'customer']);

            $order = SalesOrder::create([
                'order_number' => DocumentNumber::make('SO'),
                'warehouse_id' => $quotation->warehouse_id,
                'customer_id' => $quotation->customer_id,
                'sales_person_id' => $quotation->sales_person_id,
                'sales_partner_id' => $quotation->sales_partner_id,
                'quotation_id' => $quotation->id,
                'created_by' => Auth::id(),
                'customer_name' => $quotation->customer?->name,
                'status' => 'confirmed',
                'total' => $quotation->total,
                'notes' => 'Converted from '.$quotation->quotation_number,
                'confirmed_at' => now(),
            ]);

            foreach ($quotation->items as $item) {
                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                ]);
            }

            $quotation->update([
                'status' => 'converted',
                'converted_at' => now(),
            ]);

            return $order;
        });

        return redirect()->route('sales-orders.show', $order)->with('success', 'Quotation converted to sales order.');
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
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'sales_person_id' => ['nullable', 'exists:sales_persons,id'],
            'sales_partner_id' => ['nullable', 'exists:sales_partners,id'],
            'status' => ['nullable', 'in:draft,sent'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
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
