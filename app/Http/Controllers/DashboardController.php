<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isCustomer()) {
            $customer = Customer::query()->where('user_id', $user->id)->first();

            $orders = SalesOrder::query()
                ->with(['warehouse', 'items'])
                ->when($customer, fn ($q) => $q->where('customer_id', $customer->id), fn ($q) => $q->whereRaw('1=0'))
                ->latest()
                ->take(10)
                ->get();

            $quotations = Quotation::query()
                ->when($customer, fn ($q) => $q->where('customer_id', $customer->id), fn ($q) => $q->whereRaw('1=0'))
                ->latest()
                ->take(5)
                ->get();

            $invoices = SalesInvoice::query()
                ->when($customer, fn ($q) => $q->where('customer_id', $customer->id), fn ($q) => $q->whereRaw('1=0'))
                ->latest()
                ->take(5)
                ->get();

            return view('dashboard.customer', compact('orders', 'quotations', 'invoices', 'customer'));
        }

        $stats = [
            'warehouses' => Warehouse::count(),
            'products' => Product::count(),
            'categories' => Category::count(),
            'purchase_orders' => PurchaseOrder::count(),
            'sales_orders' => SalesOrder::count(),
            'low_stock' => WarehouseStock::query()
                ->join('products', 'products.id', '=', 'warehouse_stocks.product_id')
                ->whereColumn('warehouse_stocks.quantity', '<=', 'products.reorder_level')
                ->count(),
        ];

        $recentMovements = StockMovement::query()
            ->with(['product', 'warehouse', 'user'])
            ->latest()
            ->take(8)
            ->get();

        $pendingPurchases = PurchaseOrder::query()
            ->with('warehouse')
            ->whereIn('status', ['draft', 'ordered'])
            ->latest()
            ->take(5)
            ->get();

        $pendingSales = SalesOrder::query()
            ->with('warehouse')
            ->whereIn('status', ['draft', 'confirmed'])
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard.index', compact('stats', 'recentMovements', 'pendingPurchases', 'pendingSales'));
    }
}
