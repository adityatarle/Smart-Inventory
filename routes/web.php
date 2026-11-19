<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ModulePlaceholderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\PurchaseDashboardController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseReportController;
use App\Http\Controllers\Sales\BlanketOrderController;
use App\Http\Controllers\Sales\CustomerController;
use App\Http\Controllers\Sales\QuotationController;
use App\Http\Controllers\Sales\SalesHubController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use App\Http\Controllers\Sales\SalesPartnerController;
use App\Http\Controllers\Sales\SalesPersonController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockAlertController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockIssueController;
use App\Http\Controllers\StockReceiptController;
use App\Http\Controllers\StockReportController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StockValuationController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password', [ProfileController::class, 'passwordForm'])->name('profile.password');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Inventory – Phase 3 ledger + overview + stock in/out
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/stock-movements', [InventoryController::class, 'ledger'])->name('stock-movements.index');
    Route::get('/stock-movements/{movement}', [InventoryController::class, 'showMovement'])->name('stock-movements.show');

    Route::get('/stock-receipts', [StockReceiptController::class, 'index'])->name('stock-receipts.index');
    Route::get('/stock-receipts/create', [StockReceiptController::class, 'create'])->name('stock-receipts.create');
    Route::post('/stock-receipts', [StockReceiptController::class, 'store'])->name('stock-receipts.store');
    Route::get('/stock-receipts/{stockReceipt}', [StockReceiptController::class, 'show'])->name('stock-receipts.show');
    Route::get('/stock-receipts/{stockReceipt}/edit', [StockReceiptController::class, 'edit'])->name('stock-receipts.edit');
    Route::put('/stock-receipts/{stockReceipt}', [StockReceiptController::class, 'update'])->name('stock-receipts.update');
    Route::post('/stock-receipts/{stockReceipt}/submit', [StockReceiptController::class, 'submit'])->name('stock-receipts.submit');
    Route::post('/stock-receipts/{stockReceipt}/cancel', [StockReceiptController::class, 'cancel'])->name('stock-receipts.cancel');
    Route::delete('/stock-receipts/{stockReceipt}', [StockReceiptController::class, 'destroy'])->name('stock-receipts.destroy');

    Route::get('/stock-issues/availability', [StockIssueController::class, 'availability'])->name('stock-issues.availability');
    Route::get('/stock-issues', [StockIssueController::class, 'index'])->name('stock-issues.index');
    Route::get('/stock-issues/create', [StockIssueController::class, 'create'])->name('stock-issues.create');
    Route::post('/stock-issues', [StockIssueController::class, 'store'])->name('stock-issues.store');
    Route::get('/stock-issues/{stockIssue}', [StockIssueController::class, 'show'])->name('stock-issues.show');
    Route::get('/stock-issues/{stockIssue}/edit', [StockIssueController::class, 'edit'])->name('stock-issues.edit');
    Route::put('/stock-issues/{stockIssue}', [StockIssueController::class, 'update'])->name('stock-issues.update');
    Route::post('/stock-issues/{stockIssue}/submit', [StockIssueController::class, 'submit'])->name('stock-issues.submit');
    Route::post('/stock-issues/{stockIssue}/cancel', [StockIssueController::class, 'cancel'])->name('stock-issues.cancel');
    Route::delete('/stock-issues/{stockIssue}', [StockIssueController::class, 'destroy'])->name('stock-issues.destroy');

    Route::get('/stock-adjustments/system-quantity', [StockAdjustmentController::class, 'systemQuantity'])->name('stock-adjustments.system-quantity');
    Route::get('/stock-adjustments', [StockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
    Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
    Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
    Route::get('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show'])->name('stock-adjustments.show');
    Route::get('/stock-adjustments/{stockAdjustment}/edit', [StockAdjustmentController::class, 'edit'])->name('stock-adjustments.edit');
    Route::put('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'update'])->name('stock-adjustments.update');
    Route::post('/stock-adjustments/{stockAdjustment}/submit', [StockAdjustmentController::class, 'submit'])->name('stock-adjustments.submit');
    Route::post('/stock-adjustments/{stockAdjustment}/cancel', [StockAdjustmentController::class, 'cancel'])->name('stock-adjustments.cancel');
    Route::delete('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'destroy'])->name('stock-adjustments.destroy');

    Route::get('/stock-transfers/availability', [StockTransferController::class, 'availability'])->name('stock-transfers.availability');
    Route::get('/stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers.index');
    Route::get('/stock-transfers/create', [StockTransferController::class, 'create'])->name('stock-transfers.create');
    Route::post('/stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store');
    Route::get('/stock-transfers/{stockTransfer}', [StockTransferController::class, 'show'])->name('stock-transfers.show');
    Route::get('/stock-transfers/{stockTransfer}/edit', [StockTransferController::class, 'edit'])->name('stock-transfers.edit');
    Route::put('/stock-transfers/{stockTransfer}', [StockTransferController::class, 'update'])->name('stock-transfers.update');
    Route::post('/stock-transfers/{stockTransfer}/submit', [StockTransferController::class, 'submit'])->name('stock-transfers.submit');
    Route::get('/stock-transfers/{stockTransfer}/receive', [StockTransferController::class, 'receiveForm'])->name('stock-transfers.receive');
    Route::post('/stock-transfers/{stockTransfer}/receive', [StockTransferController::class, 'receive'])->name('stock-transfers.receive.store');
    Route::post('/stock-transfers/{stockTransfer}/cancel', [StockTransferController::class, 'cancel'])->name('stock-transfers.cancel');
    Route::delete('/stock-transfers/{stockTransfer}', [StockTransferController::class, 'destroy'])->name('stock-transfers.destroy');

    Route::get('/inventory/valuation', [StockValuationController::class, 'index'])->name('inventory.valuation');
    Route::get('/inventory/alerts', [StockAlertController::class, 'index'])->name('inventory.alerts');
    Route::post('/inventory/alerts/scan', [StockAlertController::class, 'scan'])->name('inventory.alerts.scan');
    Route::post('/inventory/alerts/{stockAlert}/resolve', [StockAlertController::class, 'resolve'])->name('inventory.alerts.resolve');
    Route::get('/inventory/low-stock', [StockAlertController::class, 'lowStock'])->name('inventory.low-stock');

    // Phase placeholders for modules not yet built
    Route::get('/payments', [ModulePlaceholderController::class, 'show'])
        ->defaults('module', 'payments')
        ->name('payments.index');
    Route::get('/reports', [StockReportController::class, 'index'])->name('reports.index');
    Route::get('/notifications', [ModulePlaceholderController::class, 'show'])
        ->defaults('module', 'notifications')
        ->name('notifications.index');
    Route::get('/settings', [ModulePlaceholderController::class, 'show'])
        ->defaults('module', 'settings')
        ->name('settings.index');
    Route::get('/roles', [ModulePlaceholderController::class, 'show'])
        ->defaults('module', 'roles')
        ->name('roles.index');

    Route::get('/sales', [SalesHubController::class, 'index'])->name('sales.hub');

    // Customers – Phase 2 master data
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    // Suppliers – Phase 2 master data
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
    Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
    Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
    Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

    // Warehouses – Phase 2 master data
    Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::get('/warehouses/create', [WarehouseController::class, 'create'])->name('warehouses.create');
    Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
    Route::get('/warehouses/{warehouse}', [WarehouseController::class, 'show'])->name('warehouses.show');
    Route::get('/warehouses/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('warehouses.edit');
    Route::put('/warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
    Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');

    // Quotations
    Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index');
    Route::middleware('role:admin,manager,staff')->group(function () {
        Route::get('/quotations/create', [QuotationController::class, 'create'])->name('quotations.create');
        Route::post('/quotations', [QuotationController::class, 'store'])->name('quotations.store');
        Route::post('/quotations/{quotation}/status', [QuotationController::class, 'updateStatus'])->name('quotations.status');
        Route::post('/quotations/{quotation}/convert', [QuotationController::class, 'convert'])->name('quotations.convert');
    });
    Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');

    // Sales Orders
    Route::get('/sales-orders', [SalesOrderController::class, 'index'])->name('sales-orders.index');
    Route::get('/sales-orders/create', [SalesOrderController::class, 'create'])->name('sales-orders.create');
    Route::post('/sales-orders', [SalesOrderController::class, 'store'])->name('sales-orders.store');
    Route::middleware('role:admin,manager,staff')->group(function () {
        Route::post('/sales-orders/{salesOrder}/fulfill', [SalesOrderController::class, 'fulfill'])->name('sales-orders.fulfill');
        Route::post('/sales-orders/{salesOrder}/invoice', [SalesOrderController::class, 'createInvoice'])->name('sales-orders.invoice');
    });
    Route::get('/sales-orders/{salesOrder}', [SalesOrderController::class, 'show'])->name('sales-orders.show');
    Route::post('/sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel'])->name('sales-orders.cancel');

    // Sales Invoices
    Route::get('/sales-invoices', [SalesInvoiceController::class, 'index'])->name('sales-invoices.index');
    Route::middleware('role:admin,manager,staff')->group(function () {
        Route::get('/sales-invoices/create', [SalesInvoiceController::class, 'create'])->name('sales-invoices.create');
        Route::post('/sales-invoices', [SalesInvoiceController::class, 'store'])->name('sales-invoices.store');
        Route::post('/sales-invoices/{salesInvoice}/pay', [SalesInvoiceController::class, 'markPaid'])->name('sales-invoices.pay');
        Route::post('/sales-invoices/{salesInvoice}/cancel', [SalesInvoiceController::class, 'cancel'])->name('sales-invoices.cancel');
    });
    Route::get('/sales-invoices/{salesInvoice}', [SalesInvoiceController::class, 'show'])->name('sales-invoices.show');

    // Blanket Orders
    Route::get('/blanket-orders', [BlanketOrderController::class, 'index'])->name('blanket-orders.index');
    Route::middleware('role:admin,manager,staff')->group(function () {
        Route::get('/blanket-orders/create', [BlanketOrderController::class, 'create'])->name('blanket-orders.create');
        Route::post('/blanket-orders', [BlanketOrderController::class, 'store'])->name('blanket-orders.store');
        Route::post('/blanket-orders/{blanketOrder}/cancel', [BlanketOrderController::class, 'cancel'])->name('blanket-orders.cancel');
    });
    Route::get('/blanket-orders/{blanketOrder}', [BlanketOrderController::class, 'show'])->name('blanket-orders.show');

    Route::middleware('role:admin,manager,staff')->group(function () {
        Route::resource('sales-partners', SalesPartnerController::class)->except(['show']);
        Route::resource('sales-persons', SalesPersonController::class)->except(['show']);

        Route::get('/stock', function () {
            return redirect()->route('inventory.index', request()->query());
        })->name('stock.index');
        Route::get('/stock/live', [StockController::class, 'live'])->name('stock.live');
    });

    // Products – Phase 2 master data (auth + controller-level permission checks)
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/import', [ProductController::class, 'importForm'])->name('products.import');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import.store');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Categories – Phase 2 master data
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/purchase', [PurchaseDashboardController::class, 'index'])->name('purchase.dashboard');
    Route::get('/purchase/reports', [PurchaseReportController::class, 'index'])->name('purchase.reports');
    Route::get('/purchase/reports/register', [PurchaseReportController::class, 'register'])->name('purchase.reports.register');
    Route::get('/purchase/reports/pending', [PurchaseReportController::class, 'pending'])->name('purchase.reports.pending');
    Route::get('/purchase/reports/grn', [PurchaseReportController::class, 'grnRegister'])->name('purchase.reports.grn');
    Route::get('/purchase/reports/supplier-wise', [PurchaseReportController::class, 'supplierWise'])->name('purchase.reports.supplier');
    Route::get('/purchase/reports/product-wise', [PurchaseReportController::class, 'productWise'])->name('purchase.reports.product');
    Route::get('/purchase/reports/summary', [PurchaseReportController::class, 'summary'])->name('purchase.reports.summary');

    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
    Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
    Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::get('/purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
    Route::put('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
    Route::post('/purchase-orders/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
    Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('/purchase-orders/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::get('/purchase-orders/{purchaseOrder}/print', [PurchaseOrderController::class, 'print'])->name('purchase-orders.print');
    Route::delete('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');

    Route::get('/goods-receipts', [GoodsReceiptController::class, 'index'])->name('goods-receipts.index');
    Route::get('/goods-receipts/create', [GoodsReceiptController::class, 'create'])->name('goods-receipts.create');
    Route::post('/goods-receipts', [GoodsReceiptController::class, 'store'])->name('goods-receipts.store');
    Route::get('/goods-receipts/{goodsReceipt}', [GoodsReceiptController::class, 'show'])->name('goods-receipts.show');
    Route::post('/goods-receipts/{goodsReceipt}/submit', [GoodsReceiptController::class, 'submit'])->name('goods-receipts.submit');
    Route::post('/goods-receipts/{goodsReceipt}/cancel', [GoodsReceiptController::class, 'cancel'])->name('goods-receipts.cancel');
    Route::delete('/goods-receipts/{goodsReceipt}', [GoodsReceiptController::class, 'destroy'])->name('goods-receipts.destroy');

    Route::middleware('role:admin,manager')->group(function () {
        Route::get('/stock/adjust', fn () => redirect()->route('stock-adjustments.create'))->name('stock.adjust');
        Route::post('/stock/adjust', fn () => redirect()->route('stock-adjustments.create'))->name('stock.adjust.store');
    });

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });
});
