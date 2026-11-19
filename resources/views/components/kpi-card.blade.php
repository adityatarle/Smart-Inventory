@props([
    'label',
    'value',
    'icon' => 'package',
    'tone' => 'primary',
])

@php
    $tones = [
        'primary' => ['text' => 'text-ss-primary', 'bg' => 'bg-ss-primary/15'],
        'secondary' => ['text' => 'text-ss-secondary', 'bg' => 'bg-ss-secondary/15'],
        'success' => ['text' => 'text-ss-success', 'bg' => 'bg-ss-success/15'],
        'warning' => ['text' => 'text-ss-warning', 'bg' => 'bg-ss-warning/15'],
        'danger' => ['text' => 'text-ss-danger', 'bg' => 'bg-ss-danger/15'],
        'info' => ['text' => 'text-sky-300', 'bg' => 'bg-sky-400/15'],
    ];
    $style = $tones[$tone] ?? $tones['primary'];
@endphp

<div {{ $attributes->merge(['class' => 'ss-card p-4 sm:p-5']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="text-xs font-semibold uppercase tracking-wide text-ss-muted">{{ $label }}</div>
            <div class="mt-2 truncate text-2xl font-bold {{ $style['text'] }}">{{ $value }}</div>
        </div>
        <div class="rounded-xl {{ $style['bg'] }} p-2.5 {{ $style['text'] }}">
            <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
        </div>
    </div>
</div>
