@extends('layouts.guest')
@section('title', 'Reset Password · SmartStock')
@section('subheading', 'Choose a new password')

@section('content')
<form method="POST" action="{{ route('password.update') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div>
        <label class="ss-label" for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" class="ss-input" required>
    </div>
    <div>
        <label class="ss-label" for="password">New password</label>
        <input id="password" type="password" name="password" class="ss-input" required>
    </div>
    <div>
        <label class="ss-label" for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" class="ss-input" required>
    </div>
    <button class="ss-btn-primary w-full">Reset password</button>
</form>
@endsection
