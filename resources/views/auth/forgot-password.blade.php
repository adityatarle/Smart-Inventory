@extends('layouts.guest')
@section('title', 'Forgot Password · SmartStock')
@section('subheading', 'We will email you a reset link')

@section('content')
<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf
    <div>
        <label class="ss-label" for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" class="ss-input" required autofocus>
    </div>
    <button class="ss-btn-primary w-full">Send reset link</button>
</form>
<p class="mt-5 text-center text-sm text-ss-muted">
    <a href="{{ route('login') }}" class="font-semibold text-ss-primary">Back to login</a>
</p>
@endsection
