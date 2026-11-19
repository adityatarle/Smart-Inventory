@extends('layouts.app')
@section('title', 'Change Password · SmartStock')
@section('heading', 'Change Password')
@section('subheading', 'Keep your account secure')
@section('breadcrumb', 'SmartStock / Settings / Password')

@section('content')
<form method="POST" action="{{ route('profile.password.update') }}" class="ss-card max-w-xl space-y-4 p-6">
    @csrf
    @method('PUT')
    <div>
        <label class="ss-label">Current password</label>
        <input type="password" name="current_password" class="ss-input" required>
    </div>
    <div>
        <label class="ss-label">New password</label>
        <input type="password" name="password" class="ss-input" required>
    </div>
    <div>
        <label class="ss-label">Confirm new password</label>
        <input type="password" name="password_confirmation" class="ss-input" required>
    </div>
    <button class="ss-btn-primary">Update password</button>
</form>
@endsection
