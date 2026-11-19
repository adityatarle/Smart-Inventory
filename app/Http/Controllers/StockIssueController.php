<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockIssueRequest;
use App\Models\Product;
use App\Models\StockIssue;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockOutService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StockIssueController extends Controller
{
    public function __construct(private readonly StockOutService $stockOut)
    {
    }

    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $issues = StockIssue::query()
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

        return view('inventory.issues.index', [
            'issues' => $issues,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        abort_unless($this->canManage(), 403);

        return view('inventory.issues.create', $this->formData());
    }

    public function store(StoreStockIssueRequest $request)
    {
        $data = $request->validated();
        $issue = $this->stockOut->createDraft(
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
                $this->stockOut->submit($issue);
            } catch (ValidationException $e) {
                return redirect()->route('stock-issues.edit', $issue)->withErrors($e->errors());
            }

            return redirect()->route('stock-issues.show', $issue)->with('success', 'Stock issue submitted and inventory updated.');
        }

        return redirect()->route('stock-issues.show', $issue)->with('success', 'Draft stock issue saved.');
    }

    public function show(StockIssue $stockIssue)
    {
        abort_unless($this->canView(), 403);
        $stockIssue->load(['items.product', 'warehouse', 'creator', 'submitter']);

        return view('inventory.issues.show', ['issue' => $stockIssue]);
    }

    public function edit(StockIssue $stockIssue)
    {
        abort_unless($this->canManage(), 403);
        abort_unless($stockIssue->isDraft(), 403, 'Only draft issues can be edited.');

        return view('inventory.issues.edit', array_merge($this->formData(), [
            'issue' => $stockIssue->load('items'),
        ]));
    }

    public function update(StoreStockIssueRequest $request, StockIssue $stockIssue)
    {
        try {
            $data = $request->validated();
            $this->stockOut->updateDraft(
                $stockIssue,
                [
                    'warehouse_id' => $data['warehouse_id'],
                    'reference_number' => $data['reference_number'] ?? null,
                    'transaction_date' => $data['transaction_date'],
                    'remarks' => $data['remarks'] ?? null,
                ],
                $data['items']
            );

            if ($request->boolean('submit')) {
                $this->stockOut->submit($stockIssue->fresh());

                return redirect()->route('stock-issues.show', $stockIssue)->with('success', 'Stock issue submitted and inventory updated.');
            }
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('stock-issues.show', $stockIssue)->with('success', 'Draft updated.');
    }

    public function submit(StockIssue $stockIssue)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->stockOut->submit($stockIssue);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('stock-issues.show', $stockIssue)->with('success', 'Stock issue submitted.');
    }

    public function cancel(StockIssue $stockIssue)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->stockOut->cancel($stockIssue);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('stock-issues.show', $stockIssue)->with('success', 'Stock issue cancelled.');
    }

    public function destroy(StockIssue $stockIssue)
    {
        abort_unless($this->canManage(), 403);

        try {
            $this->stockOut->deleteDraft($stockIssue);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('stock-issues.index')->with('success', 'Draft issue deleted.');
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
            || $user->hasPermission('inventory.issue')
            || $user->hasPermission('inventory.manage')
            || $user->canManageInventory();
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('inventory.issue')
            || $user->hasPermission('inventory.manage')
            || $user->canManageInventory();
    }
}
