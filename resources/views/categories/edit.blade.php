@extends('layouts.app')

@section('title', 'Edit Category')
@section('heading', 'Edit Category')
@section('subheading', $category->name)
@section('breadcrumb', 'SmartStock / Categories / Edit')

@section('content')
@include('categories._form', [
    'action' => route('categories.update', $category),
    'category' => $category,
    'parents' => $parents,
    'method' => 'PUT',
])
@endsection
