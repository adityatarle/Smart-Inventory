@extends('layouts.app')

@section('title', 'Edit Category')
@section('heading', 'Edit Category')

@section('content')
@include('categories._form', ['action' => route('categories.update', $category), 'category' => $category, 'method' => 'PUT'])
@endsection
