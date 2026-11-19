<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockAdjustmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StockAdjustmentController extends Controller
{
    public function __construct(private readonly StockAdjustmentService $adjustments)
    {
    }

    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $adjustments = StockAdjustment::query()
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

        return view('inventory.adjustments.index', [
            'adjustments' => $adjustments,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        abort_unless($this->canManage(), 403);

        return view('inventory.adjustments.create', $this->formData());
    }

    public function store(StoreStockAdjustmentRequest $request)
    {
        $data = $request->validated();
        $adjustment = $this->adjustments->createDraft(
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
                $this->adjustments->submit($adjustment);
            } catch (ValidationException $e) {
                return redirect()->route('stock-adjustments.edit', $adjustment)->withErrors($e->errors());
            }

            return redirect()->route('stock-adjustments.show', $adjustment)->with('success', 'Stock adjustment submitted and inventory updated.');
        }

        return redirect()->route('stock-adjustments.show', $adjustment)->with('success', 'Draft stock adjustment saved.');
    }

    public function show(StockAdjustment $stockAdjustment)
    {
        abort_unless($this->canView(), 403);
        $stockAdjustment->load(['items.product', 'warehouse', 'creator', 'submitter']);

        return view('inventory.adjustments.show', ['adjustment' => $stockAdjustment]);
    }

    public function edit(StockAdjustment $stockAdjustment)
    {
        abort_unless($this->canManage(), 403);
        abort_unless($stockAdjustment->isDraft(), 403, 'Only draft adjustments can be edited.');

        return view('inventory.adjustments.edit', array_merge($this->formData(), [
            'adjustment' => $stockAdjustment->load('items'),
        ]));
    }

    public function update(StoreStockAdjustmentRequest $request, StockAdjustment $stockAdjustment)
    {
        try {
            $data = $request->validated();
            $this->adjustments->updateDraft(
                $stockAdjustment,
                [
                    'warehouse_id' => $data['warehouse_id'],
                    'reference_number' => $data['reference_number'] ?? null,
                    'transaction_date' => $data['transaction_date'],
                    'remarks' => $data['remarks'] ?? null,
                ],
                $data['items']
            );

            if ($request->boolean('submit')) {
                $this->adjustments->submit($stockAdjustment->fresh());

                return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('success', 'Stock adjustment submitted and inventory updated.');
            }
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('success', 'Draft updated.');
    }

    public function submit(StockAdjustment $stockAdjustment)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->adjustments->submit($stockAdjustment);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('success', 'Stock adjustment submitted.');
    }

    public function cancel(StockAdjustment $stockAdjustment)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->adjustments->cancel($stockAdjustment);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('stock-adjustments.show', $stockAdjustment)->with('success', 'Stock adjustment cancelled.');
    }

    public function destroy(StockAdjustment $stockAdjustment)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->adjustments->deleteDraft($stockAdjustment);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('stock-adjustments.index')->with('success', 'Draft adjustment deleted.');
    }

    public function systemQuantity(Request $request)
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
            'system_quantity' => (int) ($stock?->quantity ?? 0),
            'average_cost' => (float) ($stock?->average_cost ?? 0),
        ]);
    }

    private function formData(): array
    {
        return [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'cost_price', 'unit']),
            'reasons' => StockAdjustment::REASONS,
        ];
    }

    private function canView(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('inventory.view')
            || $user->hasPermission('inventory.adjust')
            || $user->hasPermission('inventory.manage')
            || $user->canManageInventory();
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('inventory.adjust')
            || $user->hasPermission('inventory.manage')
            || $user->canManageInventory();
    }
}
