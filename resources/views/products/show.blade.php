@extends('layouts.app')

@section('title', $product->name . ' — Rezervace')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('products.index') }}" class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Zpět na produkty</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ $product->name }}</h1>
        </div>
        <a href="#" class="btn-primary">Rezervovat</a>
    </div>

    <div class="card mt-6 overflow-hidden">
        <div class="bg-slate-200/70 dark:bg-slate-800/50">
            @php($media = method_exists($product,'getFirstMediaUrl') ? $product->getFirstMediaUrl() : null)
            @if(!empty($media))
                <img src="{{ $media }}" alt="{{ $product->name }}" class="w-full h-80 object-cover">
            @else
                <div class="w-full h-64 flex items-center justify-center text-slate-500 dark:text-slate-400">Bez obrázku</div>
            @endif
        </div>
        <div class="card-body">
            <div class="flex flex-wrap items-center gap-2">
                @if(!empty($product->status))
                    <span class="badge">Stav: {{ ucfirst($product->status) }}</span>
                @endif
                @if($product->category)
                    <span class="badge">Kategorie: {{ $product->category->name }}</span>
                @endif
            </div>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="p-3 rounded bg-slate-50 dark:bg-slate-900/30">
                    <div class="text-xs uppercase text-slate-500">Cena za den</div>
                    <div class="text-lg font-semibold text-slate-900 dark:text-white">{{ number_format((float)($product->price_per_day ?? 0), 2, ',', ' ') }} Kč</div>
                </div>
                <div class="p-3 rounded bg-slate-50 dark:bg-slate-900/30">
                    <div class="text-xs uppercase text-slate-500">Sklad</div>
                    <div class="text-lg font-semibold text-slate-900 dark:text-white">{{ (int)($product->stock ?? 0) }} ks</div>
                </div>
                <div class="p-3 rounded bg-slate-50 dark:bg-slate-900/30">
                    <div class="text-xs uppercase text-slate-500">Dostupnost (podle termínu)</div>
                    @if(isset($available))
                        <div class="text-lg font-semibold text-slate-900 dark:text-white">{{ $available }} ks</div>
                        <div class="text-xs text-slate-500">pro termín {{ $start }} → {{ $end }}</div>
                    @else
                        <div class="text-sm text-slate-600 dark:text-slate-300">Vyberte termín níže</div>
                    @endif
                </div>
            </div>

            <form method="get" action="{{ route('products.show', $product) }}" class="mt-4 flex flex-wrap items-end gap-3">
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

            <p class="mt-4 text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ $product->description }}</p>
        </div>
    </div>
@endsection
