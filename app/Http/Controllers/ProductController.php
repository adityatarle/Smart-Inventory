<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products)
    {
    }

    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $sort = in_array($request->get('sort'), ['name', 'sku', 'sell_price', 'created_at', 'cost_price'], true)
            ? $request->get('sort')
            : 'created_at';
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';

        $query = Product::query()
            ->with('category')
            ->withSum('stocks as total_qty', 'quantity')
            ->search($request->get('search'))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->active($request->get('status'))
            ->orderBy($sort, $direction);

        if ($request->boolean('export')) {
            abort_unless($this->canManage(), 403);

            return $this->products->exportCsv($query);
        }

        $products = $query->paginate(12)->withQueryString();

        $stats = [
            'total' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'inactive' => Product::where('is_active', false)->count(),
            'low_stock' => Product::query()
                ->whereRaw('(select coalesce(sum(quantity), 0) from warehouse_stocks where warehouse_stocks.product_id = products.id) <= products.reorder_level')
                ->count(),
        ];

        return view('products.index', [
            'products' => $products,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'stats' => $stats,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create()
    {
        abort_unless($this->canManage(), 403);

        return view('products.create', [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'suggestedSku' => $this->products->suggestSku(),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $this->products->create(
            $request->safe()->except(['image', 'remove_image']),
            $request->file('image')
        );

        return redirect()->route('products.index')->with('success', 'Product created.');
    }

    public function show(Product $product)
    {
        abort_unless($this->canView(), 403);

        $product->load(['category', 'stocks.warehouse']);
        $product->loadSum('stocks as total_qty', 'quantity');

        return view('products.show', [
            'product' => $product,
            'blockers' => $this->products->deletionBlockers($product),
        ]);
    }

    public function edit(Product $product)
    {
        abort_unless($this->canManage(), 403);

        return view('products.edit', [
            'product' => $product,
            'categories' => Category::query()
                ->where(function ($q) use ($product) {
                    $q->where('is_active', true)
                        ->orWhere('id', $product->category_id);
                })
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->products->update(
            $product,
            $request->safe()->except(['image', 'remove_image']),
            $request->file('image'),
            $request->boolean('remove_image')
        );

        return redirect()->route('products.show', $product)->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        abort_unless(
            auth()->user()->hasPermission('products.delete')
            || auth()->user()->hasPermission('products.manage')
            || auth()->user()->canManageInventory(),
            403
        );

        try {
            $this->products->delete($product);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }

    public function importForm()
    {
        abort_unless($this->canManage(), 403);

        return view('products.import');
    }

    public function import(Request $request)
    {
        abort_unless($this->canManage(), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $result = $this->products->importCsv($request->file('file'));

        return redirect()
            ->route('products.index')
            ->with('success', "Import finished. Created {$result['created']}, updated {$result['updated']}, skipped {$result['skipped']}.");
    }

    private function canView(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('products.view')
            || $user->hasPermission('products.manage')
            || $user->canManageInventory()
            || $user->canOperateOrders();
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('products.create')
            || $user->hasPermission('products.update')
            || $user->hasPermission('products.manage')
            || $user->canManageInventory();
    }
}
