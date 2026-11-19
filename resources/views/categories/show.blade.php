@extends('layouts.app')

@section('title', $category->name)
@section('heading', $category->name)
@section('subheading', $category->fullPath())
@section('breadcrumb', 'SmartStock / Categories / Details')

@section('content')
@php
    $canManage = auth()->user()->hasPermission('categories.manage')
        || auth()->user()->hasPermission('categories.update')
        || auth()->user()->canManageInventory();
@endphp

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex flex-wrap gap-2">
        <span class="badge {{ $category->is_active ? 'bg-teal-400/15 text-teal-300' : 'bg-rose-400/15 text-rose-300' }}">
            {{ $category->is_active ? 'Active' : 'Inactive' }}
        </span>
        <span class="badge bg-ss-primary/15 text-ss-primary">{{ $category->products_count }} products</span>
        <span class="badge bg-ss-secondary/15 text-ss-secondary">{{ $category->children_count }} children</span>
    </div>
    <div class="flex flex-col gap-2 sm:flex-row">
        @if($canManage)
            <a href="{{ route('categories.edit', $category) }}" class="btn-primary w-full justify-center sm:w-auto">Edit</a>
            <x-confirm-delete
                :action="route('categories.destroy', $category)"
                title="Delete category?"
                :message="count($blockers) ? implode(' ', $blockers) : 'Delete this category permanently?'"
                class="w-full sm:w-auto"
                buttonClass="btn-danger w-full justify-center"
            />
        @endif
        <a href="{{ route('categories.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Back</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="ss-card space-y-3 p-5 lg:col-span-1">
        <h2 class="font-semibold">Category details</h2>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Slug</span><span>{{ $category->slug }}</span></div>
        <div class="flex justify-between gap-3 text-sm"><span class="text-ss-muted">Parent</span><span>{{ $category->parent?->name ?: '—' }}</span></div>
        <p class="pt-2 text-sm text-ss-muted">{{ $category->description ?: 'No description.' }}</p>
    </div>

    <div class="ss-card p-5 lg:col-span-2">
        <h2 class="mb-4 font-semibold">Child categories</h2>
        @forelse($category->children as $child)
            <a href="{{ route('categories.show', $child) }}" class="mb-2 flex items-center justify-between rounded-xl border border-white/10 bg-ss-card-2 px-3 py-2 hover:border-ss-primary/30">
                <span class="text-sm font-medium">{{ $child->name }}</span>
                <span class="badge {{ $child->is_active ? 'bg-teal-400/15 text-teal-300' : 'bg-rose-400/15 text-rose-300' }}">{{ $child->is_active ? 'Active' : 'Inactive' }}</span>
            </a>
        @empty
            <p class="text-sm text-ss-muted">No child categories.</p>
        @endforelse
    </div>
</div>

<div class="mt-6 ss-card overflow-hidden">
    <div class="border-b border-white/10 px-5 py-4 font-semibold">Products in this category</div>
    @if($products->isEmpty())
        <div class="p-6 text-sm text-ss-muted">No products assigned yet.</div>
    @else
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Name</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td class="font-mono text-xs">{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->total_qty ?? 0 }} {{ $product->unit }}</td>
                            <td>
                                <span class="badge {{ $product->is_active ? 'bg-teal-400/15 text-teal-300' : 'bg-rose-400/15 text-rose-300' }}">
                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('products.show', $product) }}" class="text-sky-300">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3">{{ $products->links() }}</div>
    @endif
</div>
@endsection
