@extends('layouts.app')

@section('title', 'Add User')
@section('heading', 'Add User')

@section('content')
@include('users._form', ['action' => route('users.store'), 'user' => null, 'roles' => $roles])
@endsection
