@extends('layouts.app')

@section('title', $product->name . ' — Maraka rental')

@section('content')
    @php($galleryItems = method_exists($product,'getMedia') ? $product->getMedia('product-images') : collect())
    @php($avatarUrl = method_exists($product,'getFirstMediaUrl') ? $product->getFirstMediaUrl('avatar') : null)
    @php($fallbackAny = method_exists($product,'getFirstMediaUrl') ? $product->getFirstMediaUrl() : null)
    @php($galleryFirstUrl = ($galleryItems && $galleryItems->count() > 0) ? ($galleryItems->first()?->getUrl()) : null)
    @php($mainUrl = $avatarUrl ?: ($galleryFirstUrl ?: $fallbackAny))
    @php($datesSet = !empty($start) && !empty($end))

    <a href="{{ route('products.index') }}" class="text-sm font-semibold text-accent-ink hover:underline dark:text-accent">← Zpět na techniku</a>

    <div class="mt-4 grid grid-cols-1 gap-10 lg:grid-cols-2">
        {{-- Gallery --}}
        <div>
            <div class="overflow-hidden rounded-2xl border border-line" style="background:color-mix(in oklab, var(--ink) 4%, var(--surface))">
                @if(!empty($mainUrl))
                    <div id="product-image-viewport" class="relative aspect-square overflow-hidden">
                        <img id="product-main-image" src="{{ $mainUrl }}" alt="{{ $product->name }}" class="absolute inset-0 size-full cursor-zoom-in object-contain p-8 transition-transform duration-300 ease-out">
                    </div>
                @else
                    <div class="grid aspect-square place-items-center text-line"><svg class="size-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></div>
                @endif
            </div>
            @if($galleryItems && $galleryItems->count() > 0)
                <div id="product-thumbnails" class="mt-3 flex gap-2 overflow-x-auto">
                    @foreach($galleryItems as $m)
                        <button type="button" class="shrink-0 overflow-hidden rounded-lg border border-line p-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--accent)]" data-full-url="{{ $m->getUrl() }}">
                            <img src="{{ $m->getUrl('preview') }}" alt="" class="size-16 rounded object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Info --}}
        <div data-product-card data-product-id="{{ $product->id }}">
            <div class="flex flex-wrap items-center gap-2">
                @if($product->category)<span class="badge-accent">{{ $product->category->name }}</span>@endif
                @foreach(($product->tags ?? collect()) as $tag)<span class="badge">{{ $tag->name }}</span>@endforeach
            </div>
            <h1 class="mt-3 font-display text-display-md font-extrabold text-ink">{{ $product->name }}</h1>

            <div class="mt-5 flex items-baseline gap-2">
                <span class="font-display text-4xl font-extrabold text-ink">{{ number_format((float) ($product->price_per_day ?? 0), 0, ',', ' ') }}</span>
                <span class="text-lg font-semibold text-muted">Kč / den</span>
            </div>

            {{-- Term + availability --}}
            <div class="mt-6 rounded-2xl border border-line p-4" style="background:color-mix(in oklab, var(--ink) 3%, var(--surface))">
                <div class="text-xs font-semibold uppercase tracking-wide text-faint">Dostupnost podle termínu</div>
                <div id="availability-box" class="mt-1">
                    @if(isset($available))
                        <div class="font-display text-2xl font-extrabold text-ink">{{ $available }} <span class="text-base font-semibold text-muted">ks skladem</span></div>
                        <div class="text-xs text-faint">{{ $start ? \Carbon\Carbon::parse($start)->format('d.m.Y') : '' }} → {{ $end ? \Carbon\Carbon::parse($end)->format('d.m.Y') : '' }}</div>
                    @else
                        <div class="text-sm text-muted">Zadejte termín a uvidíte, kolik kusů je volných.</div>
                    @endif
                </div>

                <form id="product-availability-form" method="get" action="{{ route('products.show', $product) }}" class="mt-3">
                    <div class="flex flex-col gap-2 rounded-xl border border-line bg-surface p-1.5 sm:flex-row sm:items-center">
                        <label class="flex flex-1 items-center gap-2 px-2 py-1">
                            <span class="text-[0.65rem] font-semibold uppercase tracking-wide text-faint">Od</span>
                            <input id="start_date" type="date" name="start_date" value="{{ $start ? \Carbon\Carbon::parse($start)->format('Y-m-d') : '' }}" class="w-full bg-transparent text-sm font-semibold text-ink focus:outline-none">
                        </label>
                        <span class="hidden text-faint sm:inline">→</span>
                        <label class="flex flex-1 items-center gap-2 px-2 py-1">
                            <span class="text-[0.65rem] font-semibold uppercase tracking-wide text-faint">Do</span>
                            <input id="end_date" type="date" name="end_date" value="{{ $end ? \Carbon\Carbon::parse($end)->format('Y-m-d') : '' }}" class="w-full bg-transparent text-sm font-semibold text-ink focus:outline-none">
                        </label>
                        <button class="btn-secondary shrink-0" type="submit">Zobrazit</button>
                    </div>
                </form>
            </div>

            {{-- Add to cart --}}
            @php($__cart = session('cart', []))
            @php($isBundle = method_exists($product,'isBundle') && $product->isBundle())
            @php($initialCount = (int) ($isBundle ? ($__cart['bundles'][$product->id] ?? 0) : ($__cart['items'][$product->id] ?? 0)))
            <form method="post" action="{{ route('cart.add') }}" class="add-to-cart-form mt-6 flex items-center gap-3">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="number" name="amount" min="1" value="1" class="field w-24 text-center" aria-label="Množství">
                <button class="btn-primary flex-1 py-3 text-base" type="submit">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    Přidat do košíku
                </button>
            </form>
            <div data-in-cart-row class="mt-2 text-sm font-medium text-accent-ink {{ $initialCount > 0 ? '' : 'hidden' }}">V košíku: <span id="product-count-{{ $product->id }}">{{ $initialCount }}</span> ks</div>

            @if(!empty($product->description))
                <div class="mt-8">
                    <div class="rule"></div>
                    <h2 class="mt-6 font-display text-lg font-bold text-ink">Popis</h2>
                    <div class="prose mt-2 max-w-none whitespace-pre-line leading-relaxed text-muted">{!! $product->description !!}</div>
                </div>
            @endif
        </div>
    </div>

    <script>
        (function () {
            // Image zoom (mouse-follow, up to natural size)
            const img = document.getElementById('product-main-image');
            const viewport = document.getElementById('product-image-viewport');
            if (img && viewport) {
                function scale() { const nw = img.naturalWidth || 0, cw = img.clientWidth || 0; return (!nw || !cw) ? 1 : Math.max(1, Math.min(2.4, nw / cw)); }
                function origin(e) { const r = viewport.getBoundingClientRect(); img.style.transformOrigin = `${((e.clientX-r.left)/r.width)*100}% ${((e.clientY-r.top)/r.height)*100}%`; }
                viewport.addEventListener('mouseenter', e => { origin(e); img.style.transform = `scale(${scale()})`; });
                viewport.addEventListener('mousemove', origin);
                viewport.addEventListener('mouseleave', () => { img.style.transformOrigin = 'center'; img.style.transform = 'scale(1)'; });
                document.querySelectorAll('#product-thumbnails [data-full-url]').forEach(b => b.addEventListener('click', () => { img.style.transform = 'scale(1)'; img.src = b.getAttribute('data-full-url'); }));
            }

            // Availability: set term (session) + refresh box in place
            const form = document.getElementById('product-availability-form');
            if (form) {
                const s = form.querySelector('#start_date'), e = form.querySelector('#end_date');
                const box = document.getElementById('availability-box');
                async function refresh() {
                    if (!(s.value && e.value)) return;
                    try {
                        await axios.post("{{ route('cart.setDates') }}", { start_date: s.value, end_date: e.value });
                        const res = await fetch(`${form.action}?start_date=${encodeURIComponent(s.value)}&end_date=${encodeURIComponent(e.value)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                        const nb = doc.querySelector('#availability-box');
                        if (nb && box) box.innerHTML = nb.innerHTML;
                    } catch (_) {}
                }
                form.addEventListener('submit', ev => { ev.preventDefault(); refresh(); });
                s.addEventListener('change', () => { if (s.value) e.min = s.value; refresh(); });
                e.addEventListener('change', refresh);
            }
        })();
    </script>
@endsection
