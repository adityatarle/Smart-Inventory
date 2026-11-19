<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class StockController extends Controller
{
    public function __construct(private StockService $stockService)
    {
    }

    public function index(Request $request)
    {
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        $warehouseId = $request->integer('warehouse_id') ?: $warehouses->first()?->id;

        $stocks = WarehouseStock::query()
            ->with(['product.category', 'warehouse'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        $movements = StockMovement::query()
            ->with(['product', 'warehouse', 'user'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->latest()
            ->take(20)
            ->get();

        return view('stock.index', compact('stocks', 'warehouses', 'warehouseId', 'movements'));
    }

    public function adjustForm()
    {
        return view('stock.adjust', [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'not_in:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->stockService->adjust(
                (int) $data['warehouse_id'],
                (int) $data['product_id'],
                (int) $data['quantity'],
                'adjust',
                null,
                Auth::id(),
                $data['notes'] ?? 'Manual stock adjustment'
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['quantity' => $e->getMessage()]);
        }

        return redirect()->route('stock.index', ['warehouse_id' => $data['warehouse_id']])
            ->with('success', 'Stock adjusted.');
    }

    public function live(Request $request)
    {
        $warehouseId = $request->integer('warehouse_id');

        $stocks = WarehouseStock::query()
            ->with(['product:id,name,sku,reorder_level,unit', 'warehouse:id,name,code'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (WarehouseStock $stock) => [
                'id' => $stock->id,
                'warehouse' => $stock->warehouse?->name,
                'sku' => $stock->product?->sku,
                'product' => $stock->product?->name,
                'quantity' => $stock->quantity,
                'reserved' => $stock->reserved_quantity,
                'available' => $stock->availableQuantity(),
                'unit' => $stock->product?->unit,
                'reorder_level' => $stock->product?->reorder_level,
                'is_low' => $stock->quantity <= ($stock->product?->reorder_level ?? 0),
                'updated_at' => optional($stock->updated_at)->diffForHumans(),
            ]);

        $movements = StockMovement::query()
            ->with(['product:id,name,sku', 'warehouse:id,name', 'user:id,name'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->latest()
            ->take(15)
            ->get()
            ->map(fn (StockMovement $m) => [
                'id' => $m->id,
                'type' => $m->type,
                'product' => $m->product?->name,
                'sku' => $m->product?->sku,
                'warehouse' => $m->warehouse?->name,
                'quantity' => $m->quantity,
                'balance_after' => $m->balance_after,
                'user' => $m->user?->name,
                'created_at' => optional($m->created_at)->diffForHumans(),
            ]);

        return response()->json([
            'stocks' => $stocks,
            'movements' => $movements,
            'refreshed_at' => now()->toDateTimeString(),
        ]);
    }
}
