<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Rezervace')</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <script>
        // Initialize theme early to avoid FOUC
        (function() {
            const stored = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if ((stored === 'dark') || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
        function toggleTheme(){
            const el = document.documentElement;
            const isDark = el.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
    </script>
</head>
<body class="h-full min-h-screen bg-slate-50 text-slate-900 dark:bg-gradient-to-br dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 dark:text-slate-200">
    <div class="relative min-h-screen">
        <div aria-hidden="true" class="pointer-events-none fixed inset-0 opacity-30 dark:opacity-60">
            <div class="absolute -top-24 -left-24 size-72 rounded-full bg-indigo-500 blur-3xl"></div>
            <div class="absolute top-1/2 -right-24 size-72 rounded-full bg-fuchsia-500 blur-3xl"></div>
        </div>

        <header class="sticky top-0 z-40 backdrop-blur supports-[backdrop-filter]:bg-white/60 bg-white/80 dark:bg-slate-900/60 border-b border-slate-200/60 dark:border-slate-700/50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="/" class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100">
                    <span>Rezervace</span>
                </a>
                <nav class="flex items-center gap-1 sm:gap-2">
                    <a href="/" class="nav-link">Domů</a>
                    <a href="{{ route('products.index') }}" class="nav-link">Produkty</a>
                    @php($__cart = session('cart', []))
                    @php($__cartCount = array_sum((array)($__cart['items'] ?? [])))
                    <a href="{{ route('cart.index') }}" class="nav-link inline-flex items-center gap-2" id="nav-cart-link">
                        <span>Košík</span>
                        @if($__cartCount > 0)
                            <span class="ml-1 inline-flex items-center justify-center rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-semibold text-white" data-cart-count>
                                {{ $__cartCount }}
                            </span>
                        @endif
                    </a>
                </nav>
            </div>
        </header>

        <main class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
            @if(session('success'))
                <div class="mb-4 rounded border border-green-300/60 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-700/60 dark:bg-green-900/30 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 rounded border border-red-300/60 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-700/60 dark:bg-red-900/30 dark:text-red-200">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </main>

        <footer class="mt-8 border-t border-slate-200/60 dark:border-slate-700/50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 text-xs text-slate-600 dark:text-slate-400 flex items-center justify-between">
                <span>&copy; {{ date('Y') }} Rezervace</span>
                <div class="flex items-center gap-2">
                    <span class="hidden sm:block">Vytvořeno pomocí Laravelu a Tailwindu</span>
                    <button type="button" onclick="toggleTheme()" class="ml-2 inline-flex items-center gap-2 rounded-md border border-slate-300/60 dark:border-slate-700/60 bg-white/60 dark:bg-slate-800/60 px-3 py-1.5 text-sm text-slate-700 dark:text-slate-200 hover:bg-white/90 hover:dark:bg-slate-800/80 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                        <svg class="size-4 hidden dark:block" viewBox="0 0 24 24" fill="currentColor"><path d="M21.64 13.65A9 9 0 1 1 10.35 2.36a7 7 0 1 0 11.29 11.29Z"/></svg>
                        <svg class="size-4 dark:hidden" viewBox="0 0 24 24" fill="currentColor"><path d="M6.76 4.84 4.95 3.03 3.54 4.44l1.82 1.82 1.4-1.41Zm10.48 14.32 1.82 1.82 1.41-1.41-1.82-1.82-1.41 1.41ZM12 5a1 1 0 0 0 1-1V1h-2v3a1 1 0 0 0 1 1Zm0 14a1 1 0 0 0-1 1v3h2v-3a1 1 0 0 0-1-1Zm7-7a1 1 0 0 0 1-1h3V8h-3a1 1 0 0 0-1 1Zm-14 0a1 1 0 0 0-1-1H1v2h3a1 1 0 0 0 1-1Zm10.95-7.05 1.82-1.82-1.41-1.41-1.82 1.82 1.41 1.41ZM6.76 19.16l-1.82 1.82 1.41 1.41 1.82-1.82-1.41-1.41ZM18 12a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/></svg>
                        <span class="hidden sm:inline">Téma</span>
                    </button>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
