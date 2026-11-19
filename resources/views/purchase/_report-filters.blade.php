<form method="GET" class="ss-page-toolbar mb-4">
    <div class="grid w-full gap-2 sm:grid-cols-5">
        <select name="supplier_id" class="input">
            <option value="">All suppliers</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>
        <select name="warehouse_id" class="input">
            <option value="">All warehouses</option>
            @foreach($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected(request('warehouse_id') == $warehouse->id)>{{ $warehouse->name }}</option>
            @endforeach
        </select>
        @isset($statuses)
        <select name="status" class="input">
            <option value="">All statuses</option>
            @foreach($statuses as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_',' ', ucfirst($status)) }}</option>
            @endforeach
        </select>
        @endisset
        <input type="date" name="date_from" class="input" value="{{ request('date_from') }}">
        <input type="date" name="date_to" class="input" value="{{ request('date_to') }}">
    </div>
    <div class="flex gap-2">
        <button class="btn-secondary">Filter</button>
        @if(!empty($export))
            <a href="{{ request()->fullUrlWithQuery(['export' => 1]) }}" class="btn-secondary">CSV</a>
        @endif
        <a href="{{ route('purchase.reports') }}" class="btn-secondary">Hub</a>
    </div>
</form>
