@extends('layouts.app')
@section('title', 'Edit '.$purchaseOrder->order_number)
@section('heading', 'Edit '.$purchaseOrder->order_number)
@section('subheading', 'Draft / rejected purchase order')
@section('breadcrumb', 'SmartStock / Purchase / Edit')

@section('content')
@include('purchase-orders._form', [
    'action' => route('purchase-orders.update', $purchaseOrder),
    'method' => 'PUT',
    'purchaseOrder' => $purchaseOrder,
])
@endsection
