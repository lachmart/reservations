@extends('layouts.app')

@section('title', 'Maraka rental — pronájem zvukové a světelné techniky')

@section('full')
    {{-- Hero --}}
    <section class="relative overflow-hidden border-b border-line">
        <div class="wrap grid gap-10 py-14 sm:py-20 lg:grid-cols-12 lg:items-center">
            <div class="lg:col-span-7">
                <div class="kicker">Zvuk · Světlo · Stage</div>
                <h1 class="mt-4 max-w-2xl text-display-xl font-extrabold text-ink">
                    Ozvučíme a&nbsp;nasvítíme<br class="hidden sm:block"> vaši <span class="text-accent">akci</span>.
                </h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-muted">
                    Profesionální aparatura a světla k zapůjčení pro koncerty, svatby i firemní eventy až do 600&nbsp;osob. Vyberte termín, sestavte si set a rezervujte online.
                </p>

                <div class="mt-8 max-w-2xl">
                    @include('partials.term', ['variant' => 'hero', 'start' => session('cart.start_date'), 'end' => session('cart.end_date'), 'submitLabel' => 'Zobrazit dostupnou techniku'])
                </div>

                <dl class="mt-10 flex flex-wrap gap-x-10 gap-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-faint">Položek k zapůjčení</dt>
                        <dd class="font-display text-2xl font-extrabold text-ink">{{ $productCount }}+</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-faint">Akce až do</dt>
                        <dd class="font-display text-2xl font-extrabold text-ink">600 osob</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-faint">Rezervace</dt>
                        <dd class="font-display text-2xl font-extrabold text-ink">Online</dd>
                    </div>
                </dl>
            </div>

            <div class="lg:col-span-5">
                @php($heroProduct = $featured->first())
                @php($heroImg = $heroProduct ? $heroProduct->getFirstMediaUrl() : null)
                <div class="relative mx-auto max-w-md rounded-2xl border border-line bg-surface p-6 shadow-card lg:ml-auto">
                    <div class="flex items-center justify-between">
                        <span class="badge-accent">Doporučujeme</span>
                        <span class="text-xs text-faint">Připraveno na scénu</span>
                    </div>
                    @if($heroImg)
                        <img src="{{ $heroImg }}" alt="{{ $heroProduct->name }}" class="mx-auto my-6 h-56 w-full object-contain">
                        <div class="text-sm font-semibold uppercase tracking-wide text-faint">{{ optional($heroProduct->category)->name }}</div>
                        <div class="font-display text-lg font-bold text-ink">{{ $heroProduct->name }}</div>
                    @else
                        <div class="my-6 grid h-56 place-items-center text-faint">Katalog techniky</div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Category tiles --}}
    <section class="wrap py-14">
        <div class="flex items-end justify-between">
            <h2 class="text-display-md font-extrabold text-ink">Co si u&nbsp;nás půjčíte</h2>
            <a href="{{ route('products.index') }}" class="hidden text-sm font-semibold text-accent-ink hover:underline sm:inline">Celý katalog →</a>
        </div>
        <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach($categories as $cat)
                <a href="{{ route('products.index', ['category' => $cat->id]) }}"
                   class="group relative flex flex-col overflow-hidden rounded-xl border border-line bg-surface transition-[box-shadow,transform] duration-200 ease-out-quart hover:-translate-y-0.5 hover:shadow-lift">
                    <div class="relative aspect-[5/4] overflow-hidden" style="background:color-mix(in oklab, var(--ink) 4%, var(--surface))">
                        @if($cat->sample_image)
                            <img src="{{ $cat->sample_image }}" alt="{{ $cat->name }}" loading="lazy" class="absolute inset-0 size-full object-contain p-6 transition-transform duration-300 ease-out-quart group-hover:scale-105">
                        @endif
                    </div>
                    <div class="flex items-center justify-between p-4">
                        <div>
                            <div class="font-display text-lg font-extrabold text-ink">{{ $cat->name }}</div>
                            <div class="text-sm text-muted">{{ $cat->available_count }} položek</div>
                        </div>
                        <span class="grid size-8 place-items-center rounded-full border border-line text-muted transition-colors group-hover:border-accent group-hover:text-accent-ink">→</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Featured gear --}}
    @if($featured->count())
        <section class="border-y border-line" style="background:color-mix(in oklab, var(--ink) 3%, var(--paper))">
            <div class="wrap py-14">
                <div class="flex items-end justify-between">
                    <div>
                        <div class="kicker">Vybavení</div>
                        <h2 class="mt-2 text-display-md font-extrabold text-ink">Oblíbená technika</h2>
                    </div>
                    <a href="{{ route('products.index') }}" class="btn-secondary">Zobrazit vše</a>
                </div>
                <div class="mt-8 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                    @foreach($featured as $product)
                        @include('partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- How it works --}}
    <section class="wrap py-16">
        <h2 class="text-display-md font-extrabold text-ink">Rezervace ve třech krocích</h2>
        <div class="mt-8 grid gap-8 sm:grid-cols-3">
            @foreach([
                ['01', 'Vyberte termín', 'Zadejte datum od–do a uvidíte, co je na váš termín skladem.'],
                ['02', 'Sestavte set', 'Přidejte reproboxy, mikrofony, světla i kabeláž do košíku.'],
                ['03', 'Odešlete rezervaci', 'Vyplňte kontakt a my se vám ozveme s potvrzením a cenou.'],
            ] as [$n, $t, $d])
                <div>
                    <div class="font-display text-4xl font-extrabold text-accent">{{ $n }}</div>
                    <h3 class="mt-3 font-display text-xl font-bold text-ink">{{ $t }}</h3>
                    <p class="mt-2 max-w-xs leading-relaxed text-muted">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="wrap pb-20">
        <div class="flex flex-col items-start justify-between gap-6 rounded-2xl px-8 py-10 sm:flex-row sm:items-center" style="background:var(--ink)">
            <div>
                <h2 class="font-display text-2xl font-extrabold" style="color:var(--paper)">Nevíte, co na akci potřebujete?</h2>
                <p class="mt-2 max-w-lg" style="color:color-mix(in oklab, var(--paper) 70%, transparent)">Poradíme se sestavením ozvučení i osvětlení na míru vaší akci a prostoru.</p>
            </div>
            <a href="{{ route('products.index') }}" class="btn-primary px-6 py-3 text-base">Prohlédnout techniku</a>
        </div>
    </section>
@endsection
