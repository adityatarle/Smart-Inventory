@extends('layouts.app')
@section('title', 'Add Customer')
@section('heading', 'Add Customer')
@section('content')
@include('sales.customers._form', ['action' => route('customers.store'), 'customer' => null])
@endsection
