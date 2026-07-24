@extends('layouts.app')

@section('title', 'Technika — Maraka rental')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <div class="kicker">Katalog</div>
            <h1 class="mt-2 text-display-md font-extrabold text-ink">Technika k zapůjčení</h1>
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

    {{-- Filter toolbar --}}
    <form id="products-filter-form" method="get" action="{{ route('products.index') }}" class="mt-6">
        @if($termStart)<input type="hidden" name="start_date" value="{{ \Carbon\Carbon::parse($termStart)->format('Y-m-d') }}">@endif
        @if($termEnd)<input type="hidden" name="end_date" value="{{ \Carbon\Carbon::parse($termEnd)->format('Y-m-d') }}">@endif
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Hledat techniku…" class="field pl-9" autocomplete="off">
            </div>
            <select name="category" class="field sm:w-52" aria-label="Kategorie">
                <option value="">Všechny kategorie</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) $selectedCategory === (int) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="sort" class="field sm:w-48" aria-label="Řazení">
                <option value="featured" @selected(($sort ?? 'featured') === 'featured')>Doporučené</option>
                <option value="price_asc" @selected($sort === 'price_asc')>Nejlevnější</option>
                <option value="price_desc" @selected($sort === 'price_desc')>Nejdražší</option>
                <option value="name_asc" @selected($sort === 'name_asc')>Název A→Z</option>
                <option value="newest" @selected($sort === 'newest')>Nejnovější</option>
            </select>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2">
            <span class="mr-1 text-xs font-semibold uppercase tracking-wide text-faint">Štítky</span>
            @foreach($allTags as $tag)
                <input id="tag-{{ $tag->id }}" type="checkbox" name="tags[]" value="{{ $tag->id }}" @checked(in_array($tag->id, $selectedTags ?? [])) class="peer sr-only">
                <label for="tag-{{ $tag->id }}" class="chip peer-checked:border-ink peer-checked:bg-ink peer-checked:text-paper">{{ $tag->name }}</label>
            @endforeach
        </div>
        <noscript><button type="submit" class="btn-primary mt-4">Filtrovat</button></noscript>
    </form>

    <div id="products-loading" class="mt-6 hidden text-sm text-faint">Načítám…</div>

    <div id="products-list" class="mt-8">
        @include('products._list', ['products' => $products])
    </div>

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
            form.querySelectorAll('select, input[type="checkbox"]').forEach(el => el.addEventListener('change', update));

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
