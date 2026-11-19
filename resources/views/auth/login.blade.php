@extends('layouts.guest')

@section('title', 'Login · Smart Inventory')
@section('subheading', 'Access your inventory workspace')

@section('content')
<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf
    <div>
        <label class="label" for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" class="input" required autofocus>
    </div>
    <div>
        <label class="label" for="password">Password</label>
        <input id="password" type="password" name="password" class="input" required>
    </div>
    <label class="flex items-center gap-2 text-sm text-muted">
        <input type="checkbox" name="remember" class="rounded border-line bg-slate-900 text-teal-400 focus:ring-teal-400/40">
        Remember me
    </label>
    <button class="btn-primary w-full">Sign in</button>
</form>
<p class="mt-5 text-center text-sm text-muted">
    No account?
    <a href="{{ route('register') }}" class="font-semibold text-teal-300 hover:text-teal-200">Register as customer</a>
</p>
@endsection
