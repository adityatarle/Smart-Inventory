<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockReceiptRequest;
use App\Models\Product;
use App\Models\StockReceipt;
use App\Models\Warehouse;
use App\Services\StockInService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StockReceiptController extends Controller
{
    public function __construct(private readonly StockInService $stockIn)
    {
    }

    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $receipts = StockReceipt::query()
            ->with(['warehouse', 'creator'])
            ->withCount('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->where('document_number', 'like', '%'.$request->search.'%')
                        ->orWhere('reference_number', 'like', '%'.$request->search.'%');
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('inventory.receipts.index', [
            'receipts' => $receipts,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        abort_unless($this->canManage(), 403);

        return view('inventory.receipts.create', $this->formData());
    }

    public function store(StoreStockReceiptRequest $request)
    {
        $data = $request->validated();
        $receipt = $this->stockIn->createDraft(
            [
                'warehouse_id' => $data['warehouse_id'],
                'reference_number' => $data['reference_number'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'remarks' => $data['remarks'] ?? null,
            ],
            $data['items']
        );

        if ($request->boolean('submit')) {
            try {
                $this->stockIn->submit($receipt);
            } catch (ValidationException $e) {
                return redirect()->route('stock-receipts.edit', $receipt)->withErrors($e->errors());
            }

            return redirect()->route('stock-receipts.show', $receipt)->with('success', 'Stock receipt submitted and inventory updated.');
        }

        return redirect()->route('stock-receipts.show', $receipt)->with('success', 'Draft stock receipt saved.');
    }

    public function show(StockReceipt $stockReceipt)
    {
        abort_unless($this->canView(), 403);
        $stockReceipt->load(['items.product', 'warehouse', 'creator', 'submitter']);

        return view('inventory.receipts.show', ['receipt' => $stockReceipt]);
    }

    public function edit(StockReceipt $stockReceipt)
    {
        abort_unless($this->canManage(), 403);
        abort_unless($stockReceipt->isDraft(), 403, 'Only draft receipts can be edited.');

        return view('inventory.receipts.edit', array_merge($this->formData(), [
            'receipt' => $stockReceipt->load('items'),
        ]));
    }

    public function update(StoreStockReceiptRequest $request, StockReceipt $stockReceipt)
    {
        try {
            $data = $request->validated();
            $this->stockIn->updateDraft(
                $stockReceipt,
                [
                    'warehouse_id' => $data['warehouse_id'],
                    'reference_number' => $data['reference_number'] ?? null,
                    'transaction_date' => $data['transaction_date'],
                    'remarks' => $data['remarks'] ?? null,
                ],
                $data['items']
            );

            if ($request->boolean('submit')) {
                $this->stockIn->submit($stockReceipt->fresh());

                return redirect()->route('stock-receipts.show', $stockReceipt)->with('success', 'Stock receipt submitted and inventory updated.');
            }
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('stock-receipts.show', $stockReceipt)->with('success', 'Draft updated.');
    }

    public function submit(StockReceipt $stockReceipt)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->stockIn->submit($stockReceipt);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('stock-receipts.show', $stockReceipt)->with('success', 'Stock receipt submitted.');
    }

    public function cancel(StockReceipt $stockReceipt)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->stockIn->cancel($stockReceipt);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('stock-receipts.show', $stockReceipt)->with('success', 'Stock receipt cancelled.');
    }

    public function destroy(StockReceipt $stockReceipt)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->stockIn->deleteDraft($stockReceipt);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('stock-receipts.index')->with('success', 'Draft receipt deleted.');
    }

    private function formData(): array
    {
        return [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'cost_price', 'unit']),
        ];
    }

    private function canView(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('inventory.view')
            || $user->hasPermission('inventory.receive')
            || $user->hasPermission('inventory.manage')
            || $user->canManageInventory();
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('inventory.receive')
            || $user->hasPermission('inventory.manage')
            || $user->canManageInventory();
    }
}
