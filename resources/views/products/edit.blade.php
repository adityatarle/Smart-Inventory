@extends('layouts.app')

@section('title', 'Edit Product')
@section('heading', 'Edit Product')
@section('subheading', $product->sku)
@section('breadcrumb', 'SmartStock / Products / Edit')

@section('content')
@include('products._form', [
    'action' => route('products.update', $product),
    'product' => $product,
    'categories' => $categories,
    'method' => 'PUT',
])
@endsection
