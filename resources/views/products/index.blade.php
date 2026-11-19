@extends('layouts.app')

@section('title', 'Products')
@section('heading', 'Products')
@section('subheading', 'Catalog, pricing, and stock overview')
@section('breadcrumb', 'SmartStock / Products')

@section('content')
@php
    $canManage = auth()->user()->hasPermission('products.manage')
        || auth()->user()->hasPermission('products.create')
        || auth()->user()->canManageInventory();
@endphp

<div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-kpi-card label="Total Products" :value="number_format($stats['total'])" icon="package" tone="primary" />
    <x-kpi-card label="Active" :value="number_format($stats['active'])" icon="check-circle" tone="success" />
    <x-kpi-card label="Inactive" :value="number_format($stats['inactive'])" icon="pause-circle" tone="secondary" />
    <x-kpi-card label="Low Stock" :value="number_format($stats['low_stock'])" icon="triangle-alert" tone="warning" />
</div>

<div class="ss-page-toolbar">
    <form method="GET" class="w-full">
        <div class="grid w-full gap-2 sm:grid-cols-2 lg:grid-cols-5">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name, SKU, barcode" class="input lg:col-span-2">
            <select name="category_id" class="input">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="status" class="input">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <div class="flex gap-2">
                <button class="btn-secondary w-full justify-center">Filter</button>
                @if(request()->hasAny(['search', 'category_id', 'status', 'sort']))
                    <a href="{{ route('products.index') }}" class="btn-secondary shrink-0 px-3" title="Clear">Clear</a>
                @endif
            </div>
        </div>
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
    </form>

    @if($canManage)
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
            <a href="{{ route('products.import') }}" class="btn-secondary w-full justify-center sm:w-auto">Import</a>
            <a href="{{ route('products.index', request()->query() + ['export' => 1]) }}" class="btn-secondary w-full justify-center sm:w-auto">Export CSV</a>
            <a href="{{ route('products.create') }}" class="btn-primary w-full justify-center sm:w-auto">Add product</a>
        </div>
    @endif
</div>

@if($products->isEmpty())
    <x-empty-state title="No products found" description="Try adjusting filters or create your first catalog item." icon="package">
        <x-slot:action>
            @if($canManage)
                <a href="{{ route('products.create') }}" class="btn-primary">Add product</a>
            @endif
        </x-slot:action>
    </x-empty-state>
@else
    {{-- Mobile cards --}}
    <div class="space-y-3 md:hidden">
        @foreach($products as $product)
            <div class="ss-card p-4" x-data="{ open: false }">
                <div class="flex items-start gap-3">
                    @if($product->imageUrl())
                        <img src="{{ $product->imageUrl() }}" alt="" class="h-12 w-12 rounded-xl object-cover">
                    @else
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-ss-primary/15 text-ss-primary">
                            <i data-lucide="package" class="h-5 w-5"></i>
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-semibold">{{ $product->name }}</div>
                        <div class="mt-1 font-mono text-xs text-ss-muted">{{ $product->sku }}</div>
                        <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-sm text-ss-muted">
                            <span>{{ $product->category?->name ?: '—' }}</span>
                            <span>${{ number_format($product->sell_price, 2) }}</span>
                            <span class="{{ ($product->total_qty ?? 0) <= $product->reorder_level ? 'text-amber-300' : 'text-teal-300' }}">
                                {{ $product->total_qty ?? 0 }} {{ $product->unit }}
                            </span>
                        </div>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-ss-muted hover:bg-white/5" @click="open = !open">
                        <i data-lucide="more-vertical" class="h-4 w-4"></i>
                    </button>
                </div>
                <div x-show="open" x-cloak class="mt-3 flex gap-3 border-t border-white/10 pt-3 text-sm">
                    <a href="{{ route('products.show', $product) }}" class="font-semibold text-sky-300">View</a>
                    @if($canManage)
                        <a href="{{ route('products.edit', $product) }}" class="font-semibold text-teal-300">Edit</a>
                        <x-confirm-delete :action="route('products.destroy', $product)" title="Delete product?" :message="'Delete '.$product->name.'? This cannot be undone if the product is unused.'" buttonClass="font-semibold text-rose-300" />
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Desktop table --}}
    <div class="card hidden overflow-hidden md:block">
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>
                            <a href="{{ route('products.index', array_merge(request()->query(), ['sort' => 'sku', 'direction' => $sort === 'sku' && $direction === 'asc' ? 'desc' : 'asc'])) }}" class="hover:text-ss-text">SKU</a>
                        </th>
                        <th>Category</th>
                        <th>
                            <a href="{{ route('products.index', array_merge(request()->query(), ['sort' => 'sell_price', 'direction' => $sort === 'sell_price' && $direction === 'asc' ? 'desc' : 'asc'])) }}" class="hover:text-ss-text">Sell price</a>
                        </th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    @if($product->imageUrl())
                                        <img src="{{ $product->imageUrl() }}" alt="" class="h-10 w-10 rounded-lg object-cover">
                                    @else
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-ss-primary/15 text-ss-primary">
                                            <i data-lucide="package" class="h-4 w-4"></i>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="truncate font-medium">{{ $product->name }}</div>
                                        <div class="truncate text-xs text-ss-muted">{{ $product->barcode ?: ($product->product_code ?: '—') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="font-mono text-xs">{{ $product->sku }}</td>
                            <td>{{ $product->category?->name ?: '—' }}</td>
                            <td>${{ number_format($product->sell_price, 2) }}</td>
                            <td>
                                <span class="{{ ($product->total_qty ?? 0) <= $product->reorder_level ? 'text-amber-300' : 'text-teal-300' }}">
                                    {{ $product->total_qty ?? 0 }} {{ $product->unit }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $product->is_active ? 'bg-teal-400/15 text-teal-300' : 'bg-rose-400/15 text-rose-300' }}">
                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right" x-data="{ open: false }">
                                <div class="relative inline-block text-left">
                                    <button type="button" class="rounded-lg border border-white/10 px-2.5 py-1.5 text-ss-muted hover:text-ss-text" @click="open = !open" @click.outside="open = false">
                                        <i data-lucide="more-horizontal" class="h-4 w-4"></i>
                                    </button>
                                    <div x-show="open" x-cloak class="absolute right-0 z-20 mt-2 w-36 rounded-xl border border-white/10 bg-ss-card p-1 shadow-xl">
                                        <a href="{{ route('products.show', $product) }}" class="ss-nav-item">View</a>
                                        @if($canManage)
                                            <a href="{{ route('products.edit', $product) }}" class="ss-nav-item">Edit</a>
                                            <x-confirm-delete :action="route('products.destroy', $product)" title="Delete product?" :message="'Delete '.$product->name.'?'" class="block w-full" buttonClass="ss-nav-item w-full text-rose-300" />
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="mt-4 overflow-x-auto">{{ $products->links() }}</div>
@endsection
