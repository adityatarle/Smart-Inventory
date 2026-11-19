<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ScopesSalesByCustomer;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesPartner;
use App\Models\SalesPerson;
use App\Models\Warehouse;
use App\Support\DocumentNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    use ScopesSalesByCustomer;

    public function index()
    {
        $invoices = $this->scopeForCustomerRole(
            SalesInvoice::query()->with(['customer', 'warehouse', 'salesOrder', 'salesPerson'])
        )->latest()->paginate(12);

        return view('sales.invoices.index', compact('invoices'));
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()->canOperateOrders(), 403);

        return view('sales.invoices.create', array_merge($this->formData(), [
            'salesOrderId' => $request->integer('sales_order_id') ?: null,
        ]));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canOperateOrders(), 403);

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'sales_order_id' => ['nullable', 'exists:sales_orders,id'],
            'sales_person_id' => ['nullable', 'exists:sales_persons,id'],
            'sales_partner_id' => ['nullable', 'exists:sales_partners,id'],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $invoice = DB::transaction(function () use ($data) {
            $customer = Customer::findOrFail($data['customer_id']);
            $total = collect($data['items'])->sum(fn ($i) => $i['quantity'] * $i['unit_price']);

            $invoice = SalesInvoice::create([
                'invoice_number' => DocumentNumber::make('INV'),
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'customer_id' => $customer->id,
                'warehouse_id' => $data['warehouse_id'],
                'sales_person_id' => $data['sales_person_id'] ?? $customer->sales_person_id,
                'sales_partner_id' => $data['sales_partner_id'] ?? $customer->sales_partner_id,
                'created_by' => Auth::id(),
                'status' => 'submitted',
                'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? now()->addDays(15)->toDateString(),
                'total' => $total,
                'amount_paid' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            return $invoice;
        });

        return redirect()->route('sales-invoices.show', $invoice)->with('success', 'Sales invoice created.');
    }

    public function show(SalesInvoice $salesInvoice)
    {
        $this->authorizeRecord($salesInvoice->customer_id);
        $salesInvoice->load(['customer', 'warehouse', 'salesOrder', 'salesPerson', 'partner', 'createdBy', 'items.product']);

        return view('sales.invoices.show', compact('salesInvoice'));
    }

    public function markPaid(SalesInvoice $salesInvoice)
    {
        abort_unless(auth()->user()->canOperateOrders(), 403);

        if ($salesInvoice->status === 'cancelled') {
            return back()->withErrors(['status' => 'Cancelled invoices cannot be paid.']);
        }

        $salesInvoice->update([
            'status' => 'paid',
            'amount_paid' => $salesInvoice->total,
            'paid_at' => now(),
        ]);

        return back()->with('success', 'Invoice marked as paid.');
    }

    public function cancel(SalesInvoice $salesInvoice)
    {
        abort_unless(auth()->user()->canOperateOrders(), 403);

        if ($salesInvoice->status === 'paid') {
            return back()->withErrors(['status' => 'Paid invoices cannot be cancelled.']);
        }

        $salesInvoice->update(['status' => 'cancelled']);

        return back()->with('success', 'Invoice cancelled.');
    }

    private function formData(): array
    {
        return [
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'persons' => SalesPerson::where('is_active', true)->orderBy('name')->get(),
            'partners' => SalesPartner::where('is_active', true)->orderBy('name')->get(),
            'orders' => SalesOrder::with('customer')->whereIn('status', ['confirmed', 'fulfilled'])->latest()->take(50)->get(),
        ];
    }

    private function authorizeRecord(?int $customerId): void
    {
        $linked = $this->linkedCustomer();
        if (auth()->user()->isCustomer() && $linked?->id !== $customerId) {
            abort(403);
        }
    }
}
