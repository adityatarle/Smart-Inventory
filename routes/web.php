<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\Sales\BlanketOrderController;
use App\Http\Controllers\Sales\CustomerController;
use App\Http\Controllers\Sales\QuotationController;
use App\Http\Controllers\Sales\SalesHubController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use App\Http\Controllers\Sales\SalesPartnerController;
use App\Http\Controllers\Sales\SalesPersonController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\StockController;
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
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/sales', [SalesHubController::class, 'index'])->name('sales.hub');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::middleware('role:admin,manager,staff')->group(function () {
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    });
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

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

        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('/stock/live', [StockController::class, 'live'])->name('stock.live');
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    });

    Route::middleware('role:admin,manager')->group(function () {
        Route::resource('warehouses', WarehouseController::class)->except(['show']);
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('products', ProductController::class)->except(['index', 'show']);

        Route::get('/stock/adjust', [StockController::class, 'adjustForm'])->name('stock.adjust');
        Route::post('/stock/adjust', [StockController::class, 'adjust'])->name('stock.adjust.store');

        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
        Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    });

    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });
});
