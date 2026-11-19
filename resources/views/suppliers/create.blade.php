@extends('layouts.app')
@section('title', 'Add Supplier')
@section('heading', 'Add Supplier')
@section('subheading', 'Create a vendor master record')
@section('breadcrumb', 'SmartStock / Suppliers / Create')
@section('content')
@include('suppliers._form', ['action' => route('suppliers.store'), 'supplier' => null, 'suggestedCode' => $suggestedCode])
@endsection
