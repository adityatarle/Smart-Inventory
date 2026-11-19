<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categories)
    {
    }

    public function index(Request $request)
    {
        abort_unless($this->canView(), 403);

        $categories = Category::query()
            ->with(['parent'])
            ->withCount(['products', 'children'])
            ->search($request->get('search'))
            ->active($request->get('status'))
            ->when($request->filled('parent_id'), function ($q) use ($request) {
                if ($request->parent_id === 'root') {
                    $q->whereNull('parent_id');
                } else {
                    $q->where('parent_id', $request->parent_id);
                }
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => Category::count(),
            'active' => Category::where('is_active', true)->count(),
            'parents' => Category::whereNull('parent_id')->count(),
            'with_products' => Category::has('products')->count(),
        ];

        return view('categories.index', [
            'categories' => $categories,
            'parents' => Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name']),
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        abort_unless($this->canManage(), 403);

        return view('categories.create', [
            'parents' => $this->categories->parentOptions(),
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        $this->categories->create($request->validated());

        return redirect()->route('categories.index')->with('success', 'Category created.');
    }

    public function show(Category $category)
    {
        abort_unless($this->canView(), 403);

        $category->load(['parent', 'children'])
            ->loadCount(['products', 'children']);

        $products = $category->products()
            ->withSum('stocks as total_qty', 'quantity')
            ->latest()
            ->paginate(10);

        return view('categories.show', [
            'category' => $category,
            'products' => $products,
            'blockers' => $this->categories->deletionBlockers($category),
        ]);
    }

    public function edit(Category $category)
    {
        abort_unless($this->canManage(), 403);

        return view('categories.edit', [
            'category' => $category,
            'parents' => $this->categories->parentOptions($category),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        try {
            $this->categories->update($category, $request->validated());
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('categories.show', $category)->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        abort_unless(
            auth()->user()->hasPermission('categories.delete')
            || auth()->user()->hasPermission('categories.manage')
            || auth()->user()->canManageInventory(),
            403
        );

        try {
            $this->categories->delete($category);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }

    private function canView(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('categories.view')
            || $user->hasPermission('categories.manage')
            || $user->canManageInventory();
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->hasPermission('categories.create')
            || $user->hasPermission('categories.update')
            || $user->hasPermission('categories.manage')
            || $user->canManageInventory();
    }
}
