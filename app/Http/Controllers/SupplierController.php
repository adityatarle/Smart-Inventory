<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    public function __construct(private readonly SupplierService $suppliers)
    {
    }

    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $suppliers = Supplier::query()
            ->search($request->get('search'))
            ->active($request->get('status'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => Supplier::count(),
            'active' => Supplier::where('is_active', true)->count(),
            'inactive' => Supplier::where('is_active', false)->count(),
            'with_gst' => Supplier::whereNotNull('gst_number')->where('gst_number', '!=', '')->count(),
        ];

        return view('suppliers.index', compact('suppliers', 'stats'));
    }

    public function create()
    {
        abort_unless($this->canManage(), 403);

        return view('suppliers.create', [
            'suggestedCode' => $this->suppliers->suggestCode(),
        ]);
    }

    public function store(StoreSupplierRequest $request)
    {
        $supplier = $this->suppliers->create($request->validated());

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier created.');
    }

    public function show(Supplier $supplier)
    {
        abort_unless($this->canView(), 403);

        $purchaseOrders = $supplier->matchingPurchaseOrders()->paginate(10);

        return view('suppliers.show', [
            'supplier' => $supplier,
            'purchaseOrders' => $purchaseOrders,
            'blockers' => $this->suppliers->deletionBlockers($supplier),
        ]);
    }

    public function edit(Supplier $supplier)
    {
        abort_unless($this->canManage(), 403);

        return view('suppliers.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $this->suppliers->update($supplier, $request->validated());

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier)
    {
        abort_unless(
            auth()->user()->hasPermission('suppliers.delete')
            || auth()->user()->hasPermission('suppliers.manage')
            || auth()->user()->canManageInventory(),
            403
        );

        try {
            $this->suppliers->delete($supplier);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted.');
    }

    private function canView(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('suppliers.view')
            || $user->hasPermission('suppliers.manage')
            || $user->canManageInventory();
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('suppliers.create')
            || $user->hasPermission('suppliers.update')
            || $user->hasPermission('suppliers.manage')
            || $user->canManageInventory();
    }
}
