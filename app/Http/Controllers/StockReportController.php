<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use App\Models\WarehouseStock;
use App\Services\StockAlertService;
use App\Services\StockValuationService;

class StockReportController extends Controller
{
    public function __construct(
        private readonly StockValuationService $valuation,
        private readonly StockAlertService $alerts,
    ) {
    }

    public function index()
    {
        abort_unless($this->canView(), 403);

        $counts = $this->alerts->openCounts();

        return view('inventory.reports', [
            'inventoryValue' => $this->valuation->totalInventoryValue(),
            'totalUnits' => (int) WarehouseStock::sum('quantity'),
            'movementsMonth' => (int) StockMovement::query()
                ->where('transaction_date', '>=', now()->startOfMonth())
                ->count(),
            'openAlerts' => $counts['total'],
            'outOfStock' => $counts['out_of_stock'],
            'lowStock' => $counts['low_stock'],
        ]);
    }

    private function canView(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('reports.view')
            || $user->hasPermission('inventory.view')
            || $user->canManageInventory();
    }
}
