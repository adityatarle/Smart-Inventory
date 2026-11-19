@extends('layouts.app')

@section('title', 'Categories')
@section('heading', 'Categories')
@section('subheading', 'Organize products with parent and child categories')
@section('breadcrumb', 'SmartStock / Categories')

@section('content')
@php
    $canManage = auth()->user()->hasPermission('categories.manage')
        || auth()->user()->hasPermission('categories.create')
        || auth()->user()->canManageInventory();
@endphp

<div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-kpi-card label="Total Categories" :value="number_format($stats['total'])" icon="tags" tone="primary" />
    <x-kpi-card label="Active" :value="number_format($stats['active'])" icon="check-circle" tone="success" />
    <x-kpi-card label="Top-level" :value="number_format($stats['parents'])" icon="folder-tree" tone="secondary" />
    <x-kpi-card label="With Products" :value="number_format($stats['with_products'])" icon="package" tone="info" />
</div>

<div class="ss-page-toolbar">
    <form method="GET" class="w-full">
        <div class="grid w-full gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search categories" class="input">
            <select name="parent_id" class="input">
                <option value="">All levels</option>
                <option value="root" @selected(request('parent_id') === 'root')>Top-level only</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" @selected(request('parent_id') == $parent->id)>Children of {{ $parent->name }}</option>
                @endforeach
            </select>
            <select name="status" class="input">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <div class="flex gap-2">
                <button class="btn-secondary w-full justify-center">Filter</button>
                @if(request()->hasAny(['search', 'parent_id', 'status']))
                    <a href="{{ route('categories.index') }}" class="btn-secondary shrink-0 px-3">Clear</a>
                @endif
            </div>
        </div>
    </form>

    @if($canManage)
        <a href="{{ route('categories.create') }}" class="btn-primary w-full justify-center sm:w-auto">Add category</a>
    @endif
</div>

@if($categories->isEmpty())
    <x-empty-state title="No categories found" description="Create categories to organize your product catalog." icon="tags">
        <x-slot:action>
            @if($canManage)
                <a href="{{ route('categories.create') }}" class="btn-primary">Add category</a>
            @endif
        </x-slot:action>
    </x-empty-state>
@else
    <div class="space-y-3 md:hidden">
        @foreach($categories as $category)
            <div class="ss-card p-4" x-data="{ open: false }">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="truncate font-semibold">{{ $category->name }}</div>
                        <div class="mt-1 text-xs text-ss-muted">
                            {{ $category->parent?->name ? 'Child of '.$category->parent->name : 'Top-level' }}
                            · {{ $category->products_count }} products
                            · {{ $category->children_count }} children
                        </div>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-ss-muted hover:bg-white/5" @click="open = !open">
                        <i data-lucide="more-vertical" class="h-4 w-4"></i>
                    </button>
                </div>
                <div x-show="open" x-cloak class="mt-3 flex gap-3 border-t border-white/10 pt-3 text-sm">
                    <a href="{{ route('categories.show', $category) }}" class="font-semibold text-sky-300">View</a>
                    @if($canManage)
                        <a href="{{ route('categories.edit', $category) }}" class="font-semibold text-teal-300">Edit</a>
                        <x-confirm-delete :action="route('categories.destroy', $category)" title="Delete category?" :message="'Delete '.$category->name.'?'" buttonClass="font-semibold text-rose-300" />
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="card hidden overflow-hidden md:block">
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Parent</th>
                        <th>Products</th>
                        <th>Children</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                        <tr>
                            <td>
                                <div class="font-medium">{{ $category->name }}</div>
                                <div class="text-xs text-ss-muted">{{ $category->slug }}</div>
                            </td>
                            <td>{{ $category->parent?->name ?: '—' }}</td>
                            <td>{{ $category->products_count }}</td>
                            <td>{{ $category->children_count }}</td>
                            <td>
                                <span class="badge {{ $category->is_active ? 'bg-teal-400/15 text-teal-300' : 'bg-rose-400/15 text-rose-300' }}">
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-right" x-data="{ open: false }">
                                <div class="relative inline-block">
                                    <button type="button" class="rounded-lg border border-white/10 px-2.5 py-1.5 text-ss-muted hover:text-ss-text" @click="open = !open" @click.outside="open = false">
                                        <i data-lucide="more-horizontal" class="h-4 w-4"></i>
                                    </button>
                                    <div x-show="open" x-cloak class="absolute right-0 z-20 mt-2 w-36 rounded-xl border border-white/10 bg-ss-card p-1 shadow-xl">
                                        <a href="{{ route('categories.show', $category) }}" class="ss-nav-item">View</a>
                                        @if($canManage)
                                            <a href="{{ route('categories.edit', $category) }}" class="ss-nav-item">Edit</a>
                                            <x-confirm-delete :action="route('categories.destroy', $category)" title="Delete category?" :message="'Delete '.$category->name.'?'" class="block w-full" buttonClass="ss-nav-item w-full text-rose-300" />
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

<div class="mt-4 overflow-x-auto">{{ $categories->links() }}</div>
@endsection
