@extends('layouts.app')
@section('title', 'New Stock In')
@section('heading', 'New Stock In')
@section('subheading', 'Create a goods receipt document')
@section('breadcrumb', 'SmartStock / Inventory / Stock In / Create')
@section('content')
@include('inventory.receipts._form', ['action' => route('stock-receipts.store'), 'receipt' => null, 'warehouses' => $warehouses, 'products' => $products])
@endsection
