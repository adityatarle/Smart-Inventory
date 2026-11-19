@extends('layouts.app')

@section('title', 'Add Category')
@section('heading', 'Add Category')

@section('content')
@include('categories._form', ['action' => route('categories.store'), 'category' => null])
@endsection
