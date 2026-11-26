@extends('layouts.app')

@php
    $seoCat = $category;
    $seoCanonical = $seoCat ? route('products.index', ['category' => $seoCat->slug]) : route('products.index');
    $seoCopy = $seoCat ? config('seo.categories.'.$seoCat->slug) : null;
    $seoH1 = $seoCopy['h1'] ?? ($seoCat ? 'Pronájem: '.$seoCat->name : 'Pronájem zvukové a světelné techniky');
    $isSub = $seoCat && $seoCat->parent_id && $section;
@endphp
@section('title', $seoCopy['title'] ?? ($seoCat ? ('Pronájem: ' . $seoCat->name . ' — Maraka rental Olomouc') : 'Pronájem zvukové a světelné techniky Olomouc — katalog | Maraka rental'))
@section('meta_description', $seoCopy['meta_description'] ?? ($seoCat
    ? ('Pronájem: ' . $seoCat->name . ' pro akce v Olomouci a okolí. Vyberte termín a rezervujte online z ' . \App\Models\Product::inCategory($seoCat)->available()->count() . ' položek.')
    : 'Kompletní katalog zvukové a světelné techniky k zapůjčení — reproboxy, mikrofony, mixpulty, světla i kabeláž. Olomouc a okolí.'))
@section('canonical', $seoCanonical)

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <div class="kicker">
                <a href="{{ route('products.index') }}" class="hover:underline">Katalog</a>
                @if($isSub)
                    <span aria-hidden="true">›</span>
                    <a href="{{ route('products.index', ['category' => $section->slug]) }}" class="hover:underline">{{ $section->name }}</a>
                @endif
            </div>
            <h1 class="mt-2 text-display-md font-extrabold text-ink">{{ $seoH1 }}</h1>
            @if(!empty($seoCopy['intro']))
                <p class="mt-3 max-w-2xl leading-relaxed text-muted">{{ $seoCopy['intro'] }}</p>
            @endif
        </div>
        <p class="text-sm text-muted">
            {{ $products->total() }} položek
            @if($termStart && $termEnd)
                · termín {{ \Carbon\Carbon::parse($termStart)->format('d.m.Y') }} → {{ \Carbon\Carbon::parse($termEnd)->format('d.m.Y') }}
            @endif
        </p>
    </div>

    {{-- Rental term --}}
    <div class="mt-5">
        @include('partials.term', ['variant' => 'bar', 'start' => $termStart, 'end' => $termEnd, 'submitLabel' => 'Uložit termín'])
    </div>

    {{-- Sections, then the chosen section's subcategories --}}
    <nav class="mt-6" aria-label="Kategorie techniky">
        <div class="flex gap-1 overflow-x-auto border-b border-line">
            <a href="{{ route('products.index') }}" @class(['tab', 'tab-on' => ! $category]) aria-current="{{ $category ? 'false' : 'page' }}">Vše</a>
            @foreach($menu as $s)
                @php($on = $section?->id === $s->id)
                <a href="{{ route('products.index', ['category' => $s->slug]) }}" @class(['tab', 'tab-on' => $on]) aria-current="{{ $category?->id === $s->id ? 'page' : ($on ? 'true' : 'false') }}">
                    {{ $s->name }} <span class="tab-count">{{ $s->available_count }}</span>
                </a>
            @endforeach
        </div>
        @if($section && $section->children->isNotEmpty())
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('products.index', ['category' => $section->slug]) }}" @class(['chip', 'chip-on' => $category->id === $section->id]) aria-current="{{ $category->id === $section->id ? 'page' : 'false' }}">Vše</a>
                @foreach($section->children as $child)
                    @php($on = $category->id === $child->id)
                    <a href="{{ route('products.index', ['category' => $child->slug]) }}" @class(['chip', 'chip-on' => $on]) aria-current="{{ $on ? 'page' : 'false' }}">{{ $child->name }}</a>
                @endforeach
            </div>
        @endif
    </nav>

    {{-- Search and sort within the chosen category --}}
    <form id="products-filter-form" method="get" action="{{ route('products.index') }}" class="mt-6">
        @if($category)<input type="hidden" name="category" value="{{ $category->slug }}">@endif
        @foreach($selectedTags ?? [] as $tagId)<input type="hidden" name="tags[]" value="{{ $tagId }}">@endforeach
        @if($termStart)<input type="hidden" name="start_date" value="{{ \Carbon\Carbon::parse($termStart)->format('Y-m-d') }}">@endif
        @if($termEnd)<input type="hidden" name="end_date" value="{{ \Carbon\Carbon::parse($termEnd)->format('Y-m-d') }}">@endif
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="{{ $category ? 'Hledat v kategorii '.$category->name.'…' : 'Hledat techniku…' }}" class="field pl-9" autocomplete="off" aria-label="Hledat">
            </div>
            <select name="sort" class="field sm:w-48" aria-label="Řazení">
                <option value="featured" @selected(($sort ?? 'featured') === 'featured')>Doporučené</option>
                <option value="price_asc" @selected($sort === 'price_asc')>Nejlevnější</option>
                <option value="price_desc" @selected($sort === 'price_desc')>Nejdražší</option>
                <option value="name_asc" @selected($sort === 'name_asc')>Název A→Z</option>
                <option value="newest" @selected($sort === 'newest')>Nejnovější</option>
            </select>
        </div>
        <noscript><button type="submit" class="btn-primary mt-4">Filtrovat</button></noscript>
    </form>

    <div id="products-loading" class="mt-6 hidden text-sm text-faint">Načítám…</div>

    <div id="products-list" class="mt-8">
        @include('products._list', ['products' => $products])
    </div>

    @include('partials.enquiry-form', ['class' => 'mt-16'])

    <script>
        (function () {
            const form = document.getElementById('products-filter-form');
            const list = document.getElementById('products-list');
            const loading = document.getElementById('products-loading');
            if (!form || !list) return;

            let timer = null;
            function debounce(fn, d) { return function () { clearTimeout(timer); timer = setTimeout(fn, d); }; }

            async function fetchList(url) {
                loading.classList.remove('hidden');
                list.style.opacity = '0.5';
                try {
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    list.innerHTML = await res.text();
                } catch (e) { /* keep current */ }
                loading.classList.add('hidden');
                list.style.opacity = '1';
            }

            function update() {
                const params = new URLSearchParams(new FormData(form));
                const url = form.action + '?' + params.toString();
                if (window.history.pushState) window.history.pushState({}, '', url);
                fetchList(url);
            }

            form.addEventListener('submit', function (e) { e.preventDefault(); update(); });
            form.querySelectorAll('input[name="q"]').forEach(el => el.addEventListener('input', debounce(update, 350)));
            form.querySelectorAll('select').forEach(el => el.addEventListener('change', update));

            // AJAX pagination
            list.addEventListener('click', function (e) {
                const a = e.target.closest('nav a[href], .pagination a[href]');
                if (!a || e.metaKey || e.ctrlKey || e.shiftKey) return;
                e.preventDefault();
                const href = a.getAttribute('href');
                if (window.history.pushState) window.history.pushState({}, '', href);
                fetchList(href);
                window.scrollTo({ top: list.offsetTop - 90, behavior: 'smooth' });
            });

            window.addEventListener('popstate', function () { fetchList(location.href); });
        })();
    </script>
@endsection
