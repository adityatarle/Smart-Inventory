@extends('layouts.guest')
@section('title', 'Login · SmartStock')
@section('subheading', 'Access your SmartStock workspace')

@section('content')
<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf
    <div>
        <label class="ss-label" for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" class="ss-input" required autofocus>
    </div>
    <div>
        <label class="ss-label" for="password">Password</label>
        <input id="password" type="password" name="password" class="ss-input" required>
    </div>
    <div class="flex items-center justify-between text-sm">
        <label class="flex items-center gap-2 text-ss-muted">
            <input type="checkbox" name="remember" class="rounded border-white/20 bg-[#12111F] text-ss-primary">
            Remember me
        </label>
        <a href="{{ route('password.request') }}" class="font-semibold text-ss-primary hover:text-purple-300">Forgot password?</a>
    </div>
    <button class="ss-btn-primary w-full">Sign in</button>
</form>
<p class="mt-5 text-center text-xs text-ss-muted">Demo: admin@smartstock.test / password</p>
@endsection
