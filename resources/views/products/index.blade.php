@extends('layouts.app')

@section('title', 'Produkty — Rezervace')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Produkty</h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Procházejte dostupné položky k rezervaci.</p>
        </div>
        <a href="#" class="btn-primary">Nová rezervace</a>
    </div>

    @if($products->count() === 0)
        <div class="card mt-6">
            <div class="card-body">
                <p class="text-slate-600 dark:text-slate-300">Nenalezeny žádné produkty.</p>
            </div>
        </div>
    @else
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($products as $product)
                <a href="{{ url('/products/'.$product->id) }}" class="card hover:shadow-md transition">
                    <div class="bg-slate-200/70 dark:bg-slate-800/50 flex items-center justify-center text-slate-500 dark:text-slate-400">
                        @php($media = method_exists($product,'getFirstMediaUrl') ? $product->getFirstMediaUrl() : null)
                        @if(!empty($media))
                            <img src="{{ $media }}" alt="{{ $product->name }}" class="w-full h-48 object-cover">
                        @else
                            <span class="p-6">Bez obrázku</span>
                        @endif
                    </div>
                    <div class="card-body">
                        <h3 class="card-title">{{ $product->name }}</h3>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300 line-clamp-2">{{ Str::limit($product->description, 120) }}</p>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $products->links() }}
        </div>
    @endif
@endsection
