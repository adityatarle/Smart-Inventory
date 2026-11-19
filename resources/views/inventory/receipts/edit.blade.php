@extends('layouts.app')
@section('title', 'Edit Stock In')
@section('heading', 'Edit '.$receipt->document_number)
@section('subheading', 'Draft receipt')
@section('breadcrumb', 'SmartStock / Inventory / Stock In / Edit')
@section('content')
@include('inventory.receipts._form', ['action' => route('stock-receipts.update', $receipt), 'receipt' => $receipt, 'warehouses' => $warehouses, 'products' => $products, 'method' => 'PUT'])
@endsection
