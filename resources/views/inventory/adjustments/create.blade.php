@extends('layouts.app')
@section('title', 'New Adjustment')
@section('heading', 'New Stock Adjustment')
@section('subheading', 'Enter physical quantities; difference posts on submit')
@section('breadcrumb', 'SmartStock / Inventory / Adjustments / Create')

@section('content')
@include('inventory.adjustments._form', [
    'action' => route('stock-adjustments.store'),
    'method' => null,
    'adjustment' => null,
])
@endsection
