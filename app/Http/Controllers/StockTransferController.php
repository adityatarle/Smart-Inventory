<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceiveStockTransferRequest;
use App\Http\Requests\StoreStockTransferRequest;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StockTransferController extends Controller
{
    public function __construct(private readonly StockTransferService $transfers)
    {
    }

    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $transfers = StockTransfer::query()
            ->with(['fromWarehouse', 'toWarehouse', 'creator'])
            ->withCount('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('warehouse_id'), function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->where('from_warehouse_id', $request->warehouse_id)
                        ->orWhere('to_warehouse_id', $request->warehouse_id);
                });
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->where('document_number', 'like', '%'.$request->search.'%')
                        ->orWhere('reference_number', 'like', '%'.$request->search.'%');
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('inventory.transfers.index', [
            'transfers' => $transfers,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        abort_unless($this->canManage(), 403);

        return view('inventory.transfers.create', $this->formData());
    }

    public function store(StoreStockTransferRequest $request)
    {
        $data = $request->validated();
        $transfer = $this->transfers->createDraft(
            [
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'reference_number' => $data['reference_number'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'remarks' => $data['remarks'] ?? null,
            ],
            $data['items']
        );

        if ($request->boolean('submit')) {
            try {
                $this->transfers->submit($transfer);
            } catch (ValidationException $e) {
                return redirect()->route('stock-transfers.edit', $transfer)->withErrors($e->errors());
            }

            return redirect()->route('stock-transfers.show', $transfer)->with('success', 'Transfer dispatched. Stock is in transit.');
        }

        return redirect()->route('stock-transfers.show', $transfer)->with('success', 'Draft transfer saved.');
    }

    public function show(StockTransfer $stockTransfer)
    {
        abort_unless($this->canView(), 403);
        $stockTransfer->load(['items.product', 'fromWarehouse', 'toWarehouse', 'creator', 'submitter']);

        return view('inventory.transfers.show', ['transfer' => $stockTransfer]);
    }

    public function edit(StockTransfer $stockTransfer)
    {
        abort_unless($this->canManage(), 403);
        abort_unless($stockTransfer->isDraft(), 403, 'Only draft transfers can be edited.');

        return view('inventory.transfers.edit', array_merge($this->formData(), [
            'transfer' => $stockTransfer->load('items'),
        ]));
    }

    public function update(StoreStockTransferRequest $request, StockTransfer $stockTransfer)
    {
        try {
            $data = $request->validated();
            $this->transfers->updateDraft(
                $stockTransfer,
                [
                    'from_warehouse_id' => $data['from_warehouse_id'],
                    'to_warehouse_id' => $data['to_warehouse_id'],
                    'reference_number' => $data['reference_number'] ?? null,
                    'transaction_date' => $data['transaction_date'],
                    'remarks' => $data['remarks'] ?? null,
                ],
                $data['items']
            );

            if ($request->boolean('submit')) {
                $this->transfers->submit($stockTransfer->fresh());

                return redirect()->route('stock-transfers.show', $stockTransfer)->with('success', 'Transfer dispatched. Stock is in transit.');
            }
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('stock-transfers.show', $stockTransfer)->with('success', 'Draft updated.');
    }

    public function submit(StockTransfer $stockTransfer)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->transfers->submit($stockTransfer);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('stock-transfers.show', $stockTransfer)->with('success', 'Transfer dispatched.');
    }

    public function receiveForm(StockTransfer $stockTransfer)
    {
        abort_unless($this->canManage(), 403);
        abort_unless($stockTransfer->isOpenForReceive(), 403, 'This transfer is not open for receiving.');

        $stockTransfer->load(['items.product', 'fromWarehouse', 'toWarehouse']);

        return view('inventory.transfers.receive', ['transfer' => $stockTransfer]);
    }

    public function receive(ReceiveStockTransferRequest $request, StockTransfer $stockTransfer)
    {
        try {
            $this->transfers->receive($stockTransfer, $request->validated()['receipts']);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('stock-transfers.show', $stockTransfer)->with('success', 'Transfer receipt posted.');
    }

    public function cancel(StockTransfer $stockTransfer)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->transfers->cancel($stockTransfer);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('stock-transfers.show', $stockTransfer)->with('success', 'Transfer cancelled.');
    }

    public function destroy(StockTransfer $stockTransfer)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->transfers->deleteDraft($stockTransfer);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('stock-transfers.index')->with('success', 'Draft transfer deleted.');
    }

    public function availability(Request $request)
    {
        abort_unless($this->canView(), 403);

        $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $stock = WarehouseStock::query()
            ->where('warehouse_id', $request->warehouse_id)
            ->where('product_id', $request->product_id)
            ->first();

        return response()->json([
            'quantity' => (int) ($stock?->quantity ?? 0),
            'available' => (int) ($stock?->availableQuantity() ?? 0),
        ]);
    }

    private function formData(): array
    {
        return [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'unit']),
        ];
    }

    private function canView(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('inventory.view')
            || $user->hasPermission('inventory.transfer')
            || $user->hasPermission('inventory.manage')
            || $user->canManageInventory();
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('inventory.transfer')
            || $user->hasPermission('inventory.manage')
            || $user->canManageInventory();
    }
}
