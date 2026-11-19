<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGoodsReceiptRequest;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Services\GoodsReceiptService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GoodsReceiptController extends Controller
{
    public function __construct(private readonly GoodsReceiptService $receipts)
    {
    }

    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $receipts = GoodsReceipt::query()
            ->with(['purchaseOrder', 'supplier', 'warehouse', 'creator'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->where('document_number', 'like', '%'.$request->search.'%')
                        ->orWhereHas('purchaseOrder', fn ($po) => $po->where('order_number', 'like', '%'.$request->search.'%'));
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('goods-receipts.index', compact('receipts'));
    }

    public function create(Request $request)
    {
        abort_unless($this->canManage(), 403);

        $purchaseOrder = null;
        if ($request->filled('purchase_order_id')) {
            $purchaseOrder = PurchaseOrder::query()
                ->with(['items.product', 'supplier', 'warehouse'])
                ->findOrFail($request->integer('purchase_order_id'));
            abort_unless($purchaseOrder->canReceiveGoods(), 403, 'PO is not open for receiving.');
        }

        $openOrders = PurchaseOrder::query()
            ->with('supplier')
            ->whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED])
            ->latest()
            ->get();

        return view('goods-receipts.create', [
            'purchaseOrder' => $purchaseOrder,
            'openOrders' => $openOrders,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreGoodsReceiptRequest $request)
    {
        $data = $request->validated();
        $order = PurchaseOrder::query()->findOrFail($request->integer('purchase_order_id'));

        try {
            $receipt = $this->receipts->createDraft(
                $order,
                [
                    'warehouse_id' => $data['warehouse_id'],
                    'receipt_date' => $data['receipt_date'],
                    'remarks' => $data['remarks'] ?? null,
                ],
                $data['lines']
            );

            if ($request->boolean('submit')) {
                $this->receipts->submit($receipt);
            }
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('goods-receipts.show', $receipt)->with(
            'success',
            $request->boolean('submit') ? 'GRN submitted and inventory updated.' : 'Draft GRN saved.'
        );
    }

    public function show(GoodsReceipt $goodsReceipt)
    {
        abort_unless($this->canView(), 403);
        $goodsReceipt->load(['items.product', 'purchaseOrder', 'supplier', 'warehouse', 'creator', 'submitter']);

        return view('goods-receipts.show', [
            'receipt' => $goodsReceipt,
            'canManage' => $this->canManage(),
        ]);
    }

    public function submit(GoodsReceipt $goodsReceipt)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->receipts->submit($goodsReceipt);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'GRN submitted and inventory updated.');
    }

    public function cancel(GoodsReceipt $goodsReceipt)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->receipts->cancelDraft($goodsReceipt);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Draft GRN cancelled.');
    }

    public function destroy(GoodsReceipt $goodsReceipt)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->receipts->deleteDraft($goodsReceipt);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('goods-receipts.index')->with('success', 'Draft GRN deleted.');
    }

    private function canView(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('purchase_orders.view')
            || $user->hasPermission('purchase_orders.receive')
            || $user->hasPermission('purchase_orders.manage')
            || $user->canManageInventory()
            || $user->isAdmin()
            || $user->isManager();
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('purchase_orders.receive')
            || $user->hasPermission('purchase_orders.manage')
            || $user->canManageInventory()
            || $user->isAdmin()
            || $user->isManager();
    }
}
