@extends('layouts.app')
@section('title', 'Add Customer')
@section('heading', 'Add Customer')
@section('subheading', 'Create a customer master record')
@section('breadcrumb', 'SmartStock / Customers / Create')
@section('content')
@include('sales.customers._form', ['action' => route('customers.store'), 'customer' => null, 'partners' => $partners, 'persons' => $persons, 'users' => $users])
@endsection
