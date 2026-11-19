<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockValuationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockValuationController extends Controller
{
    public function __construct(private readonly StockValuationService $valuation)
    {
    }

    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $warehouseId = $request->integer('warehouse_id') ?: null;
        $productId = $request->integer('product_id') ?: null;
        $from = Carbon::parse($request->get('date_from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->get('date_to', now()->toDateString()))->endOfDay();

        $report = $this->valuation->periodReport($warehouseId, $productId, $from, $to);
        $currentValue = $this->valuation->totalInventoryValue($warehouseId);

        if ($request->boolean('export')) {
            return $this->export($report['rows']);
        }

        return view('inventory.valuation', [
            'rows' => $report['rows'],
            'totals' => $report['totals'],
            'currentValue' => $currentValue,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku']),
            'filters' => [
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
            ],
        ]);
    }

    private function export($rows): StreamedResponse
    {
        $filename = 'stock-valuation-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'warehouse', 'sku', 'product', 'opening_qty', 'opening_value',
                'received_qty', 'received_value', 'issued_qty', 'issued_value',
                'closing_qty', 'average_unit_cost', 'closing_value',
            ]);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['warehouse'], $row['sku'], $row['product'],
                    $row['opening_qty'], $row['opening_value'],
                    $row['received_qty'], $row['received_value'],
                    $row['issued_qty'], $row['issued_value'],
                    $row['closing_qty'], $row['average_unit_cost'], $row['closing_value'],
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function canView(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('inventory.view')
            || $user->hasPermission('reports.view')
            || $user->hasPermission('inventory.manage')
            || $user->canManageInventory();
    }
}
