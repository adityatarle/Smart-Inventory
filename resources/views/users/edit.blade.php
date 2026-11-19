@extends('layouts.app')

@section('title', 'Edit User')
@section('heading', 'Edit User')

@section('content')
@include('users._form', ['action' => route('users.update', $user), 'user' => $user, 'roles' => $roles, 'method' => 'PUT'])
@endsection
