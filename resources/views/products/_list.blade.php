@php($__cart = session('cart', []))
<div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($products as $product)
        @php($isBundle = method_exists($product,'isBundle') && $product->isBundle())
        @php($initialCount = (int) ($isBundle ? (($__cart['bundles'][$product->id] ?? 0)) : (($__cart['items'][$product->id] ?? 0))))
        <div class="card overflow-hidden hover:ring-2 hover:ring-emerald-400/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500" data-product-card data-product-id="{{ $product->id }}">
            <a href="{{ route('products.show', $product) }}" class="block bg-slate-200/70 dark:bg-slate-800/50">
                @php($media = method_exists($product,'getFirstMediaUrl') ? $product->getFirstMediaUrl() : null)
                @if(!empty($media))
                    <img src="{{ $media }}" alt="{{ $product->name }}" class="w-full h-48 object-contain">
                @else
                    <div class="w-full h-48 flex items-center justify-center text-slate-500 dark:text-slate-400">Bez obrázku</div>
                @endif
            </a>
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <a href="{{ route('products.show', $product) }}" class="text-lg font-semibold text-slate-900 hover:underline dark:text-white">{{ $product->name }}</a>
                    <div class="text-sm text-slate-600 dark:text-slate-300">{{ number_format((float)($product->price_per_day ?? 0), 2, ',', ' ') }} Kč/den</div>
                </div>
                <div class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                    @if($product->category)
                        <span class="badge">{{ $product->category->name }}</span>
                    @endif
                </div>
                @if(($product->tags ?? null) && $product->tags->count())
                    <div class="mt-2 flex flex-wrap gap-1">
                        @foreach($product->tags as $tag)
                            <span class="badge">#{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="mt-3 flex items-center justify-between gap-3">
                    <form method="post" action="{{ route('cart.add') }}" class="flex-1 flex items-center gap-3 add-to-cart-form">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="amount" value="1">
                        <button type="submit" class="btn-primary w-full sm:w-auto">Přidat do košíku</button>
                    </form>
                    <a href="{{ route('products.show', $product) }}" class="btn-secondary">Detail</a>
                </div>
                <div class="mt-2 text-sm text-slate-600 dark:text-slate-300 {{ $initialCount > 0 ? '' : 'hidden' }}" data-in-cart-row>
                    V košíku: <span id="product-count-{{ $product->id }}">{{ $initialCount }}</span> ks
                </div>
            </div>
        </div>
    @empty
        <div class="sm:col-span-2 lg:col-span-3">
            <div class="card">
                <div class="card-body text-slate-600 dark:text-slate-300">Žádné produkty nenalezeny podle zadaných filtrů.</div>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-6">
    {{ $products->links() }}
</div>
