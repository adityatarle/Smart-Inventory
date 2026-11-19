<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart Inventory')</title>
    @include('partials.assets')
</head>
<body>
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-400 to-sky-500 text-lg font-bold text-slate-950">SI</div>
                <h1 class="text-2xl font-bold">Smart Inventory</h1>
                <p class="mt-1 text-sm text-muted">@yield('subheading', 'Sign in to continue')</p>
            </div>
            <div class="card p-6">
                @if($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-200">
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
</body>
</html>
