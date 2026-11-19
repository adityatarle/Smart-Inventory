@extends('layouts.app')
@section('title', 'Receive '.$transfer->document_number)
@section('heading', 'Receive '.$transfer->document_number)
@section('subheading', $transfer->fromWarehouse?->name.' → '.$transfer->toWarehouse?->name)
@section('breadcrumb', 'SmartStock / Inventory / Transfers / Receive')

@section('content')
<form method="POST" action="{{ route('stock-transfers.receive.store', $transfer) }}" class="space-y-5">
    @csrf

    <div class="ss-card overflow-hidden">
        <div class="border-b border-white/10 px-5 py-4 font-semibold">Quantities to receive</div>
        <div class="table-wrap border-0">
            <table class="data">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Dispatched</th>
                        <th>Already received</th>
                        <th>In transit</th>
                        <th>Receive now</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfer->items as $index => $item)
                        <tr>
                            <td>
                                <div class="font-medium">{{ $item->product?->name }}</div>
                                <div class="font-mono text-xs text-ss-muted">{{ $item->product?->sku }}</div>
                                <input type="hidden" name="receipts[{{ $index }}][item_id]" value="{{ $item->id }}">
                            </td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->quantity_received }}</td>
                            <td class="text-sky-300">{{ $item->quantityInTransit() }}</td>
                            <td>
                                <input type="number" min="0" max="{{ $item->quantityInTransit() }}"
                                       name="receipts[{{ $index }}][quantity]"
                                       value="{{ old('receipts.'.$index.'.quantity', $item->quantityInTransit()) }}"
                                       class="input w-28"
                                       @disabled($item->quantityInTransit() === 0)>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
        <button class="btn-primary w-full justify-center sm:w-auto">Post receipt</button>
        <a href="{{ route('stock-transfers.show', $transfer) }}" class="btn-secondary w-full justify-center sm:w-auto">Back</a>
    </div>
</form>
@endsection
