@extends('layouts.app')
@section('title', 'Edit '.$adjustment->document_number)
@section('heading', 'Edit '.$adjustment->document_number)
@section('subheading', 'Draft adjustment')
@section('breadcrumb', 'SmartStock / Inventory / Adjustments / Edit')

@section('content')
@include('inventory.adjustments._form', [
    'action' => route('stock-adjustments.update', $adjustment),
    'method' => 'PUT',
    'adjustment' => $adjustment,
])
@endsection
