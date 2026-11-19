@extends('layouts.app')
@section('title', 'Edit Warehouse')
@section('heading', 'Edit Warehouse')
@section('subheading', $warehouse->code)
@section('breadcrumb', 'SmartStock / Warehouses / Edit')
@section('content')
@include('warehouses._form', ['action' => route('warehouses.update', $warehouse), 'warehouse' => $warehouse, 'method' => 'PUT'])
@endsection
