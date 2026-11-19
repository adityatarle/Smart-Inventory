<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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
            theme: {
                extend: {
                    fontFamily: { sans: ['Manrope', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        'ss-bg': '#171529',
                        'ss-sidebar': '#11101F',
                        'ss-card': '#1C1B2E',
                        'ss-card-2': '#242238',
                        'ss-primary': '#A855F7',
                        'ss-secondary': '#D6A85B',
                        'ss-success': '#22C55E',
                        'ss-warning': '#F59E0B',
                        'ss-danger': '#EF4444',
                        'ss-text': '#F8FAFC',
                        'ss-muted': '#A1A1AA',
                    },
                },
            },
        };
    </script>
    <style type="text/tailwindcss">
        @layer base {
            html { @apply overflow-x-hidden; }
            body { @apply bg-ss-bg font-sans text-ss-text antialiased overflow-x-hidden; min-height: 100vh; }
            img, canvas, svg, video { max-width: 100%; }
        }
        @layer components {
            .ss-card { @apply rounded-2xl border border-white/10 bg-ss-card shadow-lg shadow-black/20; }
            .ss-card-2 { @apply rounded-2xl border border-white/10 bg-ss-card-2; }
            .ss-btn { @apply inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-semibold transition; }
            .ss-btn-primary { @apply ss-btn bg-ss-primary text-white hover:bg-purple-400; }
            .ss-btn-secondary { @apply ss-btn border border-white/10 bg-ss-card-2 text-ss-text hover:border-ss-primary/40; }
            .ss-btn-danger { @apply ss-btn bg-ss-danger text-white hover:bg-red-400; }
            .ss-page-toolbar { @apply mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between; }
            .ss-page-toolbar form { @apply flex w-full flex-col gap-2 sm:max-w-xl sm:flex-row; }
            .ss-chart-box { @apply relative h-52 w-full max-w-full sm:h-56; }
            .ss-input { @apply w-full rounded-xl border border-white/10 bg-[#12111F] px-3 py-2.5 text-sm text-ss-text outline-none placeholder:text-ss-muted focus:border-ss-primary/50 focus:ring focus:ring-ss-primary/30; }
            .ss-label { @apply mb-1.5 block text-xs font-semibold uppercase tracking-wide text-ss-muted; }
            .ss-badge { @apply inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold; }
            .ss-nav-item { @apply flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-ss-muted transition hover:bg-white/5 hover:text-ss-text; }
            .ss-nav-item-active { @apply bg-ss-primary/15 text-ss-primary ring-1 ring-ss-primary/30; }
            .ss-table-wrap { @apply w-full max-w-full overflow-x-auto overscroll-x-contain rounded-2xl border border-white/10; }
            table.ss-data { @apply w-full min-w-[640px] divide-y divide-white/10 text-sm; }
            table.ss-data thead { @apply bg-ss-card-2/80 text-left text-xs uppercase tracking-wide text-ss-muted; }
            table.ss-data th, table.ss-data td { @apply whitespace-nowrap px-3 py-3 sm:px-4; }
            table.ss-data tbody { @apply divide-y divide-white/10 bg-ss-card/60; }
            .card { @apply ss-card; }
            .btn-primary { @apply ss-btn-primary; }
            .btn-secondary { @apply ss-btn-secondary; }
            .btn-danger { @apply ss-btn-danger; }
            .input { @apply ss-input; }
            .label { @apply ss-label; }
            .badge { @apply ss-badge; }
            .nav-link { @apply ss-nav-item; }
            .nav-link-active { @apply ss-nav-item-active; }
            .table-wrap { @apply ss-table-wrap; }
            table.data { @apply ss-data; }
            table.data thead { @apply bg-ss-card-2/80 text-left text-xs uppercase tracking-wide text-ss-muted; }
            table.data th, table.data td { @apply whitespace-nowrap px-3 py-3 sm:px-4; }
            table.data tbody { @apply divide-y divide-white/10 bg-ss-card/60; }
            .text-muted { @apply text-ss-muted; }
            .border-line { border-color: rgba(255,255,255,0.08); }
            .bg-panel { @apply bg-ss-card; }
            .bg-panel-2 { @apply bg-ss-card-2; }
            .nav-toggle { @apply flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-ss-text transition hover:bg-white/5; }
            .nav-toggle-active { @apply bg-white/5 text-ss-primary; }
            .nav-chevron { @apply h-4 w-4 text-ss-muted transition-transform duration-200; }
            .nav-submenu { @apply ml-2 space-y-1 border-l border-white/10 pl-2; }
            .nav-group { @apply space-y-1; }
        }
    </style>
@endif

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
