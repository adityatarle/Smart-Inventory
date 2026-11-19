@extends('layouts.app')

@section('title', 'Categories')
@section('heading', 'Categories')
@section('subheading', 'Organize products by category')

@section('content')
<div class="mb-5 flex justify-end">
    <a href="{{ route('categories.create') }}" class="btn-primary">Add category</a>
</div>

<div class="card overflow-hidden">
    <div class="table-wrap border-0">
        <table class="data">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Products</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                    <tr>
                        <td class="font-medium">{{ $category->name }}</td>
                        <td class="text-muted">{{ $category->slug }}</td>
                        <td>{{ $category->products_count }}</td>
                        <td class="space-x-3 text-right">
                            <a href="{{ route('categories.edit', $category) }}" class="text-teal-300">Edit</a>
                            <form action="{{ route('categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Delete category?')">
                                @csrf @method('DELETE')
                                <button class="text-rose-300">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $categories->links() }}</div>
@endsection
