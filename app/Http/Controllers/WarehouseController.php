<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WarehouseController extends Controller
{
    public function __construct(private readonly WarehouseService $warehouses)
    {
    }

    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $warehouses = Warehouse::query()
            ->withCount('stocks')
            ->withSum('stocks as total_qty', 'quantity')
            ->search($request->get('search'))
            ->active($request->get('status'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => Warehouse::count(),
            'active' => Warehouse::where('is_active', true)->count(),
            'inactive' => Warehouse::where('is_active', false)->count(),
            'units' => (int) \App\Models\WarehouseStock::sum('quantity'),
        ];

        return view('warehouses.index', compact('warehouses', 'stats'));
    }

    public function create()
    {
        abort_unless($this->canManage(), 403);

        return view('warehouses.create', [
            'suggestedCode' => $this->warehouses->suggestCode(),
        ]);
    }

    public function store(StoreWarehouseRequest $request)
    {
        $warehouse = $this->warehouses->create($request->validated());

        return redirect()->route('warehouses.show', $warehouse)->with('success', 'Warehouse created.');
    }

    public function show(Warehouse $warehouse)
    {
        abort_unless($this->canView(), 403);

        $warehouse->loadCount('stocks')
            ->loadSum('stocks as total_qty', 'quantity');

        $stocks = $warehouse->stocks()
            ->with('product')
            ->orderByDesc('quantity')
            ->paginate(10, ['*'], 'stocks_page');

        $movements = $warehouse->movements()
            ->with(['product', 'user'])
            ->latest()
            ->take(10)
            ->get();

        return view('warehouses.show', [
            'warehouse' => $warehouse,
            'stocks' => $stocks,
            'movements' => $movements,
            'blockers' => $this->warehouses->deletionBlockers($warehouse),
        ]);
    }

    public function edit(Warehouse $warehouse)
    {
        abort_unless($this->canManage(), 403);

        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        $this->warehouses->update($warehouse, $request->validated());

        return redirect()->route('warehouses.show', $warehouse)->with('success', 'Warehouse updated.');
    }

    public function destroy(Warehouse $warehouse)
    {
        abort_unless(
            auth()->user()->hasPermission('warehouses.delete')
            || auth()->user()->hasPermission('warehouses.manage')
            || auth()->user()->canManageInventory(),
            403
        );

        try {
            $this->warehouses->delete($warehouse);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('warehouses.index')->with('success', 'Warehouse deleted.');
    }

    private function canView(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('warehouses.view')
            || $user->hasPermission('warehouses.manage')
            || $user->canManageInventory();
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('warehouses.create')
            || $user->hasPermission('warehouses.update')
            || $user->hasPermission('warehouses.manage')
            || $user->canManageInventory();
    }
}
