@extends('layouts.app')

@section('title', 'Add Product')
@section('heading', 'Add Product')

@section('content')
@include('products._form', ['action' => route('products.store'), 'product' => null, 'categories' => $categories])
@endsection
