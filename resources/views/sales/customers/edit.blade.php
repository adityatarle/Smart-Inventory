@extends('layouts.app')
@section('title', 'Edit Customer')
@section('heading', 'Edit Customer')
@section('subheading', $customer->code)
@section('breadcrumb', 'SmartStock / Customers / Edit')
@section('content')
@include('sales.customers._form', [
    'action' => route('customers.update', $customer),
    'customer' => $customer,
    'partners' => $partners,
    'persons' => $persons,
    'users' => $users,
    'method' => 'PUT',
])
@endsection
