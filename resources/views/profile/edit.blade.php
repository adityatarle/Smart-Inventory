@extends('layouts.app')
@section('title', 'Profile · SmartStock')
@section('heading', 'My Profile')
@section('subheading', 'Update your personal account details')
@section('breadcrumb', 'SmartStock / Settings / Profile')

@section('content')
<form method="POST" action="{{ route('profile.update') }}" class="ss-card max-w-xl space-y-4 p-6">
    @csrf
    @method('PUT')
    <div>
        <label class="ss-label">Name</label>
        <input name="name" class="ss-input" value="{{ old('name', $user->name) }}" required>
    </div>
    <div>
        <label class="ss-label">Email</label>
        <input type="email" name="email" class="ss-input" value="{{ old('email', $user->email) }}" required>
    </div>
    <div>
        <label class="ss-label">Phone</label>
        <input name="phone" class="ss-input" value="{{ old('phone', $user->phone) }}">
    </div>
    <div class="text-sm text-ss-muted">Role: <span class="text-ss-text">{{ $user->primaryRoleName() }}</span></div>
    <button class="ss-btn-primary">Save profile</button>
</form>
@endsection
