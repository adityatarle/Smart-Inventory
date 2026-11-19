@extends('layouts.app')
@section('title', 'Add Warehouse')
@section('heading', 'Add Warehouse')
@section('subheading', 'Register a new storage location')
@section('breadcrumb', 'SmartStock / Warehouses / Create')
@section('content')
@include('warehouses._form', ['action' => route('warehouses.store'), 'warehouse' => null, 'suggestedCode' => $suggestedCode])
@endsection
