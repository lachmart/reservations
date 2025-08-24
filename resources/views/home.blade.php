@extends('layouts.app')

@section('title', 'Rezervace')

@section('content')
    <section class="relative">
        <div class="mx-auto max-w-3xl text-center">
            <h1 class="text-3xl sm:text-5xl font-bold tracking-tight text-slate-900 dark:text-white">
                Rezervujte snadno a rychle
            </h1>
            <p class="mt-4 text-slate-600 dark:text-slate-300">
                Moderní a přehledný rezervační systém pro procházení produktů, plánování a správu rezervací v krásném tmavém rozhraní.
            </p>
            <div class="mt-8 flex items-center justify-center gap-3">
                <a href="{{ route('products.index') }}" class="btn-primary">Procházet produkty</a>
                <a href="#" class="btn-secondary">Vytvořit rezervaci</a>
            </div>
        </div>
    </section>

    <section class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div class="card">
            <div class="card-body">
                <div class="badge">Rychlé</div>
                <h3 class="card-title mt-2">Rychlá rezervace</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Rezervujte položky během několika kliknutí v intuitivním rozhraní.</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="badge">Chytré</div>
                <h3 class="card-title mt-2">Chytrá doporučení</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Zobrazte oblíbené volby a doporučené položky pro vaši trasu.</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="badge">Bezpečné</div>
                <h3 class="card-title mt-2">Bezpečná správa</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Spravujte rezervace s jistotou a přehledem.</p>
            </div>
        </div>
    </section>
@endsection
