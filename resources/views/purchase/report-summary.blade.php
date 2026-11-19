@extends('layouts.app')
@section('title', 'Purchase Summary')
@section('heading', 'Purchase Summary')
@section('breadcrumb', 'SmartStock / Purchase / Reports / Summary')
@section('content')
@include('purchase._report-filters')
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach([
        ['Orders', $summary['orders']],
        ['Total value', '$'.number_format($summary['value'], 2)],
        ['Pending approval', $summary['pending']],
        ['Approved', $summary['approved']],
        ['Partial', $summary['partial']],
        ['Fully received', $summary['fully_received']],
        ['Cancelled', $summary['cancelled']],
    ] as [$label, $value])
        <div class="ss-card p-4">
            <p class="text-xs text-ss-muted">{{ $label }}</p>
            <p class="mt-1 text-xl font-semibold">{{ $value }}</p>
        </div>
    @endforeach
</div>
@endsection
