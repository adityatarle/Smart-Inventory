@extends('layouts.app')

@section('title', 'Add Category')
@section('heading', 'Add Category')
@section('subheading', 'Create a parent or child category')
@section('breadcrumb', 'SmartStock / Categories / Create')

@section('content')
@include('categories._form', [
    'action' => route('categories.store'),
    'category' => null,
    'parents' => $parents,
])
@endsection
