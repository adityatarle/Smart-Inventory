@extends('layouts.app')

@section('title', 'Import Products')
@section('heading', 'Import Products')
@section('subheading', 'Upload a CSV file to create or update products by SKU')
@section('breadcrumb', 'SmartStock / Products / Import')

@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    <form method="POST" action="{{ route('products.import.store') }}" enctype="multipart/form-data" class="ss-card space-y-4 p-5">
        @csrf
        <div>
            <label class="label">CSV file <span class="text-ss-danger">*</span></label>
            <input type="file" name="file" accept=".csv,text/csv" class="input" required>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <button class="btn-primary w-full justify-center sm:w-auto">Import</button>
            <a href="{{ route('products.index') }}" class="btn-secondary w-full justify-center sm:w-auto">Cancel</a>
        </div>
    </form>

    <div class="ss-card space-y-3 p-5 text-sm text-ss-muted">
        <h2 class="font-semibold text-ss-text">CSV columns</h2>
        <p>sku, product_code, barcode, name, category, unit, cost_price, sell_price, reorder_level, max_stock_level, is_active, description</p>
        <p>Existing SKUs are updated. Unknown categories are left blank. Use Export CSV for a ready template.</p>
    </div>
</div>
@endsection
