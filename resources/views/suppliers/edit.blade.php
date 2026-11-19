@extends('layouts.app')
@section('title', 'Edit Supplier')
@section('heading', 'Edit Supplier')
@section('subheading', $supplier->code)
@section('breadcrumb', 'SmartStock / Suppliers / Edit')
@section('content')
@include('suppliers._form', ['action' => route('suppliers.update', $supplier), 'supplier' => $supplier, 'method' => 'PUT'])
@endsection
