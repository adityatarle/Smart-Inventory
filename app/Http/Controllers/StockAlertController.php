<?php

namespace App\Http\Controllers;

use App\Models\StockAlert;
use App\Models\Warehouse;
use App\Services\StockAlertService;
use Illuminate\Http\Request;

class StockAlertController extends Controller
{
    public function __construct(private readonly StockAlertService $alerts)
    {
    }

    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $status = $request->get('status', 'open');

        $alerts = StockAlert::query()
            ->with(['product', 'warehouse'])
            ->when($status === 'open', fn ($q) => $q->open())
            ->when($status === 'resolved', fn ($q) => $q->whereNotNull('resolved_at'))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('inventory.alerts', [
            'alerts' => $alerts,
            'counts' => $this->alerts->openCounts(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function scan(Request $request)
    {
        abort_unless($this->canManage(), 403);

        $touched = $this->alerts->scanAll($request->integer('warehouse_id') ?: null);

        return redirect()->route('inventory.alerts')->with('success', "Alert scan complete ({$touched} stock rows checked).");
    }

    public function resolve(StockAlert $stockAlert)
    {
        abort_unless($this->canManage(), 403);
        $stockAlert->resolve();

        return back()->with('success', 'Alert marked resolved.');
    }

    public function lowStock(Request $request)
    {
        abort_unless($this->canView(), 403);

        $warehouseId = $request->integer('warehouse_id') ?: null;

        $rows = \App\Models\WarehouseStock::query()
            ->with(['product', 'warehouse'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->where(function ($q) {
                $q->where('quantity', '<=', 0)
                    ->orWhereHas('product', fn ($p) => $p->whereColumn('warehouse_stocks.quantity', '<=', 'products.reorder_level'));
            })
            ->orderBy('quantity')
            ->paginate(25)
            ->withQueryString();

        return view('inventory.low-stock', [
            'rows' => $rows,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'warehouseId' => $warehouseId,
        ]);
    }

    private function canView(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('inventory.view')
            || $user->hasPermission('reports.view')
            || $user->canManageInventory();
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('inventory.manage')
            || $user->hasPermission('inventory.adjust')
            || $user->canManageInventory();
    }
}
