@extends('layouts.app')

@section('title', $page['title'])
@section('meta_description', $page['meta_description'])
@section('canonical', url($landing))
@push('schema')
<script type="application/ld+json">
{!! json_encode([
    '@@context' => 'https://schema.org',
    '@graph' => array_values(array_filter([
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Domů', 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $page['h1'], 'item' => url($landing)],
            ],
        ],
        ! empty($page['faq']) ? [
            '@type' => 'FAQPage',
            'mainEntity' => collect($page['faq'])->map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
            ])->all(),
        ] : null,
    ])),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('full')
    <section class="wrap py-12 sm:py-16">
        <div class="kicker">{{ $page['kicker'] }}</div>
        <h1 class="mt-2 max-w-3xl text-display-xl font-extrabold text-ink">{{ $page['h1'] }}</h1>
        <p class="mt-5 max-w-2xl text-lg leading-relaxed text-muted">{{ $page['intro'] }}</p>
        <div class="mt-6 flex flex-wrap gap-3">
            @if($sets->count())<a href="#sety" class="btn-primary px-6">Vybrat set</a>@endif
            <a href="#poptavka" class="btn-secondary px-6">Nezávazná poptávka</a>
        </div>
    </section>

    @if($sets->count())
        <section id="sety" class="scroll-mt-24 border-y border-line" style="background:color-mix(in oklab, var(--ink) 3%, var(--paper))">
            <div class="wrap py-12">
                <h2 class="text-display-md font-extrabold text-ink">{{ $page['sets_heading'] ?? 'Hotové sety' }}</h2>
                <p class="mt-2 max-w-2xl text-muted">Cena je za den a za celý set. Co v setu je, najdete v detailu.</p>
                <div class="mt-8 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                    @foreach($sets as $product)
                        @include('partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="wrap grid gap-10 py-14 md:grid-cols-2">
        @foreach($page['sections'] as [$heading, $body])
            <div>
                <h2 class="font-display text-xl font-bold text-ink">{{ $heading }}</h2>
                <p class="mt-2 leading-relaxed text-muted">{{ $body }}</p>
            </div>
        @endforeach
    </section>

    @if($products->count())
        <section class="border-t border-line">
            <div class="wrap py-12">
                <div class="flex items-end justify-between">
                    <h2 class="text-display-md font-extrabold text-ink">{{ $page['products_heading'] ?? 'Technika' }}</h2>
                    <a href="{{ route('products.index') }}" class="btn-secondary">Celý katalog</a>
                </div>
                <div class="mt-8 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                    @foreach($products as $product)
                        @include('partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if(!empty($page['faq']))
        <section class="border-t border-line">
            <div class="wrap py-12">
                <h2 class="text-display-md font-extrabold text-ink">Časté otázky</h2>
                <div class="mt-4 max-w-3xl divide-y divide-line border-y border-line">
                    @foreach($page['faq'] as [$q, $a])
                        <details class="group py-4" open>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-display font-bold text-ink">
                                {{ $q }}
                                <span class="text-muted transition-transform group-open:rotate-45">+</span>
                            </summary>
                            <p class="mt-2 leading-relaxed text-muted">{{ $a }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <div class="wrap py-12">
        @include('partials.enquiry-form')

        @if($others->count())
            <nav class="mt-10 text-sm text-muted" aria-label="Další stránky">
                <span class="font-semibold text-ink">Mohlo by vás zajímat:</span>
                @foreach($others as $o)
                    <a href="{{ $o['url'] }}" class="ml-2 font-semibold text-accent-ink hover:underline dark:text-accent">{{ $o['label'] }}</a>@if(! $loop->last) ·@endif
                @endforeach
            </nav>
        @endif
    </div>
@endsection
