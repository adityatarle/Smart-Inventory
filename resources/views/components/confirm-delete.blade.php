@props([
    'action',
    'title' => 'Delete record?',
    'message' => 'This action cannot be undone.',
    'button' => 'Delete',
])

<div x-data="{ open: false }" {{ $attributes }}>
    <button type="button" @click="open = true" class="{{ $attributes->get('buttonClass', 'text-rose-300 hover:text-rose-200') }}">
        {{ $slot->isEmpty() ? $button : $slot }}
    </button>

    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[80] flex items-center justify-center bg-black/60 p-4"
        @keydown.escape.window="open = false"
    >
        <div class="w-full max-w-md rounded-2xl border border-white/10 bg-ss-card p-5 shadow-2xl" @click.outside="open = false">
            <h3 class="text-lg font-semibold">{{ $title }}</h3>
            <p class="mt-2 text-sm text-ss-muted">{{ $message }}</p>
            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="btn-secondary w-full justify-center sm:w-auto" @click="open = false">Cancel</button>
                <form method="POST" action="{{ $action }}" class="w-full sm:w-auto">
                    @csrf
                    @method('DELETE')
                    <button class="btn-danger w-full justify-center">Confirm delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
