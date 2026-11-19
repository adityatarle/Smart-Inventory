<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $orders)
    {
    }

    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $orders = PurchaseOrder::query()
            ->with(['warehouse', 'supplier', 'orderedBy'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->where('order_number', 'like', '%'.$request->search.'%')
                        ->orWhere('supplier_name', 'like', '%'.$request->search.'%');
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('purchase-orders.index', [
            'orders' => $orders,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name', 'code']),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'statuses' => PurchaseOrder::STATUSES,
        ]);
    }

    public function create()
    {
        abort_unless($this->canManage(), 403);

        return view('purchase-orders.create', $this->formData());
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        $data = $request->validated();
        $order = $this->orders->createDraft($this->header($data), $data['items']);

        if ($request->boolean('submit')) {
            try {
                $this->orders->submit($order);
            } catch (ValidationException $e) {
                return redirect()->route('purchase-orders.edit', $order)->withErrors($e->errors());
            }

            return redirect()->route('purchase-orders.show', $order)->with('success', 'Purchase order submitted for approval.');
        }

        return redirect()->route('purchase-orders.show', $order)->with('success', 'Draft purchase order saved.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        abort_unless($this->canView(), 403);
        $purchaseOrder->load([
            'warehouse', 'supplier', 'orderedBy', 'submitter', 'approver', 'rejector',
            'items.product', 'goodsReceipts' => fn ($q) => $q->latest(),
        ]);

        return view('purchase-orders.show', [
            'purchaseOrder' => $purchaseOrder,
            'canApprove' => $this->canApprove(),
            'canManage' => $this->canManage(),
            'canReceive' => $this->canReceive(),
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        abort_unless($this->canManage(), 403);
        abort_unless(in_array($purchaseOrder->status, [PurchaseOrder::STATUS_DRAFT, PurchaseOrder::STATUS_REJECTED], true), 403);

        return view('purchase-orders.edit', array_merge($this->formData(), [
            'purchaseOrder' => $purchaseOrder->load('items'),
        ]));
    }

    public function update(StorePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        try {
            $data = $request->validated();
            $this->orders->updateDraft($purchaseOrder, $this->header($data), $data['items']);

            if ($request->boolean('submit')) {
                $this->orders->submit($purchaseOrder->fresh());

                return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', 'Purchase order submitted for approval.');
            }
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', 'Draft updated.');
    }

    public function submit(PurchaseOrder $purchaseOrder)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->orders->submit($purchaseOrder);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Purchase order submitted for approval.');
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        abort_unless($this->canApprove(), 403);

        try {
            $this->orders->approve($purchaseOrder);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Purchase order approved.');
    }

    public function reject(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_unless($this->canApprove(), 403);

        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);

        try {
            $this->orders->reject($purchaseOrder, $data['rejection_reason']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Purchase order rejected.');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->orders->cancel($purchaseOrder);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Purchase order cancelled.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->orders->deleteDraft($purchaseOrder);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('purchase-orders.index')->with('success', 'Draft purchase order deleted.');
    }

    public function print(PurchaseOrder $purchaseOrder)
    {
        abort_unless($this->canView(), 403);
        $purchaseOrder->load(['warehouse', 'supplier', 'orderedBy', 'approver', 'items.product']);

        return view('purchase-orders.print', compact('purchaseOrder'));
    }

    /** @deprecated Direct PO receive replaced by GRN in Phase 4.5 */
    public function receive(PurchaseOrder $purchaseOrder)
    {
        abort_unless($this->canReceive(), 403);

        if (! $purchaseOrder->canReceiveGoods()) {
            return back()->withErrors(['status' => 'Create a Goods Receipt against an approved PO instead.']);
        }

        return redirect()->route('goods-receipts.create', ['purchase_order_id' => $purchaseOrder->id]);
    }

    private function header(array $data): array
    {
        return [
            'warehouse_id' => $data['warehouse_id'],
            'supplier_id' => $data['supplier_id'],
            'order_date' => $data['order_date'],
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'terms' => $data['terms'] ?? null,
        ];
    }

    private function formData(): array
    {
        return [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'cost_price', 'unit']),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function canView(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('purchase_orders.view')
            || $user->hasPermission('purchase_orders.manage')
            || $user->canManageInventory()
            || $user->isAdmin()
            || $user->isManager();
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('purchase_orders.create')
            || $user->hasPermission('purchase_orders.update')
            || $user->hasPermission('purchase_orders.manage')
            || $user->canManageInventory()
            || $user->isAdmin()
            || $user->isManager();
    }

    private function canApprove(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('purchase_orders.approve')
            || $user->hasPermission('purchase_orders.manage')
            || $user->isAdmin()
            || $user->isManager();
    }

    private function canReceive(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('purchase_orders.receive')
            || $user->hasPermission('purchase_orders.manage')
            || $user->canManageInventory()
            || $user->isAdmin()
            || $user->isManager();
    }
}
