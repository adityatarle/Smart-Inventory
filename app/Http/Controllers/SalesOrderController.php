<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Sales\Concerns\ScopesSalesByCustomer;
use App\Models\BlanketOrder;
use App\Models\BlanketOrderItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesPartner;
use App\Models\SalesPerson;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Support\DocumentNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesOrderController extends Controller
{
    use ScopesSalesByCustomer;

    public function __construct(private StockService $stockService)
    {
    }

    public function index()
    {
        $orders = $this->scopeForCustomerRole(
            SalesOrder::query()->with(['warehouse', 'customer', 'salesPerson', 'createdBy'])
        )->latest()->paginate(12);

        return view('sales.orders.index', compact('orders'));
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()->canOperateOrders() || auth()->user()->isCustomer(), 403);

        return view('sales.orders.create', array_merge($this->formData(), [
            'blanketOrderId' => $request->integer('blanket_order_id') ?: null,
        ]));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'sales_person_id' => ['nullable', 'exists:sales_persons,id'],
            'sales_partner_id' => ['nullable', 'exists:sales_partners,id'],
            'blanket_order_id' => ['nullable', 'exists:blanket_orders,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $linked = $this->linkedCustomer();
        if ($user->isCustomer()) {
            abort_unless($linked && (int) $data['customer_id'] === $linked->id, 403);
        }

        try {
            $order = DB::transaction(function () use ($data, $user) {
                $customer = Customer::findOrFail($data['customer_id']);

                foreach ($data['items'] as $item) {
                    $this->stockService->ensureAvailable(
                        (int) $data['warehouse_id'],
                        (int) $item['product_id'],
                        (int) $item['quantity']
                    );
                }

                if (! empty($data['blanket_order_id'])) {
                    $this->consumeBlanketQuantities((int) $data['blanket_order_id'], $data['items']);
                }

                $total = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']);

                $order = SalesOrder::create([
                    'order_number' => DocumentNumber::make('SO'),
                    'warehouse_id' => $data['warehouse_id'],
                    'customer_id' => $customer->id,
                    'sales_person_id' => $data['sales_person_id'] ?? $customer->sales_person_id,
                    'sales_partner_id' => $data['sales_partner_id'] ?? $customer->sales_partner_id,
                    'blanket_order_id' => $data['blanket_order_id'] ?? null,
                    'created_by' => $user->id,
                    'customer_name' => $customer->name,
                    'status' => 'confirmed',
                    'total' => $total,
                    'notes' => $data['notes'] ?? null,
                    'confirmed_at' => now(),
                ]);

                foreach ($data['items'] as $item) {
                    SalesOrderItem::create([
                        'sales_order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                    ]);
                }

                return $order;
            });
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('sales-orders.show', $order)->with('success', 'Sales order created.');
    }

    public function show(SalesOrder $salesOrder)
    {
        $this->authorizeRecord($salesOrder->customer_id);
        $salesOrder->load([
            'warehouse', 'customer', 'salesPerson', 'partner', 'quotation', 'blanketOrder',
            'createdBy', 'items.product', 'invoice',
        ]);

        return view('sales.orders.show', compact('salesOrder'));
    }

    public function fulfill(SalesOrder $salesOrder)
    {
        abort_unless(Auth::user()->canOperateOrders(), 403);

        if ($salesOrder->status === 'fulfilled') {
            return back()->withErrors(['status' => 'Order already fulfilled.']);
        }

        if ($salesOrder->status === 'cancelled') {
            return back()->withErrors(['status' => 'Cancelled orders cannot be fulfilled.']);
        }

        try {
            DB::transaction(function () use ($salesOrder) {
                $salesOrder->load('items');

                foreach ($salesOrder->items as $item) {
                    $this->stockService->ensureAvailable(
                        $salesOrder->warehouse_id,
                        $item->product_id,
                        $item->quantity
                    );

                    $this->stockService->adjust(
                        $salesOrder->warehouse_id,
                        $item->product_id,
                        -$item->quantity,
                        'out',
                        $salesOrder,
                        Auth::id(),
                        "SO {$salesOrder->order_number} fulfilled"
                    );
                }

                $salesOrder->update([
                    'status' => 'fulfilled',
                    'fulfilled_at' => now(),
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'Sales order fulfilled and stock deducted.');
    }

    public function createInvoice(SalesOrder $salesOrder)
    {
        abort_unless(Auth::user()->canOperateOrders(), 403);

        if ($salesOrder->invoice) {
            return redirect()->route('sales-invoices.show', $salesOrder->invoice)
                ->with('success', 'Invoice already exists for this order.');
        }

        if ($salesOrder->status === 'cancelled') {
            return back()->withErrors(['status' => 'Cannot invoice a cancelled order.']);
        }

        $invoice = DB::transaction(function () use ($salesOrder) {
            $salesOrder->load('items');

            $invoice = SalesInvoice::create([
                'invoice_number' => DocumentNumber::make('INV'),
                'sales_order_id' => $salesOrder->id,
                'customer_id' => $salesOrder->customer_id,
                'warehouse_id' => $salesOrder->warehouse_id,
                'sales_person_id' => $salesOrder->sales_person_id,
                'sales_partner_id' => $salesOrder->sales_partner_id,
                'created_by' => Auth::id(),
                'status' => 'submitted',
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(15)->toDateString(),
                'total' => $salesOrder->total,
                'amount_paid' => 0,
                'notes' => 'Generated from '.$salesOrder->order_number,
            ]);

            foreach ($salesOrder->items as $item) {
                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                ]);
            }

            return $invoice;
        });

        return redirect()->route('sales-invoices.show', $invoice)->with('success', 'Sales invoice created.');
    }

    public function cancel(SalesOrder $salesOrder)
    {
        $this->authorizeRecord($salesOrder->customer_id);

        if ($salesOrder->status === 'fulfilled') {
            return back()->withErrors(['status' => 'Fulfilled orders cannot be cancelled.']);
        }

        $salesOrder->update(['status' => 'cancelled']);

        return back()->with('success', 'Sales order cancelled.');
    }

    private function formData(): array
    {
        $linked = $this->linkedCustomer();

        return [
            'customers' => $linked
                ? collect([$linked])
                : Customer::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'persons' => SalesPerson::where('is_active', true)->orderBy('name')->get(),
            'partners' => SalesPartner::where('is_active', true)->orderBy('name')->get(),
            'blankets' => BlanketOrder::where('status', 'active')->with('customer')->latest()->get(),
            'isCustomer' => auth()->user()->isCustomer(),
        ];
    }

    private function consumeBlanketQuantities(int $blanketOrderId, array $items): void
    {
        $blanket = BlanketOrder::query()->whereKey($blanketOrderId)->lockForUpdate()->firstOrFail();

        if ($blanket->status !== 'active') {
            throw new InvalidArgumentException('Blanket order is not active.');
        }

        foreach ($items as $item) {
            $line = BlanketOrderItem::query()
                ->where('blanket_order_id', $blanket->id)
                ->where('product_id', $item['product_id'])
                ->lockForUpdate()
                ->first();

            if (! $line) {
                throw new InvalidArgumentException('Product is not part of the selected blanket order.');
            }

            if ($line->remainingQuantity() < (int) $item['quantity']) {
                throw new InvalidArgumentException(
                    'Blanket remaining qty exceeded for product #'.$item['product_id'].'. Remaining: '.$line->remainingQuantity()
                );
            }

            $line->increment('ordered_quantity', (int) $item['quantity']);
        }

        $blanket->load('items');
        if ($blanket->items->every(fn (BlanketOrderItem $i) => $i->remainingQuantity() === 0)) {
            $blanket->update(['status' => 'completed']);
        }
    }

    private function authorizeRecord(?int $customerId): void
    {
        $linked = $this->linkedCustomer();
        if (Auth::user()->isCustomer() && $linked?->id !== $customerId) {
            abort(403);
        }
    }
}
