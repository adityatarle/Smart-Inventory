@extends('layouts.app')
@section('title', 'Edit Sales Person')
@section('heading', 'Edit Sales Person')
@section('content')
@include('sales.persons._form', ['action' => route('sales-persons.update', $salesPerson), 'salesPerson' => $salesPerson, 'method' => 'PUT'])
@endsection
