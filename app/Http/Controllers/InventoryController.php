<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $warehouseId = $request->integer('warehouse_id') ?: null;
        $categoryId = $request->integer('category_id') ?: null;
        $status = $request->get('stock_status');

        $stocksQuery = WarehouseStock::query()
            ->with(['product.category', 'warehouse'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($categoryId, fn ($q) => $q->whereHas('product', fn ($p) => $p->where('category_id', $categoryId)))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($status === 'low', function ($q) {
                $q->whereHas('product', function ($p) {
                    $p->whereColumn('warehouse_stocks.quantity', '<=', 'products.reorder_level')
                        ->where('warehouse_stocks.quantity', '>', 0);
                });
            })
            ->when($status === 'out', fn ($q) => $q->where('quantity', '<=', 0))
            ->when($status === 'in_stock', fn ($q) => $q->where('quantity', '>', 0));

        $stocks = (clone $stocksQuery)
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        $monthStart = now()->startOfMonth();

        $stats = [
            'product_types' => (int) WarehouseStock::query()
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->where('quantity', '>', 0)
                ->selectRaw('count(distinct product_id) as aggregate')
                ->value('aggregate'),
            'total_qty' => (int) WarehouseStock::query()->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))->sum('quantity'),
            'inventory_value' => $this->inventory->inventoryValue($warehouseId),
            'low_stock' => WarehouseStock::query()
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->where('quantity', '>', 0)
                ->whereHas('product', fn ($p) => $p->whereColumn('warehouse_stocks.quantity', '<=', 'products.reorder_level'))
                ->count(),
            'out_of_stock' => WarehouseStock::query()
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->where('quantity', '<=', 0)
                ->count(),
            'received_month' => (int) StockMovement::query()
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->where('transaction_date', '>=', $monthStart)
                ->sum('quantity_in'),
            'issued_month' => (int) StockMovement::query()
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->where('transaction_date', '>=', $monthStart)
                ->sum('quantity_out'),
        ];

        $recentMovements = StockMovement::query()
            ->with(['product', 'warehouse', 'user'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->latest('transaction_date')
            ->latest('id')
            ->take(10)
            ->get();

        return view('inventory.index', [
            'stocks' => $stocks,
            'stats' => $stats,
            'recentMovements' => $recentMovements,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']),
            'filters' => [
                'warehouse_id' => $warehouseId,
                'category_id' => $categoryId,
                'product_id' => $request->get('product_id'),
                'stock_status' => $status,
            ],
        ]);
    }

    public function ledger(Request $request)
    {
        abort_unless($this->canView(), 403);

        $query = StockMovement::query()
            ->with(['product', 'warehouse', 'user'])
            ->search($request->get('search'))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('direction'), fn ($q) => $q->where('direction', $request->direction))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('transaction_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('transaction_date', '<=', $request->date_to))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        if ($request->boolean('export')) {
            return $this->exportLedger($query);
        }

        $movements = $query->paginate(20)->withQueryString();

        return view('inventory.ledger', [
            'movements' => $movements,
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku']),
            'types' => StockMovement::query()->distinct()->orderBy('type')->pluck('type'),
        ]);
    }

    public function showMovement(StockMovement $movement)
    {
        abort_unless($this->canView(), 403);

        $movement->load(['product.category', 'warehouse', 'user', 'reference']);

        return view('inventory.movement-show', compact('movement'));
    }

    private function exportLedger($query): StreamedResponse
    {
        $filename = 'stock-ledger-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'date', 'document_number', 'sku', 'product', 'warehouse', 'type', 'direction',
                'qty_in', 'qty_out', 'balance_after', 'unit_cost', 'total_cost', 'created_by', 'notes',
            ]);

            $query->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $m) {
                    fputcsv($handle, [
                        optional($m->transaction_date)->format('Y-m-d H:i'),
                        $m->document_number,
                        $m->product?->sku,
                        $m->product?->name,
                        $m->warehouse?->name,
                        $m->type,
                        $m->direction,
                        $m->quantity_in,
                        $m->quantity_out,
                        $m->balance_after,
                        $m->unit_cost,
                        $m->total_cost,
                        $m->user?->name,
                        $m->notes,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function canView(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('inventory.view')
            || $user->hasPermission('inventory.manage')
            || $user->canManageInventory();
    }
}
