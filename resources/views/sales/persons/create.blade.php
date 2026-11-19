@extends('layouts.app')
@section('title', 'Add Sales Person')
@section('heading', 'Add Sales Person')
@section('content')
@include('sales.persons._form', ['action' => route('sales-persons.store'), 'salesPerson' => null])
@endsection
