@extends('layouts.app')
@section('title', 'New Stock Out')
@section('heading', 'New Stock Out')
@section('subheading', 'Create a stock issue document')
@section('breadcrumb', 'SmartStock / Inventory / Stock Out / Create')
@section('content')
@include('inventory.issues._form', ['action' => route('stock-issues.store'), 'issue' => null, 'warehouses' => $warehouses, 'products' => $products])
@endsection
