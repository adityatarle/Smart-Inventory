<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PurchaseOrderController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    public function index()
    {
        $orders = PurchaseOrder::query()
            ->with(['warehouse', 'orderedBy'])
            ->latest()
            ->paginate(12);

        return view('purchase-orders.index', compact('orders'));
    }

    public function create()
    {
        return view('purchase-orders.create', [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $order = DB::transaction(function () use ($data) {
            $total = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_cost']);

            $order = PurchaseOrder::create([
                'order_number' => 'PO-'.strtoupper(Str::random(8)),
                'warehouse_id' => $data['warehouse_id'],
                'ordered_by' => Auth::id(),
                'supplier_name' => $data['supplier_name'],
                'status' => 'ordered',
                'total' => $total,
                'notes' => $data['notes'] ?? null,
                'ordered_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                ]);
            }

            return $order;
        });

        return redirect()->route('purchase-orders.show', $order)->with('success', 'Purchase order created.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['warehouse', 'orderedBy', 'items.product']);

        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    public function receive(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status === 'received') {
            return back()->withErrors(['status' => 'Order already received.']);
        }

        if ($purchaseOrder->status === 'cancelled') {
            return back()->withErrors(['status' => 'Cancelled orders cannot be received.']);
        }

        try {
            DB::transaction(function () use ($purchaseOrder) {
                $purchaseOrder->load('items');

                foreach ($purchaseOrder->items as $item) {
                    $qty = $item->quantity - $item->received_quantity;
                    if ($qty <= 0) {
                        continue;
                    }

                    $this->stockService->adjust(
                        $purchaseOrder->warehouse_id,
                        $item->product_id,
                        $qty,
                        'in',
                        $purchaseOrder,
                        Auth::id(),
                        "PO {$purchaseOrder->order_number} received"
                    );

                    $item->update(['received_quantity' => $item->quantity]);
                }

                $purchaseOrder->update([
                    'status' => 'received',
                    'received_at' => now(),
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'Purchase order received and stock updated.');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status === 'received') {
            return back()->withErrors(['status' => 'Received orders cannot be cancelled.']);
        }

        $purchaseOrder->update(['status' => 'cancelled']);

        return back()->with('success', 'Purchase order cancelled.');
    }
}
