@extends('layouts.app')
@section('title', 'Edit Customer')
@section('heading', 'Edit Customer')
@section('content')
@include('sales.customers._form', ['action' => route('customers.update', $customer), 'customer' => $customer, 'method' => 'PUT'])
@endsection
