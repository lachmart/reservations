<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#151310">
    <title>@yield('title', 'Maraka rental — pronájem zvukové a světelné techniky')</title>
    <meta name="description" content="@yield('meta_description', 'Půjčovna profesionální zvukové a světelné techniky pro akce až do 600 osob. Vyberte termín, sestavte si vybavení a rezervujte online.')">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Hanken+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css','resources/js/app.js'])

    <script>
        (function () {
            const stored = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) document.documentElement.classList.add('dark');
        })();
        function toggleTheme() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }
    </script>
    <script>
        (function () {
            if (window.axios) return;
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/axios@1.7.7/dist/axios.min.js';
            s.async = true;
            s.onload = function () {
                const token = document.head.querySelector('meta[name="csrf-token"]');
                if (window.axios) {
                    window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
                    if (token) window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
                }
            };
            document.head.appendChild(s);
        })();
        window.showToast = function (message, isError = false) {
            let box = document.getElementById('toast-box');
            if (!box) {
                box = document.createElement('div');
                box.id = 'toast-box';
                box.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem';
                document.body.appendChild(box);
            }
            const t = document.createElement('div');
            t.textContent = message;
            t.style.cssText = 'border-radius:.6rem;padding:.7rem 1rem;font-size:.875rem;font-weight:600;box-shadow:0 10px 30px -12px rgba(0,0,0,.4);color:var(--accent-ink);' +
                'background:' + (isError ? 'oklch(0.72 0.15 30)' : 'var(--accent)') + ';transform:translateY(-6px);opacity:0;transition:all .25s cubic-bezier(0.25,1,0.5,1)';
            box.appendChild(t);
            requestAnimationFrame(() => { t.style.transform = 'translateY(0)'; t.style.opacity = '1'; });
            setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateY(-6px)'; setTimeout(() => t.remove(), 250); }, 2800);
        };
    </script>
</head>
<body class="flex min-h-full flex-col">
    <header class="sticky top-0 z-40 border-b border-line bg-paper/95 backdrop-blur" style="background:color-mix(in oklab, var(--paper) 88%, transparent)">
        <div class="wrap flex h-16 items-center justify-between">
            <a href="/" class="group flex items-baseline gap-2" aria-label="Maraka rental">
                <span class="font-display text-xl font-extrabold tracking-tight text-ink">MARAKA</span>
                <span class="rounded bg-accent px-1.5 py-0.5 font-display text-[0.7rem] font-bold uppercase tracking-widest text-accent-ink">rental</span>
            </a>
            <nav class="flex items-center gap-1">
                <a href="/" class="nav-link hidden sm:inline-flex">Domů</a>
                <a href="{{ route('products.index') }}" class="nav-link">Technika</a>
                @php($__cart = session('cart', []))
                @php($__cartCount = array_sum((array) ($__cart['items'] ?? [])))
                <a href="{{ route('cart.index') }}" id="nav-cart-link" class="btn-secondary ml-1 py-2">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    <span class="hidden sm:inline">Košík</span>
                    <span data-cart-count class="inline-flex min-w-5 items-center justify-center rounded-full bg-accent px-1.5 text-xs font-bold text-accent-ink {{ $__cartCount > 0 ? '' : 'hidden' }}">{{ $__cartCount }}</span>
                </a>
            </nav>
        </div>
    </header>

    <main class="flex-1">
        @hasSection('full')
            @yield('full')
        @else
            <div class="wrap py-8 sm:py-12">
                @if($errors->any())
                    <div class="mb-6 rounded-lg border border-line bg-surface p-4 text-sm" style="color:oklch(0.55 0.16 30)">
                        <ul class="list-inside list-disc">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </div>
        @endif
    </main>

    @if(session('success'))
        <script>window.showToast && window.showToast(@json(session('success')), false);</script>
    @endif

    <footer class="mt-16 border-t border-line">
        @php($phone = config('contact.phone'))
        @php($phoneHref = preg_replace('/[^0-9+]/', '', $phone))
        @php($email = config('contact.email'))
        <div class="wrap grid gap-8 py-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2 lg:col-span-1">
                <div class="font-display text-lg font-extrabold text-ink">MARAKA <span class="text-accent">rental</span></div>
                <p class="mt-2 max-w-xs text-sm text-muted">Pronájem profesionální zvukové a světelné techniky pro akce až do 600 osob.</p>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-faint">Kontakt</div>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="tel:{{ $phoneHref }}" class="font-semibold text-ink hover:text-accent-ink">{{ $phone }}</a></li>
                    <li><a href="mailto:{{ $email }}" class="text-muted hover:text-ink">{{ $email }}</a></li>
                </ul>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-faint">Sídlo</div>
                <address class="mt-3 space-y-1 text-sm not-italic text-muted">
                    <div class="text-ink">{{ config('contact.name') }}</div>
                    <div>{{ config('contact.address') }}</div>
                    <div>IČO: {{ config('contact.ico') }}</div>
                </address>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-faint">Web</div>
                <ul class="mt-3 space-y-2 text-sm text-muted">
                    <li><a href="{{ route('products.index') }}" class="hover:text-ink">Technika</a></li>
                    <li><a href="{{ route('cart.index') }}" class="hover:text-ink">Rezervace</a></li>
                    <li>
                        <button type="button" onclick="toggleTheme()" class="inline-flex items-center gap-2 hover:text-ink" aria-label="Přepnout téma">
                            <svg class="size-4 dark:hidden" viewBox="0 0 24 24" fill="currentColor"><path d="M21.64 13.65A9 9 0 1 1 10.35 2.36a7 7 0 1 0 11.29 11.29Z"/></svg>
                            <svg class="hidden size-4 dark:block" viewBox="0 0 24 24" fill="currentColor"><path d="M12 5a1 1 0 0 0 1-1V1h-2v3a1 1 0 0 0 1 1Zm0 14a1 1 0 0 0-1 1v3h2v-3a1 1 0 0 0-1-1Zm7-7a1 1 0 0 0 1-1h3V8h-3a1 1 0 0 0-1 1Zm-14 0a1 1 0 0 0-1-1H1v2h3a1 1 0 0 0 1-1Zm10.95-7.05 1.82-1.82-1.41-1.41-1.82 1.82 1.41 1.41ZM6.76 19.16l-1.82 1.82 1.41 1.41 1.82-1.82-1.41-1.41ZM18 12a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/></svg>
                            <span>Přepnout téma</span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
        <div class="wrap pb-6 text-xs text-faint">&copy; {{ date('Y') }} {{ config('contact.name') }} · Maraka rental</div>
    </footer>
</body>
</html>
