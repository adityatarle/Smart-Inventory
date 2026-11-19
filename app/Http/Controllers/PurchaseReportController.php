<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseReportController extends Controller
{
    public function index()
    {
        abort_unless($this->canView(), 403);

        return view('purchase.reports', [
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name', 'code']),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function register(Request $request)
    {
        abort_unless($this->canView(), 403);
        $orders = $this->poQuery($request)->with(['supplier', 'warehouse'])->latest()->paginate(25)->withQueryString();

        return $this->exportOrView($request, $orders, 'purchase.report-register', 'po-register', [
            'order_number', 'supplier', 'warehouse', 'status', 'order_date', 'total',
        ], function ($o) {
            return [$o->order_number, $o->supplierDisplayName(), $o->warehouse?->name, $o->status, optional($o->order_date)->format('Y-m-d'), $o->total];
        });
    }

    public function pending(Request $request)
    {
        abort_unless($this->canView(), 403);
        $request->merge(['status' => null]);
        $orders = PurchaseOrder::query()
            ->with(['supplier', 'warehouse'])
            ->whereIn('status', [
                PurchaseOrder::STATUS_PENDING_APPROVAL,
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ])
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('order_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('order_date', '<=', $request->date_to))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('purchase.report-pending', [
            'orders' => $orders,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function grnRegister(Request $request)
    {
        abort_unless($this->canView(), 403);

        $receipts = GoodsReceipt::query()
            ->with(['purchaseOrder', 'supplier', 'warehouse'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('receipt_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('receipt_date', '<=', $request->date_to))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('purchase.report-grn', [
            'receipts' => $receipts,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function supplierWise(Request $request)
    {
        abort_unless($this->canView(), 403);

        $rows = PurchaseOrder::query()
            ->selectRaw('supplier_id, COUNT(*) as orders, COALESCE(SUM(total),0) as ordered_value')
            ->whereNotNull('supplier_id')
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('order_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('order_date', '<=', $request->date_to))
            ->whereNotIn('status', [PurchaseOrder::STATUS_CANCELLED, PurchaseOrder::STATUS_DRAFT])
            ->groupBy('supplier_id')
            ->with('supplier')
            ->orderByDesc('ordered_value')
            ->get();

        return view('purchase.report-supplier', compact('rows'));
    }

    public function productWise(Request $request)
    {
        abort_unless($this->canView(), 403);

        $rows = PurchaseOrderItem::query()
            ->selectRaw('product_id, SUM(quantity) as ordered_qty, SUM(received_quantity) as received_qty, SUM(line_total) as ordered_value')
            ->whereHas('purchaseOrder', function ($q) use ($request) {
                $q->whereNotIn('status', [PurchaseOrder::STATUS_CANCELLED, PurchaseOrder::STATUS_DRAFT])
                    ->when($request->filled('date_from'), fn ($qq) => $qq->whereDate('order_date', '>=', $request->date_from))
                    ->when($request->filled('date_to'), fn ($qq) => $qq->whereDate('order_date', '<=', $request->date_to))
                    ->when($request->filled('supplier_id'), fn ($qq) => $qq->where('supplier_id', $request->supplier_id));
            })
            ->groupBy('product_id')
            ->with('product')
            ->orderByDesc('ordered_value')
            ->get();

        return view('purchase.report-product', [
            'rows' => $rows,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function summary(Request $request)
    {
        abort_unless($this->canView(), 403);

        $base = $this->poQuery($request);

        return view('purchase.report-summary', [
            'summary' => [
                'orders' => (clone $base)->count(),
                'value' => (float) (clone $base)->sum('total'),
                'approved' => (clone $base)->where('status', PurchaseOrder::STATUS_APPROVED)->count(),
                'pending' => (clone $base)->where('status', PurchaseOrder::STATUS_PENDING_APPROVAL)->count(),
                'partial' => (clone $base)->where('status', PurchaseOrder::STATUS_PARTIALLY_RECEIVED)->count(),
                'fully_received' => (clone $base)->where('status', PurchaseOrder::STATUS_FULLY_RECEIVED)->count(),
                'cancelled' => (clone $base)->where('status', PurchaseOrder::STATUS_CANCELLED)->count(),
            ],
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function poQuery(Request $request)
    {
        return PurchaseOrder::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('order_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('order_date', '<=', $request->date_to));
    }

    private function exportOrView(Request $request, $orders, string $view, string $file, array $headers, callable $map)
    {
        if ($request->boolean('export')) {
            return response()->streamDownload(function () use ($orders, $headers, $map) {
                $h = fopen('php://output', 'w');
                fputcsv($h, $headers);
                foreach ($orders as $row) {
                    fputcsv($h, $map($row));
                }
                fclose($h);
            }, $file.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
        }

        return view($view, [
            'orders' => $orders,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::orderBy('name')->get(['id', 'name']),
            'statuses' => PurchaseOrder::STATUSES,
        ]);
    }

    private function canView(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('reports.view')
            || $user->hasPermission('purchase_orders.view')
            || $user->hasPermission('purchase_orders.manage')
            || $user->canManageInventory()
            || $user->isAdmin()
            || $user->isManager();
    }
}
