@extends('layouts.app')
@section('title', $title.' · SmartStock')
@section('heading', $title)
@section('subheading', $phase.' module scaffold')

@section('content')
<div class="ss-card mx-auto max-w-2xl p-8 text-center">
    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-ss-primary/15 text-ss-primary">
        <i data-lucide="construction" class="h-6 w-6"></i>
    </div>
    <h2 class="text-xl font-semibold">{{ $title }}</h2>
    <p class="mt-2 text-sm text-ss-muted">{{ $description }}</p>
    <p class="mt-4 text-xs uppercase tracking-wide text-ss-secondary">Scheduled: {{ $phase }}</p>
    <a href="{{ route('dashboard') }}" class="ss-btn-secondary mt-6">Back to dashboard</a>
</div>
@endsection
