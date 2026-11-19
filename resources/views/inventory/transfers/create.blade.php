@extends('layouts.app')
@section('title', 'New Transfer')
@section('heading', 'New Warehouse Transfer')
@section('subheading', 'Draft then dispatch to move stock into transit')
@section('breadcrumb', 'SmartStock / Inventory / Transfers / Create')

@section('content')
@include('inventory.transfers._form', [
    'action' => route('stock-transfers.store'),
    'method' => null,
    'transfer' => null,
])
@endsection
