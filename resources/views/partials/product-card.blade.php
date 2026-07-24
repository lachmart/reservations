@php
    $__cart = session('cart', []);
    $isBundle = method_exists($product, 'isBundle') && $product->isBundle();
    $initialCount = (int) ($isBundle ? ($__cart['bundles'][$product->id] ?? 0) : ($__cart['items'][$product->id] ?? 0));
    $media = method_exists($product, 'mainImageUrl') ? $product->mainImageUrl() : null;
    $price = number_format((float) ($product->price_per_day ?? 0), 0, ',', ' ');
@endphp
<article data-product-card data-product-id="{{ $product->id }}"
         class="group flex flex-col overflow-hidden rounded-xl border border-line bg-surface transition-[box-shadow,transform] duration-200 ease-out-quart hover:-translate-y-0.5 hover:shadow-lift">
    <a href="{{ route('products.show', $product) }}" class="relative block aspect-[4/3] overflow-hidden" style="background:color-mix(in oklab, var(--ink) 4%, var(--surface))">
        @if(!empty($media))
            <img src="{{ $media }}" alt="{{ $product->name }}" loading="lazy"
                 class="absolute inset-0 size-full object-contain p-5 transition-transform duration-300 ease-out-quart group-hover:scale-[1.04]">
        @else
            <div class="absolute inset-0 flex items-center justify-center">
                <svg class="size-10 text-line" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
            </div>
        @endif
        @if($isBundle)
            <span class="absolute left-3 top-3 badge-accent">Set</span>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-4">
        @if($product->category)
            <div class="text-xs font-semibold uppercase tracking-wide text-faint">{{ $product->category->name }}</div>
        @endif
        <a href="{{ route('products.show', $product) }}" class="mt-1 line-clamp-2 font-display text-[0.98rem] font-bold leading-tight text-ink hover:text-accent-ink">{{ $product->name }}</a>

        <div class="mt-auto flex items-end justify-between gap-2 pt-4">
            <div>
                <div class="font-display text-lg font-extrabold text-ink">{{ $price }} <span class="text-sm font-semibold text-muted">Kč/den</span></div>
                <div data-in-cart-row class="text-xs font-medium text-accent-ink {{ $initialCount > 0 ? '' : 'hidden' }}">V košíku: <span id="product-count-{{ $product->id }}">{{ $initialCount }}</span> ks</div>
            </div>
            <form method="post" action="{{ route('cart.add') }}" class="add-to-cart-form shrink-0">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="amount" value="1">
                <button type="submit" class="btn-primary px-3 py-2" aria-label="Přidat {{ $product->name }} do košíku">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                    <span class="hidden sm:inline">Přidat</span>
                </button>
            </form>
        </div>
    </div>
</article>
@once
<script>
(function () {
    function setBadge(count) {
        var b = document.querySelector('#nav-cart-link [data-cart-count]');
        if (!b) return;
        b.textContent = String(count);
        b.classList.toggle('hidden', !(count > 0));
    }
    document.addEventListener('submit', async function (e) {
        var form = e.target.closest('form.add-to-cart-form');
        if (!form) return;
        e.preventDefault();
        var pid = Number(form.querySelector('input[name="product_id"]').value);
        var amt = Number((form.querySelector('input[name="amount"]') || {}).value || 1);
        try {
            var res = await axios.post(form.getAttribute('action'), { product_id: pid, amount: amt });
            var data = res.data || {};
            setBadge(Number(data.cart_count || 0));
            var card = form.closest('[data-product-card]');
            var countEl = document.getElementById('product-count-' + pid);
            var row = card ? card.querySelector('[data-in-cart-row]') : null;
            if (countEl) countEl.textContent = String(data.product_count || 0);
            if (row) row.classList.toggle('hidden', !((data.product_count || 0) > 0));
            window.showToast && window.showToast(data.message || 'Přidáno do košíku');
        } catch (err) {
            window.showToast && window.showToast((err && err.response && err.response.data && err.response.data.message) || 'Nelze přidat do košíku', true);
        }
    });
})();
</script>
@endonce
