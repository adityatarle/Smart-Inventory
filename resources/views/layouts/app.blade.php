<!DOCTYPE html>
<html lang="en" class="dark h-full overflow-x-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SmartStock ERP')</title>
    @include('partials.assets')
</head>
<body
    class="overflow-x-hidden"
    x-data="{
        sidebarOpen: true,
        mobileOpen: false,
        profileOpen: false,
        notifOpen: false,
        isDesktop: window.innerWidth >= 1024,
        syncViewport() {
            this.isDesktop = window.innerWidth >= 1024;
            if (this.isDesktop) this.mobileOpen = false;
        }
    }"
    x-init="syncViewport()"
    @resize.window.debounce.100ms="syncViewport()"
    :class="{ 'overflow-hidden': mobileOpen }"
>
@php
    $user = auth()->user();
    $unread = $user?->unreadNotificationsCount() ?? 0;
@endphp

<div class="flex min-h-screen w-full max-w-[100vw] overflow-x-hidden">
    {{-- Mobile overlay --}}
    <div
        x-show="mobileOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-40 bg-black/60 backdrop-blur-[2px] lg:hidden"
        @click="mobileOpen = false"
    ></div>

    {{-- Sidebar --}}
    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-[min(272px,86vw)] flex-col border-r border-white/10 bg-ss-sidebar transition-transform duration-300 lg:static lg:z-auto lg:shrink-0 lg:transition-[width] lg:duration-300"
        :class="[
            mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            sidebarOpen ? 'lg:w-[272px]' : 'lg:w-[84px]'
        ]"
    >
        <div class="flex h-16 items-center gap-3 border-b border-white/10 px-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-ss-primary to-[#7C3AED] text-sm font-bold text-white">SS</div>
            <div class="min-w-0 flex-1" x-show="sidebarOpen || !isDesktop" x-cloak>
                <div class="truncate text-sm font-bold tracking-wide">SmartStock</div>
                <div class="truncate text-[11px] text-ss-muted">Inventory & Logistics ERP</div>
            </div>
            <button type="button" class="rounded-lg p-2 text-ss-muted hover:bg-white/5 hover:text-ss-text lg:hidden" @click="mobileOpen = false" aria-label="Close menu">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto overscroll-contain px-3 py-4">
            @include('layouts.partials.sidebar-nav')
        </nav>

        <div class="hidden border-t border-white/10 p-3 lg:block">
            <button type="button" class="ss-nav-item w-full justify-center lg:justify-start" @click="sidebarOpen = !sidebarOpen" :title="sidebarOpen ? 'Collapse' : 'Expand'">
                <i data-lucide="panel-left-close" class="h-4 w-4 shrink-0" x-show="sidebarOpen"></i>
                <i data-lucide="panel-left-open" class="h-4 w-4 shrink-0" x-show="!sidebarOpen" x-cloak></i>
                <span x-show="sidebarOpen" x-cloak>Collapse</span>
            </button>
        </div>
    </aside>

    <div class="flex min-w-0 w-full flex-1 flex-col overflow-x-hidden">
        {{-- Topbar --}}
        <header class="sticky top-0 z-30 flex min-h-16 flex-wrap items-center justify-between gap-3 border-b border-white/10 bg-ss-bg/95 px-3 py-3 backdrop-blur sm:px-4 md:px-6">
            <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                <button type="button" class="ss-btn-secondary shrink-0 px-3 lg:hidden" @click="mobileOpen = true" aria-label="Open menu">
                    <i data-lucide="menu" class="h-4 w-4"></i>
                </button>
                <div class="min-w-0">
                    <div class="truncate text-[11px] text-ss-muted sm:text-xs">
                        @hasSection('breadcrumb')
                            @yield('breadcrumb')
                        @else
                            SmartStock / @yield('heading', 'Dashboard')
                        @endif
                    </div>
                    <h1 class="truncate text-base font-semibold md:text-lg">@yield('heading', 'Dashboard')</h1>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2 md:gap-3">
                <form action="{{ route('dashboard') }}" method="GET" class="hidden lg:block">
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ss-muted"></i>
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Global search..." class="ss-input w-44 pl-9 xl:w-64">
                    </div>
                </form>

                <div class="relative" @click.outside="notifOpen = false">
                    <button type="button" class="relative rounded-xl border border-white/10 bg-ss-card p-2.5 text-ss-muted hover:text-ss-text" @click="notifOpen = !notifOpen; profileOpen = false" aria-label="Notifications">
                        <i data-lucide="bell" class="h-4 w-4"></i>
                        @if($unread > 0)
                            <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-ss-danger px-1 text-[10px] font-bold text-white">{{ $unread }}</span>
                        @endif
                    </button>
                    <div
                        x-show="notifOpen"
                        x-cloak
                        x-transition
                        class="absolute right-0 z-50 mt-2 w-[min(20rem,calc(100vw-1.5rem))] rounded-2xl border border-white/10 bg-ss-card p-3 shadow-xl"
                    >
                        <div class="mb-2 text-sm font-semibold">Notifications</div>
                        @forelse(($user?->appNotifications()->latest()->take(5)->get() ?? collect()) as $notification)
                            <div class="mb-2 rounded-xl border border-white/10 bg-ss-card-2 px-3 py-2">
                                <div class="text-sm font-medium">{{ $notification->title }}</div>
                                <div class="text-xs text-ss-muted">{{ $notification->message }}</div>
                            </div>
                        @empty
                            <p class="text-sm text-ss-muted">No notifications yet.</p>
                        @endforelse
                        <a href="{{ route('notifications.index') }}" class="mt-2 block text-center text-xs font-semibold text-ss-primary">View all</a>
                    </div>
                </div>

                <div class="relative" @click.outside="profileOpen = false">
                    <button type="button" class="flex max-w-[11rem] items-center gap-2 rounded-xl border border-white/10 bg-ss-card px-2 py-1.5 sm:max-w-none sm:px-2.5" @click="profileOpen = !profileOpen; notifOpen = false">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-ss-primary/20 text-xs font-bold text-ss-primary">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="hidden min-w-0 text-left sm:block">
                            <div class="truncate text-sm font-medium leading-tight">{{ $user->name }}</div>
                            <div class="truncate text-[11px] text-ss-muted">{{ $user->primaryRoleName() }}</div>
                        </div>
                        <i data-lucide="chevron-down" class="hidden h-4 w-4 shrink-0 text-ss-muted sm:block"></i>
                    </button>
                    <div
                        x-show="profileOpen"
                        x-cloak
                        x-transition
                        class="absolute right-0 z-50 mt-2 w-52 rounded-2xl border border-white/10 bg-ss-card p-2 shadow-xl"
                    >
                        <a href="{{ route('profile.edit') }}" class="ss-nav-item">Profile</a>
                        <a href="{{ route('profile.password') }}" class="ss-nav-item">Change password</a>
                        <a href="{{ route('settings.index') }}" class="ss-nav-item">Settings</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="ss-nav-item w-full text-ss-danger">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full min-w-0 max-w-[1600px] flex-1 px-3 py-4 sm:px-4 sm:py-6 md:px-6">
            @hasSection('subheading')
                <p class="mb-4 text-sm text-ss-muted">@yield('subheading')</p>
            @endif

            @if(session('success'))
                <div class="mb-4 rounded-xl border border-ss-success/30 bg-ss-success/10 px-4 py-3 text-sm text-green-200">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="mb-4 rounded-xl border border-ss-danger/30 bg-ss-danger/10 px-4 py-3 text-sm text-red-200">
                    <ul class="list-disc space-y-1 pl-4">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="min-w-0 w-full">
                @yield('content')
            </div>
        </main>
    </div>
</div>

<style>[x-cloak]{display:none!important}</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
@stack('scripts')
</body>
</html>
