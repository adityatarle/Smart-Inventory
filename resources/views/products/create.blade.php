@extends('layouts.app')

@section('title', 'Add Product')
@section('heading', 'Add Product')
@section('subheading', 'Create a new catalog item')
@section('breadcrumb', 'SmartStock / Products / Create')

@section('content')
@include('products._form', [
    'action' => route('products.store'),
    'product' => null,
    'categories' => $categories,
    'suggestedSku' => $suggestedSku,
])
@endsection
