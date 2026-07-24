<div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
    @forelse($products as $product)
        @include('partials.product-card', ['product' => $product])
    @empty
        <div class="col-span-full">
            <div class="rounded-xl border border-dashed border-line bg-surface px-6 py-16 text-center">
                <div class="font-display text-lg font-bold text-ink">Nic jsme nenašli</div>
                <p class="mx-auto mt-2 max-w-sm text-muted">Zkuste upravit hledaný výraz nebo zrušit filtry — pak se vám zobrazí celý katalog.</p>
                <a href="{{ route('products.index') }}" class="btn-secondary mt-5">Zrušit filtry</a>
            </div>
        </div>
    @endforelse
</div>

@if($products->hasPages())
    <div class="mt-10">
        {{ $products->links() }}
    </div>
@endif
