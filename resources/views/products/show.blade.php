@extends('layouts.app')

@section('title', $product->name . ' — Maraka rental')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('products.index') }}" class="text-sm text-emerald-600 hover:underline dark:text-emerald-400">← Zpět na produkty</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ $product->name }}</h1>
        </div>
        <a href="{{ route('cart.index') }}" class="btn-primary">Přejít do košíku</a>
    </div>

    <div class="card mt-6 overflow-hidden">
        <div class="card-body">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div>
                    @php($galleryItems = method_exists($product,'getMedia') ? $product->getMedia('product-images') : collect())
                    @php($avatarUrl = method_exists($product,'getFirstMediaUrl') ? $product->getFirstMediaUrl('avatar') : null)
                    @php($fallbackAny = method_exists($product,'getFirstMediaUrl') ? $product->getFirstMediaUrl() : null)
                    @php($galleryFirstUrl = ($galleryItems && $galleryItems->count() > 0) ? ($galleryItems->first()?->getUrl()) : null)
                    @php($mainUrl = $avatarUrl ?: ($galleryFirstUrl ?: $fallbackAny))
                    @if(!empty($mainUrl))
                        <div class="mx-auto max-w-lg lg:max-w-xl">
                            <div id="product-image-viewport" class="overflow-hidden">
                                <img id="product-main-image" src="{{ $mainUrl }}" alt="{{ $product->name }}" class="block w-full h-auto max-h-[420px] object-contain rounded transition-transform duration-300 ease-out cursor-default">
                            </div>
                            @if($galleryItems && $galleryItems->count() > 0)
                                <div id="product-thumbnails" class="mt-3 flex gap-2 overflow-x-auto">
                                    @foreach($galleryItems as $m)
                                        <button type="button" class="shrink-0 rounded-md p-0.5 border border-transparent bg-white/60 dark:bg-slate-800/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-slate-900" data-full-url="{{ $m->getUrl() }}">
                                            <img src="{{ $m->getUrl('preview') }}" alt="{{ $product->name }} thumbnail" class="block h-16 w-16 rounded object-cover">
                                        </button>
                                    @endforeach
                                </div>
                                <script>
                                    (function(){
                                        const mainImg = document.getElementById('product-main-image');
                                        if (!mainImg) return;
                                        const viewport = document.getElementById('product-image-viewport') || mainImg.parentElement;

                                        // --- 1:1 Zoom within container ---
                                        let isHovering = false;
                                        let pointerInside = false;
                                        let lastX = 50; // last known cursor X inside container in %
                                        let lastY = 50; // last known cursor Y inside container in %
                                        let zoomScale = 1;

                                        function computeZoomScale() {
                                            // natural width vs current displayed width
                                            const nw = mainImg.naturalWidth || 0;
                                            const cw = mainImg.clientWidth || 0;
                                            if (!nw || !cw) return 1;
                                            // We want to zoom to 100% (natural size) but not beyond
                                            return Math.max(1, nw / cw);
                                        }

                                        function applyZoom(scale) {
                                            mainImg.style.transform = `scale(${scale})`;
                                        }

                                        function setOriginFromEvent(e) {
                                            const rect = viewport.getBoundingClientRect();
                                            const x = Math.min(100, Math.max(0, ((e.clientX - rect.left) / rect.width) * 100));
                                            const y = Math.min(100, Math.max(0, ((e.clientY - rect.top) / rect.height) * 100));
                                            lastX = x; lastY = y;
                                            mainImg.style.transformOrigin = `${x}% ${y}%`;
                                        }

                                        function onEnter(e) {
                                            pointerInside = true;
                                            zoomScale = computeZoomScale();
                                            isHovering = true;
                                            if (e) setOriginFromEvent(e);
                                            applyZoom(zoomScale);
                                        }

                                        function onLeave() {
                                            pointerInside = false;
                                            isHovering = false;
                                            mainImg.style.transformOrigin = 'center center';
                                            applyZoom(1);
                                        }

                                        function onMove(e) {
                                            // Track last known pointer position always
                                            const rect = viewport.getBoundingClientRect();
                                            lastX = Math.min(100, Math.max(0, ((e.clientX - rect.left) / rect.width) * 100));
                                            lastY = Math.min(100, Math.max(0, ((e.clientY - rect.top) / rect.height) * 100));
                                            pointerInside = true;
                                            if (!isHovering) return;
                                            mainImg.style.transformOrigin = `${lastX}% ${lastY}%`;
                                        }

                                        viewport.addEventListener('mouseenter', (e) => onEnter(e));
                                        viewport.addEventListener('mouseleave', onLeave);
                                        viewport.addEventListener('mousemove', onMove);

                                        // Recompute on image load (e.g., after switching thumbnails)
                                        mainImg.addEventListener('load', () => {
                                            // Reset state for new image
                                            isHovering = false;
                                            mainImg.style.transformOrigin = 'center center';
                                            applyZoom(1);
                                            // If pointer is still inside, immediately re-apply zoom at last position
                                            if (pointerInside) {
                                                zoomScale = computeZoomScale();
                                                mainImg.style.transformOrigin = `${lastX}% ${lastY}%`;
                                                applyZoom(zoomScale);
                                                isHovering = true;
                                            }
                                        });

                                        // --- Gallery switching ---
                                        const thumbs = document.getElementById('product-thumbnails');
                                        const buttons = thumbs?.querySelectorAll('button[data-full-url]');
                                        if (buttons) {
                                            buttons.forEach(btn => {
                                                btn.addEventListener('click', () => {
                                                    const url = btn.getAttribute('data-full-url');
                                                    if (!url) return;
                                                    // Reset transform while loading new image
                                                    onLeave();
                                                    mainImg.setAttribute('src', url);
                                                });
                                            });
                                        }
                                    })();
                                </script>
                            @endif
                        </div>
                    @else
                        <div class="w-full h-64 flex items-center justify-center text-slate-500 dark:text-slate-400">Bez obrázku</div>
                    @endif
                </div>
                <div>
            <div class="flex flex-wrap items-center gap-2">
                @if(!empty($product->status))
                    <span class="badge">Stav: {{ ucfirst($product->status) }}</span>
                @endif
                @if($product->category)
                    <span class="badge">Kategorie: {{ $product->category->name }}</span>
                @endif
            </div>

            @php($datesSet = !empty($start) && !empty($end))
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="p-3 rounded bg-slate-50 dark:bg-slate-900/30">
                    <div class="text-xs uppercase text-slate-500">Cena za den</div>
                    <div class="text-lg font-semibold text-slate-900 dark:text-white">{{ number_format((float)($product->price_per_day ?? 0), 2, ',', ' ') }} Kč</div>
                </div>
                <div class="p-3 rounded bg-slate-50 dark:bg-slate-900/30">
                    <div class="text-xs uppercase text-slate-500">Sklad</div>
                    <div class="text-lg font-semibold text-slate-900 dark:text-white">{{ (int)($product->stock ?? 0) }} ks</div>
                </div>
                <div id="availability-box" class="p-3 rounded bg-slate-50 dark:bg-slate-900/30 sm:col-span-3">
                    <div class="text-xs uppercase text-slate-500">Dostupnost (podle termínu)</div>
                    @if(isset($available))
                        <div class="text-lg font-semibold text-slate-900 dark:text-white">{{ $available }} ks</div>
                        <div class="text-xs text-slate-500">pro termín {{ $start ? \Carbon\Carbon::parse($start)->format('d.m.y H:i') : '' }} → {{ $end ? \Carbon\Carbon::parse($end)->format('d.m.y H:i') : '' }}</div>
                    @else
                        <div class="text-sm text-slate-600 dark:text-slate-300">Vyberte termín níže</div>
                    @endif
                    @if($datesSet)
                        <div class="mt-2">
                            <button id="toggle-date-inputs" type="button" class="btn-secondary text-xs px-2 py-1">Změnit termín</button>
                        </div>
                    @endif
                </div>
            </div>


            <form id="product-availability-form" method="get" action="{{ route('products.show', $product) }}" class="mt-4 flex flex-wrap items-end gap-3 {{ $datesSet ? 'hidden' : '' }}">
                <div>
                    <label for="start_date" class="block text-xs text-slate-500">Od</label>
                    <input id="start_date" type="datetime-local" name="start_date" value="{{ old('start_date', $start) }}" class="input">
                </div>
                <div>
                    <label for="end_date" class="block text-xs text-slate-500">Do</label>
                    <input id="end_date" type="datetime-local" name="end_date" value="{{ old('end_date', $end) }}" class="input">
                </div>
                <div>
                    <button class="btn-secondary" type="submit">Zobrazit dostupnost</button>
                </div>
            </form>
            <script>
                (function(){
                    const form = document.getElementById('product-availability-form');
                    if (!form) return;
                    const start = form.querySelector('#start_date');
                    const end = form.querySelector('#end_date');
                    const availabilityBox = document.getElementById('availability-box');
                    const availabilityUrlBase = form.getAttribute('action');

                    const toggleBtn = document.getElementById('toggle-date-inputs');
                    if (toggleBtn) {
                        toggleBtn.addEventListener('click', function(){
                            form.classList.remove('hidden');
                            toggleBtn.classList.add('hidden');
                            // focus the first input for convenience
                            const first = form.querySelector('#start_date');
                            if (first) first.focus();
                        });
                    }

                    function bothFilled(){ return start && start.value && end && end.value; }

                    async function setDatesAjax(){
                        if (!bothFilled()) return;
                        await axios.post("{{ route('cart.setDates') }}", {
                            start_date: start.value,
                            end_date: end.value,
                        });
                    }

                    async function refreshAvailability(){
                        if (!availabilityBox) return;
                        const url = `${availabilityUrlBase}?start_date=${encodeURIComponent(start.value)}&end_date=${encodeURIComponent(end.value)}`;
                        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        const html = await res.text();
                        // Create a DOM to query the new availability box
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newBox = doc.querySelector('#availability-box');
                        if (newBox) {
                            availabilityBox.innerHTML = newBox.innerHTML;
                        }
                    }

                    async function onChange(){
                        if (!bothFilled()) return;
                        try {
                            await setDatesAjax();
                            await refreshAvailability();
                        } catch (e) {
                            // ignore errors; validation will take place later
                        }
                    }

                    if (start) start.addEventListener('change', onChange);
                    if (end) end.addEventListener('change', onChange);
                })();
            </script>

            @php($__cart = session('cart', []))
            @php($isBundle = method_exists($product,'isBundle') && $product->isBundle())
            @php($initialCount = (int) ($isBundle ? (($__cart['bundles'][$product->id] ?? 0)) : (($__cart['items'][$product->id] ?? 0))))

            <form method="post" action="{{ route('cart.add') }}" class="mt-4 flex flex-wrap items-end gap-3" id="product-add-form">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <div>
                    <label for="amount" class="block text-xs text-slate-500">Množství</label>
                    <input id="amount" type="number" name="amount" min="1" value="{{ old('amount', 1) }}" class="input w-32">
                </div>
                <div>
                    <button class="btn-primary" type="submit">Přidat do košíku</button>
                </div>
                <div class="text-sm text-slate-600 dark:text-slate-300 ml-2 {{ $initialCount > 0 ? '' : 'hidden' }}" id="product-in-cart-row">
                    V košíku: <span id="product-count-{{ $product->id }}">{{ $initialCount }}</span> ks
                </div>
            </form>
            <script>
                (function(){
                    const form = document.getElementById('product-add-form');
                    if (!form) return;
                    function showToast(message){
                        try {
                            const toast = document.createElement('div');
                            toast.textContent = message;
                            toast.className = 'fixed top-4 right-4 z-50 rounded border border-green-300/60 bg-green-50 px-4 py-2 text-sm text-green-800 shadow dark:border-green-700/60 dark:bg-green-900/30 dark:text-green-200';
                            document.body.appendChild(toast);
                            setTimeout(()=>{ toast.remove(); }, 2000);
                        } catch(_) {}
                    }
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
                    form.addEventListener('submit', async function(e){
                        e.preventDefault();
                        const amountInput = form.querySelector('input[name="amount"]');
                        const amount = amountInput ? Number(amountInput.value || '1') : 1;
                        try {
                            const res = await axios.post(form.getAttribute('action'), { product_id: {{ (int) $product->id }}, amount });
                            const data = res.data || {};
                            updateHeaderCartCount(Number(data.cart_count || 0));
                            const countEl = document.getElementById('product-count-{{ $product->id }}');
                            const row = document.getElementById('product-in-cart-row');
                            if (countEl) countEl.textContent = String(data.product_count || 0);
                            if (row) {
                                if ((data.product_count || 0) > 0) row.classList.remove('hidden');
                                else row.classList.add('hidden');
                            }
                            showToast(data.message || 'Přidáno do košíku');
                        } catch (err) {
                            showToast((err?.response?.data?.message) || 'Nelze přidat do košíku');
                        }
                    });
                })();
            </script>

                </div>
            </div>

            <p class="mt-4 text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ $product->description }}</p>
        </div>
    </div>
@endsection
