<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">

@php
    $viteHot = public_path('hot');
    $viteManifest = public_path('build/manifest.json');
    $useVite = file_exists($viteHot) || file_exists($viteManifest);
@endphp

@if($useVite)
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@else
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['DM Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        surface: '#0b1220',
                        panel: '#121a2b',
                        'panel-2': '#182338',
                        line: '#243049',
                        ink: '#e8eefc',
                        muted: '#93a0b8',
                        accent: '#2dd4bf',
                    },
                },
            },
        };
    </script>
    <style type="text/tailwindcss">
        @layer base {
            body {
                @apply bg-surface text-ink antialiased font-sans;
                background-image:
                    radial-gradient(ellipse 80% 50% at 10% -10%, rgba(45, 212, 191, 0.12), transparent 55%),
                    radial-gradient(ellipse 60% 40% at 90% 0%, rgba(56, 189, 248, 0.10), transparent 50%),
                    linear-gradient(180deg, #0b1220 0%, #0a101a 100%);
                min-height: 100vh;
            }
        }
        @layer components {
            .card { @apply rounded-2xl border border-line bg-panel/90 shadow-xl shadow-black/20; }
            .btn { @apply inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition; }
            .btn-primary { @apply inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition bg-teal-400 text-slate-950 hover:bg-teal-300; }
            .btn-secondary { @apply inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition border border-line bg-panel-2 text-ink hover:border-teal-400/40 hover:bg-slate-800/60; }
            .btn-danger { @apply inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition bg-rose-500/90 text-white hover:bg-rose-400; }
            .input { @apply w-full rounded-xl border border-line bg-slate-950/50 px-3 py-2.5 text-sm text-ink outline-none ring-teal-400/30 placeholder:text-muted focus:border-teal-400/50 focus:ring; }
            .label { @apply mb-1.5 block text-xs font-semibold uppercase tracking-wide text-muted; }
            .badge { @apply inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold; }
            .nav-link { @apply flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-muted transition hover:bg-panel-2 hover:text-ink; }
            .nav-link-active { @apply bg-teal-400/10 text-teal-300 ring-1 ring-teal-400/30; }
            .table-wrap { @apply overflow-x-auto rounded-2xl border border-line; }
            table.data { @apply min-w-full divide-y divide-line text-sm; }
            table.data thead { @apply bg-panel-2/80 text-left text-xs uppercase tracking-wide text-muted; }
            table.data th, table.data td { @apply px-4 py-3; }
            table.data tbody { @apply divide-y divide-line bg-panel/60; }
        }
    </style>
@endif
