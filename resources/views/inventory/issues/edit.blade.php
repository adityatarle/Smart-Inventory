@extends('layouts.app')
@section('title', 'Edit Stock Out')
@section('heading', 'Edit '.$issue->document_number)
@section('subheading', 'Draft issue')
@section('breadcrumb', 'SmartStock / Inventory / Stock Out / Edit')
@section('content')
@include('inventory.issues._form', ['action' => route('stock-issues.update', $issue), 'issue' => $issue, 'warehouses' => $warehouses, 'products' => $products, 'method' => 'PUT'])
@endsection
