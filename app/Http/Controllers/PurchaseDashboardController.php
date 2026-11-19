<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class PurchaseDashboardController extends Controller
{
    public function index()
    {
        abort_unless($this->canView(), 403);

        $ymExpr = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', COALESCE(order_date, created_at))"
            : "DATE_FORMAT(COALESCE(order_date, created_at), '%Y-%m')";

        $monthly = PurchaseOrder::query()
            ->selectRaw("{$ymExpr} as ym, COALESCE(SUM(total),0) as value")
            ->whereNotIn('status', [PurchaseOrder::STATUS_CANCELLED, PurchaseOrder::STATUS_DRAFT])
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('value', 'ym');

        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'));

        $supplierWise = PurchaseOrder::query()
            ->selectRaw('supplier_id, COALESCE(SUM(total),0) as value, COUNT(*) as orders')
            ->whereNotNull('supplier_id')
            ->whereNotIn('status', [PurchaseOrder::STATUS_CANCELLED, PurchaseOrder::STATUS_DRAFT])
            ->groupBy('supplier_id')
            ->orderByDesc('value')
            ->limit(8)
            ->get();
        $supplierWise->load('supplier');

        return view('purchase.dashboard', [
            'stats' => [
                'total' => PurchaseOrder::count(),
                'pending_approval' => PurchaseOrder::where('status', PurchaseOrder::STATUS_PENDING_APPROVAL)->count(),
                'approved' => PurchaseOrder::where('status', PurchaseOrder::STATUS_APPROVED)->count(),
                'partial' => PurchaseOrder::where('status', PurchaseOrder::STATUS_PARTIALLY_RECEIVED)->count(),
                'fully_received' => PurchaseOrder::where('status', PurchaseOrder::STATUS_FULLY_RECEIVED)->count(),
                'pending_delivery' => PurchaseOrder::whereIn('status', [
                    PurchaseOrder::STATUS_APPROVED,
                    PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
                ])->count(),
                'monthly_value' => (float) PurchaseOrder::query()
                    ->whereNotIn('status', [PurchaseOrder::STATUS_CANCELLED, PurchaseOrder::STATUS_DRAFT])
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->sum('total'),
                'grns_month' => GoodsReceipt::query()
                    ->where('status', GoodsReceipt::STATUS_SUBMITTED)
                    ->where('submitted_at', '>=', now()->startOfMonth())
                    ->count(),
            ],
            'months' => $months,
            'monthlyValues' => $months->map(fn ($m) => (float) ($monthly[$m] ?? 0)),
            'supplierWise' => $supplierWise,
            'recentOrders' => PurchaseOrder::with(['supplier', 'warehouse'])->latest()->take(8)->get(),
            'pendingApprovals' => PurchaseOrder::with(['supplier', 'submitter'])
                ->where('status', PurchaseOrder::STATUS_PENDING_APPROVAL)
                ->latest('submitted_at')
                ->take(6)
                ->get(),
        ]);
    }

    private function canView(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('purchase_orders.view')
            || $user->hasPermission('reports.view')
            || $user->hasPermission('purchase_orders.manage')
            || $user->canManageInventory()
            || $user->isAdmin()
            || $user->isManager();
    }
}
