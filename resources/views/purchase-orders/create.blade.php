@extends('layouts.app')
@section('title', 'New Purchase Order')
@section('heading', 'New Purchase Order')
@section('subheading', 'Draft → submit for approval')
@section('breadcrumb', 'SmartStock / Purchase / New PO')

@section('content')
@include('purchase-orders._form', [
    'action' => route('purchase-orders.store'),
    'method' => null,
    'purchaseOrder' => null,
])
@endsection
