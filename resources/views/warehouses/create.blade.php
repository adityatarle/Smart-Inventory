@extends('layouts.app')

@section('title', 'Add Warehouse')
@section('heading', 'Add Warehouse')
@section('subheading', 'Create a new storage location')

@section('content')
@include('warehouses._form', ['action' => route('warehouses.store'), 'warehouse' => null])
@endsection
