@props([
    'title' => 'Nothing here yet',
    'description' => 'Get started by creating your first record.',
    'icon' => 'inbox',
])

<div {{ $attributes->merge(['class' => 'ss-card flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <div class="mb-4 rounded-2xl bg-ss-primary/15 p-4 text-ss-primary">
        <i data-lucide="{{ $icon }}" class="h-8 w-8"></i>
    </div>
    <h3 class="text-lg font-semibold">{{ $title }}</h3>
    <p class="mt-2 max-w-md text-sm text-ss-muted">{{ $description }}</p>
    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>
