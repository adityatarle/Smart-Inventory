<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SmartStock ERP')</title>
    @include('partials.assets')
</head>
<body>
    <div class="flex min-h-screen items-center justify-center px-4 py-10"
         style="background:
            radial-gradient(ellipse 70% 50% at 15% -10%, rgba(168,85,247,0.18), transparent 55%),
            radial-gradient(ellipse 50% 40% at 90% 0%, rgba(214,168,91,0.10), transparent 50%),
            #171529;">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-ss-primary to-[#7C3AED] text-lg font-bold text-white">SS</div>
                <h1 class="text-2xl font-bold">SmartStock</h1>
                <p class="mt-1 text-sm text-ss-muted">@yield('subheading', 'Sign in to continue')</p>
            </div>
            <div class="ss-card p-6">
                @if(session('status'))
                    <div class="mb-4 rounded-xl border border-ss-success/30 bg-ss-success/10 px-4 py-3 text-sm text-green-200">{{ session('status') }}</div>
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
                @yield('content')
            </div>
        </div>
    </div>
    <script>document.addEventListener('DOMContentLoaded', () => window.lucide && window.lucide.createIcons());</script>
</body>
</html>
