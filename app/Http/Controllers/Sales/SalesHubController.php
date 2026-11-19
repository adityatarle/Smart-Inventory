<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ScopesSalesByCustomer;
use App\Models\BlanketOrder;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesPartner;
use App\Models\SalesPerson;

class SalesHubController extends Controller
{
    use ScopesSalesByCustomer;

    public function index()
    {
        $stats = [
            'customers' => $this->scopeForCustomerRole(Customer::query(), 'id')->count(),
            'quotations' => $this->scopeForCustomerRole(Quotation::query())->count(),
            'orders' => $this->scopeForCustomerRole(SalesOrder::query())->count(),
            'invoices' => $this->scopeForCustomerRole(SalesInvoice::query())->count(),
            'blankets' => $this->scopeForCustomerRole(BlanketOrder::query())->count(),
            'partners' => auth()->user()->isCustomer() ? 0 : SalesPartner::count(),
            'persons' => auth()->user()->isCustomer() ? 0 : SalesPerson::count(),
        ];

        return view('sales.hub', compact('stats'));
    }
}
