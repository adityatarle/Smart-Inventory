@extends('layouts.guest')

@section('title', 'Register · Smart Inventory')
@section('subheading', 'Create a customer account')

@section('content')
<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf
    <div>
        <label class="label" for="name">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" class="input" required autofocus>
    </div>
    <div>
        <label class="label" for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" class="input" required>
    </div>
    <div>
        <label class="label" for="password">Password</label>
        <input id="password" type="password" name="password" class="input" required>
    </div>
    <div>
        <label class="label" for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" class="input" required>
    </div>
    <button class="btn-primary w-full">Create account</button>
</form>
<p class="mt-5 text-center text-sm text-muted">
    Already registered?
    <a href="{{ route('login') }}" class="font-semibold text-teal-300 hover:text-teal-200">Sign in</a>
</p>
@endsection
