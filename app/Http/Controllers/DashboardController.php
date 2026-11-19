<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Product::count();
        $inventoryValue = (float) WarehouseStock::query()
            ->join('products', 'products.id', '=', 'warehouse_stocks.product_id')
            ->selectRaw('COALESCE(SUM(warehouse_stocks.quantity * products.cost_price), 0) as value')
            ->value('value');

        $totalOrders = SalesOrder::count() + PurchaseOrder::count();
        $lowStock = WarehouseStock::query()
            ->join('products', 'products.id', '=', 'warehouse_stocks.product_id')
            ->whereColumn('warehouse_stocks.quantity', '<=', 'products.reorder_level')
            ->count();

        $categoryStock = Category::query()
            ->leftJoin('products', 'products.category_id', '=', 'categories.id')
            ->leftJoin('warehouse_stocks', 'warehouse_stocks.product_id', '=', 'products.id')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('categories.name')
            ->get([
                'categories.name',
                DB::raw('COALESCE(SUM(warehouse_stocks.quantity), 0) as qty'),
            ]);

        $warehouseStock = Warehouse::query()
            ->leftJoin('warehouse_stocks', 'warehouse_stocks.warehouse_id', '=', 'warehouses.id')
            ->groupBy('warehouses.id', 'warehouses.name')
            ->orderBy('warehouses.name')
            ->get([
                'warehouses.name',
                DB::raw('COALESCE(SUM(warehouse_stocks.quantity), 0) as qty'),
            ]);

        $monthlySales = SalesOrder::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('total', 'ym');

        $monthlyPurchases = PurchaseOrder::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('total', 'ym');

        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'));

        $stockInOut = StockMovement::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym,
                SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) as stock_in,
                SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END) as stock_out")
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $recentOrders = SalesOrder::with(['customer', 'warehouse'])->latest()->take(6)->get();
        $lowStockItems = WarehouseStock::with(['product', 'warehouse'])
            ->join('products', 'products.id', '=', 'warehouse_stocks.product_id')
            ->whereColumn('warehouse_stocks.quantity', '<=', 'products.reorder_level')
            ->select('warehouse_stocks.*')
            ->latest('warehouse_stocks.updated_at')
            ->take(6)
            ->get();
        $recentActivities = ActivityLog::with('user')->latest()->take(8)->get();

        $orderStatus = [
            'confirmed' => SalesOrder::where('status', 'confirmed')->count(),
            'fulfilled' => SalesOrder::where('status', 'fulfilled')->count(),
            'cancelled' => SalesOrder::where('status', 'cancelled')->count(),
            'draft' => SalesOrder::where('status', 'draft')->count(),
        ];

        return view('dashboard.index', [
            'kpis' => [
                'products' => $totalProducts,
                'inventory_value' => $inventoryValue,
                'orders' => $totalOrders,
                'low_stock' => $lowStock,
            ],
            'categoryStock' => $categoryStock,
            'warehouseStock' => $warehouseStock,
            'months' => $months,
            'monthlySales' => $months->map(fn ($m) => (int) ($monthlySales[$m] ?? 0)),
            'monthlyPurchases' => $months->map(fn ($m) => (int) ($monthlyPurchases[$m] ?? 0)),
            'stockIn' => $months->map(fn ($m) => (int) ($stockInOut[$m]->stock_in ?? 0)),
            'stockOut' => $months->map(fn ($m) => (int) ($stockInOut[$m]->stock_out ?? 0)),
            'recentOrders' => $recentOrders,
            'lowStockItems' => $lowStockItems,
            'recentActivities' => $recentActivities,
            'orderStatus' => $orderStatus,
            'totalWarehouses' => Warehouse::count(),
            'totalUnits' => (int) WarehouseStock::sum('quantity'),
        ]);
    }
}
