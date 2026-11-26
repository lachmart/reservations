<!DOCTYPE html>
{{-- The public site is Czech only; don't follow APP_LOCALE (en on the server). --}}
<html lang="cs" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f8fbfa" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0e1715" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="/favicon.ico" sizes="48x48">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16x16.png">
    <link rel="apple-touch-icon" href="/images/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    {{-- Machine-readable entry points for AI assistants and agents --}}
    <link rel="alternate" type="text/plain" href="/llms.txt" title="llms.txt">
    <link rel="service-desc" type="application/json" href="{{ url('/mcp/server-card.json') }}" title="MCP server card">
    @php
        // @section('title', $value) escapes the value already; decode it so the
        // {{ }} below doesn't escape it twice ("Allen&amp;amp;Heath").
        $seoTitle = html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?: 'Maraka rental — půjčovna ozvučení a světel Olomouc';
        $seoDesc = html_entity_decode(trim($__env->yieldContent('meta_description')), ENT_QUOTES | ENT_HTML5, 'UTF-8') ?: 'Půjčovna profesionální zvukové a světelné techniky pro akce až do 600 osob v Olomouci a okolí. Vyberte termín, sestavte si vybavení a rezervujte online.';
        $seoCanonical = trim($__env->yieldContent('canonical')) ?: request()->url();
        $seoImage = trim($__env->yieldContent('og_image'));
        $seoType = trim($__env->yieldContent('og_type')) ?: 'website';
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}">
    <link rel="canonical" href="{{ $seoCanonical }}">

    {{-- Open Graph / social sharing --}}
    <meta property="og:site_name" content="Maraka rental">
    <meta property="og:locale" content="cs_CZ">
    <meta property="og:type" content="{{ $seoType }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    @if($seoImage)
        <meta property="og:image" content="{{ $seoImage }}">
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    @if($seoImage)<meta name="twitter:image" content="{{ $seoImage }}">@endif
    @hasSection('gsc_verification')<meta name="google-site-verification" content="@yield('gsc_verification')">@endif

    {{-- LocalBusiness structured data (sitewide) --}}
    @php
        $bizPhone = preg_replace('/[^0-9+]/', '', config('contact.phone'));
    @endphp
    <script type="application/ld+json">
    {!! json_encode([
        '@@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        '@id' => config('app.url').'/#business',
        'name' => 'Maraka rental',
        'description' => 'Půjčovna profesionální zvukové a světelné techniky pro akce až do 600 osob.',
        'url' => config('app.url'),
        'telephone' => $bizPhone,
        'email' => config('contact.email'),
        'priceRange' => '$$',
        'founder' => ['@type' => 'Person', 'name' => config('contact.name')],
        'identifier' => ['@type' => 'PropertyValue', 'name' => 'IČO', 'value' => config('contact.ico')],
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'Černá cesta 96/31',
            'addressLocality' => 'Olomouc',
            'postalCode' => '779 00',
            'addressCountry' => 'CZ',
        ],
        'areaServed' => collect(config('seo.area_served'))->map(fn ($place) => [
            '@type' => str_contains($place, 'kraj') ? 'AdministrativeArea' : 'City',
            'name' => $place,
        ])->all(),
        'knowsAbout' => ['pronájem ozvučení', 'pronájem osvětlení', 'pronájem světel', 'ozvučení svateb', 'ozvučení koncertů', 'pódiová technika'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @stack('schema')

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
                // Below the sticky header and click-through, so it never covers the cart button.
                box.style.cssText = 'position:fixed;top:4.75rem;right:1rem;left:auto;max-width:calc(100vw - 2rem);z-index:9999;display:flex;flex-direction:column;gap:.5rem;pointer-events:none';
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
            <a href="/" class="flex items-center" aria-label="Maraka rental – domů">
                <img src="/images/logo.png" alt="Maraka rental" width="569" height="128" class="h-9 w-auto dark:hidden">
                <img src="/images/logo-dark.png" alt="Maraka rental" width="569" height="128" class="hidden h-9 w-auto dark:block">
            </a>
            <nav class="flex items-center gap-1 self-stretch">
                <a href="/" class="nav-link hidden sm:inline-flex">Domů</a>
                <div class="flex items-center self-stretch" data-mega-trigger>
                    <a href="{{ route('products.index') }}" @class(['nav-link', 'pr-1' => $__menu->isNotEmpty()])>Technika</a>
                    @if($__menu->isNotEmpty())
                        <button type="button" class="nav-link px-1.5" aria-expanded="false" aria-controls="mega-menu" data-mega-toggle>
                            <span class="sr-only">Kategorie techniky</span>
                            <svg class="size-4 transition-transform duration-200" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </button>
                    @endif
                </div>
                <a href="{{ route('home') }}#poptavka" class="nav-link hidden sm:inline-flex">Poptávka</a>
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', config('contact.phone')) }}" class="btn-primary ml-1 py-2" aria-label="Zavolat {{ config('contact.phone') }}">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92Z"/></svg>
                    <span class="hidden lg:inline">{{ config('contact.phone') }}</span>
                    <span class="lg:hidden">Zavolat</span>
                </a>
                @php($__cart = session('cart', []))
                @php($__cartCount = \App\Support\CartCount::of((array) $__cart))
                <a href="{{ route('cart.index') }}" id="nav-cart-link" class="btn-secondary ml-1 py-2" aria-label="Košík{{ $__cartCount > 0 ? ' ('.$__cartCount.' '.($__cartCount === 1 ? 'položka' : ($__cartCount < 5 ? 'položky' : 'položek')).')' : '' }}">
                    <svg class="size-4" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    <span class="hidden sm:inline">Košík</span>
                    <span data-cart-count class="inline-flex min-w-5 items-center justify-center rounded-full bg-accent px-1.5 text-xs font-bold text-accent-ink {{ $__cartCount > 0 ? '' : 'hidden' }}">{{ $__cartCount }}</span>
                </a>
            </nav>
        </div>

        {{-- Category menu: the sets, then sections with their subcategories, then the rest --}}
        @if($__menu->isNotEmpty())
            @php($__sety = $__sets->isNotEmpty() ? $__menu->firstWhere('slug', 'sety') : null)
            @php($__withSubs = $__menu->filter(fn ($s) => $s->children->isNotEmpty()))
            @php($__plain = $__menu->filter(fn ($s) => $s->children->isEmpty() && $s !== $__sety))
            @php($__cols = ($__sety ? 1 : 0) + $__withSubs->sum(fn ($s) => $s->children->count() > 6 ? 2 : 1) + ($__plain->isNotEmpty() ? 1 : 0))
            <div id="mega-menu" class="absolute inset-x-0 top-full hidden max-h-[calc(100dvh-4rem)] overflow-y-auto border-b border-line bg-paper shadow-lift" data-mega>
                <div @class(['wrap grid grid-cols-2 gap-x-8 gap-y-6 py-6 md:grid-cols-4', 'lg:grid-cols-5' => $__cols >= 5])>
                    @if($__sety)
                        <div>
                            <a href="{{ route('products.index', ['category' => $__sety->slug]) }}" class="font-display text-base font-extrabold text-ink hover:text-accent-ink">{{ $__sety->name }}</a>
                            <ul class="mt-2 space-y-1">
                                @foreach($__sets as $__p)
                                    <li>
                                        <a href="{{ route('products.show', $__p) }}" class="flex items-baseline justify-between gap-3 py-0.5 text-sm text-muted hover:text-ink">
                                            <span>{{ \Illuminate\Support\Str::of($__p->name)->before(' (')->replaceStart('Set ', '') }}</span>
                                            <span class="shrink-0 tabular-nums text-faint">{{ number_format((float) $__p->price_per_day, 0, ',', ' ') }} Kč</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @foreach($__withSubs as $__s)
                        @php($__wide = $__s->children->count() > 6)
                        <div @class(['md:col-span-2' => $__wide])>
                            <a href="{{ route('products.index', ['category' => $__s->slug]) }}" class="font-display text-base font-extrabold text-ink hover:text-accent-ink">{{ $__s->name }}</a>
                            <ul @class(['mt-2 space-y-1', 'md:columns-2 md:gap-x-8' => $__wide])>
                                @foreach($__s->children as $__c)
                                    <li><a href="{{ route('products.index', ['category' => $__c->slug]) }}" class="block py-0.5 text-sm text-muted hover:text-ink">{{ $__c->name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                    @if($__plain->isNotEmpty())
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-faint">{{ $__withSubs->isNotEmpty() ? 'Další' : 'Kategorie' }}</div>
                            <ul class="mt-2 space-y-1.5">
                                @foreach($__plain as $__s)
                                    <li><a href="{{ route('products.index', ['category' => $__s->slug]) }}" class="font-display text-base font-extrabold text-ink hover:text-accent-ink">{{ $__s->name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                <div class="wrap border-t border-line py-3">
                    <a href="{{ route('products.index') }}" class="text-sm font-semibold text-accent-ink hover:underline">Celý katalog →</a>
                </div>
            </div>
            <script>
                (function () {
                    const panel = document.getElementById('mega-menu');
                    const trigger = document.querySelector('[data-mega-trigger]');
                    const toggle = document.querySelector('[data-mega-toggle]');
                    if (!panel || !trigger || !toggle) return;
                    let closeTimer = null;
                    let hoverOpenedAt = 0;
                    const isOpen = () => !panel.classList.contains('hidden');
                    function setOpen(open) {
                        clearTimeout(closeTimer);
                        panel.classList.toggle('hidden', !open);
                        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                        toggle.querySelector('svg').classList.toggle('rotate-180', open);
                    }
                    toggle.addEventListener('click', function () {
                        // The hover that led to this click already opened it: keep it open.
                        if (isOpen() && Date.now() - hoverOpenedAt < 700) return;
                        setOpen(!isOpen());
                    });
                    // Mouse: open on hover, with a short grace period to reach the panel.
                    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
                        [trigger, panel].forEach(function (el) {
                            el.addEventListener('mouseenter', function () {
                                if (!isOpen()) hoverOpenedAt = Date.now();
                                setOpen(true);
                            });
                            el.addEventListener('mouseleave', function () { closeTimer = setTimeout(function () { setOpen(false); }, 200); });
                        });
                    }
                    document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape' && isOpen()) { setOpen(false); toggle.focus(); }
                    });
                    document.addEventListener('click', function (e) {
                        if (isOpen() && !panel.contains(e.target) && !trigger.contains(e.target)) setOpen(false);
                    });
                    panel.addEventListener('focusout', function (e) {
                        if (e.relatedTarget && !panel.contains(e.relatedTarget) && !trigger.contains(e.relatedTarget)) setOpen(false);
                    });
                })();
            </script>
        @endif
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
                <img src="/images/logo.png" alt="Maraka rental" width="569" height="128" loading="lazy" class="h-10 w-auto dark:hidden">
                <img src="/images/logo-dark.png" alt="Maraka rental" width="569" height="128" loading="lazy" class="hidden h-10 w-auto dark:block">
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
                @php($addrParts = array_map('trim', explode(',', config('contact.address'))))
                @php($addrCity = array_pop($addrParts))
                <address class="mt-3 space-y-1 text-sm not-italic text-muted">
                    <div class="text-ink">{{ config('contact.name') }}</div>
                    <div>{{ implode(', ', $addrParts) }},</div>
                    <div>{{ $addrCity }}</div>
                    <div>IČO: {{ config('contact.ico') }}</div>
                    <div class="pt-1">{{ config('contact.hours') }}</div>
                </address>
            </div>
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-faint">Web</div>
                <ul class="mt-3 space-y-2 text-sm text-muted">
                    <li><a href="{{ route('products.index') }}" class="hover:text-ink">Technika</a></li>
                    <li><a href="{{ route('cart.index') }}" class="hover:text-ink">Rezervace</a></li>
                    <li><a href="{{ route('ai.index') }}" class="hover:text-ink">Rezervace přes AI</a></li>
                    @foreach(config('landing.pages', []) as $__slug => $__page)
                        <li><a href="{{ url($__slug) }}" class="hover:text-ink">{{ $__page['nav_label'] }}</a></li>
                    @endforeach
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
