@extends('layouts.app')

@section('title', 'Produkty — Rezervace')

@section('content')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Produkty</h1>
        <a href="{{ route('cart.index') }}" class="btn-primary">Přejít do košíku</a>
    </div>

    <form id="products-filter-form" method="get" action="{{ route('products.index') }}" class="card mt-6">
        <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div class="sm:col-span-2">
                <label class="block text-xs text-slate-500">Hledat</label>
                <input type="text" name="q" value="{{ old('q', $q ?? request('q')) }}" placeholder="Hledat název nebo popis..." class="input w-full" />
            </div>

            <div>
                <label class="block text-xs text-slate-500">Kategorie</label>
                <select name="category" class="input w-full">
                    <option value="">— Všechny —</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((int)($selectedCategory) === (int)($category->id))>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs text-slate-500">Řazení</label>
                <select name="sort" class="input w-full">
                    <option value="newest" @selected(($sort ?? 'newest') === 'newest')>Nejnovější</option>
                    <option value="price_asc" @selected(($sort ?? '') === 'price_asc')>Cena ↑</option>
                    <option value="price_desc" @selected(($sort ?? '') === 'price_desc')>Cena ↓</option>
                    <option value="name_asc" @selected(($sort ?? '') === 'name_asc')>Název A→Z</option>
                    <option value="name_desc" @selected(($sort ?? '') === 'name_desc')>Název Z→A</option>
                </select>
            </div>

            <div class="sm:col-span-4">
                <span class="block text-xs text-slate-500 mb-1">Štítky</span>
                <div class="flex flex-wrap gap-2">
                    @foreach($allTags as $tag)
                        <label class="inline-flex items-center gap-2 rounded border border-slate-300/60 dark:border-slate-700/60 px-2 py-1 text-sm">
                            <input type="checkbox" name="tags[]" value="{{ $tag->id }}" @checked(in_array($tag->id, $selectedTags ?? []))>
                            <span>{{ $tag->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="px-6 pb-4">
            <button type="submit" class="btn-secondary">Filtrovat</button>
            @php($hasFilters = (request('q') && request('q') !== '') || request('category') || request('tags') || (request('sort') && request('sort') !== 'newest'))
            @if($hasFilters)
                <a href="{{ route('products.index') }}" class="ml-2 text-sm text-slate-600 underline dark:text-slate-300">Zrušit filtry</a>
            @endif
        </div>
    </form>

    <div id="products-list">
        @include('products._list', ['products' => $products])
    </div>

    <div id="products-loading" class="hidden mt-6 text-sm text-slate-500 dark:text-slate-400">Načítám…</div>

    <script>
        (function(){
            const form = document.getElementById('products-filter-form');
            const list = document.getElementById('products-list');
            const loading = document.getElementById('products-loading');
            if (!form || !list) return;

            const inputs = form.querySelectorAll('input[name="q"], select[name="category"], select[name="sort"], input[name="tags[]"]');

            function formToQuery(){
                const data = new FormData(form);
                // Ensure unchecked checkboxes handled by FormData; it's fine
                const params = new URLSearchParams();
                for (const [k,v] of data.entries()) { params.append(k,v); }
                return params.toString();
            }

            let debounceTimer = null;
            function debounce(fn, delay){
                return function(...args){
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(()=>fn.apply(this,args), delay);
                }
            }

            async function fetchList(url){
                try {
                    loading && loading.classList.remove('hidden');
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const html = await res.text();
                    list.innerHTML = html;
                    bindPaginationLinks();
                    bindAddToCart();
                } catch (e) {
                    // non-fatal; leave as-is
                } finally {
                    loading && loading.classList.add('hidden');
                }
            }

            function update(push=true){
                const base = form.getAttribute('action');
                const qs = formToQuery();
                const url = qs ? `${base}?${qs}` : base;
                if (push && window.history && window.history.pushState) {
                    window.history.pushState({}, '', url);
                }
                fetchList(url);
            }

            // Submit handler (for button)
            form.addEventListener('submit', function(e){
                e.preventDefault();
                update(true);
            });

            // Change handlers
            inputs.forEach(el => {
                const handler = el.name === 'q' ? debounce(()=>update(true), 350) : ()=>update(true);
                const eventName = el.type === 'checkbox' ? 'change' : (el.name === 'q' ? 'input' : 'change');
                el.addEventListener(eventName, handler);
            });

            // Intercept pagination links inside the list
            function updateHeaderCartCount(count){
                const link = document.getElementById('nav-cart-link');
                if (!link) return;
                let badge = link.querySelector('[data-cart-count]');
                if (count > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.setAttribute('data-cart-count','');
                        badge.className = 'ml-1 inline-flex items-center justify-center rounded-full bg-emerald-600 px-2 py-0.5 text-xs font-semibold text-white';
                        link.appendChild(badge);
                    }
                    badge.textContent = String(count);
                } else {
                    if (badge) badge.remove();
                }
            }

            function bindAddToCart(){
                list.querySelectorAll('form.add-to-cart-form').forEach(form => {
                    if (form.__boundAddToCart) return;
                    form.addEventListener('submit', async function(e){
                        e.preventDefault();
                        const productInput = form.querySelector('input[name="product_id"]');
                        const amountInput = form.querySelector('input[name="amount"]');
                        const productId = productInput ? Number(productInput.value) : null;
                        const amount = amountInput ? Number(amountInput.value || '1') : 1;
                        try {
                            const res = await axios.post(form.getAttribute('action'), { product_id: productId, amount });
                            const data = res.data || {};
                            updateHeaderCartCount(Number(data.cart_count || 0));
                            if (productId) {
                                const countEl = document.getElementById('product-count-' + productId);
                                const row = form.closest('[data-product-card]')?.querySelector('[data-in-cart-row]');
                                if (countEl) countEl.textContent = String(data.product_count || 0);
                                if (row) {
                                    if ((data.product_count || 0) > 0) row.classList.remove('hidden');
                                    else row.classList.add('hidden');
                                }
                            }
                            window.showToast && window.showToast(data.message || 'Přidáno do košíku');
                        } catch (err) {
                            window.showToast && window.showToast((err?.response?.data?.message) || 'Nelze přidat do košíku', true);
                        }
                    });
                    form.__boundAddToCart = true;
                });
            }

            function bindPaginationLinks(){
                list.querySelectorAll('a[href]').forEach(a => {
                    if (!a.closest('nav[role="navigation"], .pagination')) return; // limit to paginator
                    a.addEventListener('click', function(e){
                        // allow middle/ctrl clicks
                        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                        e.preventDefault();
                        const href = a.getAttribute('href');
                        if (!href) return;
                        if (window.history && window.history.pushState) {
                            window.history.pushState({}, '', href);
                        }
                        fetchList(href);
                    });
                });
            }

            bindPaginationLinks();
            bindAddToCart();

            // Handle back/forward navigation
            window.addEventListener('popstate', function(){
                fetchList(location.href);
                // Also update form fields to reflect query string
                try {
                    const url = new URL(location.href);
                    const params = url.searchParams;
                    // text
                    const q = form.querySelector('input[name="q"]');
                    if (q) q.value = params.get('q') || '';
                    // selects
                    const cat = form.querySelector('select[name="category"]');
                    if (cat) cat.value = params.get('category') || '';
                    const sort = form.querySelector('select[name="sort"]');
                    if (sort) sort.value = params.get('sort') || 'newest';
                    // checkboxes
                    const tagInputs = form.querySelectorAll('input[name="tags[]"]');
                    const selected = new Set(params.getAll('tags[]').concat(params.getAll('tags')));
                    tagInputs.forEach(cb => { cb.checked = selected.has(cb.value); });
                } catch(_) {}
            });
        })();
    </script>
@endsection
