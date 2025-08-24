@extends('layouts.app')

@section('title', 'Maraka rental')

@section('content')
    <section class="relative">
        <div class="mx-auto max-w-3xl text-center">
            <h1 class="text-3xl sm:text-5xl font-bold tracking-tight text-slate-900 dark:text-white">
                Pronájem zvukové a světelné techniky
            </h1>
            <p class="mt-4 text-slate-600 dark:text-slate-300">
                Maraka rental půjčuje profesionální zvukovou a světelnou techniku pro akce až do 600 osob. Procházejte nabídku, přidejte položky do košíku a snadno si rezervujte termín.
            </p>
            <div class="mt-8 flex items-center justify-center gap-3">
                <a href="{{ route('products.index') }}" class="btn-primary">Procházet produkty</a>
                <a href="{{ route('cart.index') }}" class="btn-secondary">Přejít do košíku</a>
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
                <div class="badge">Profesionální</div>
                <h3 class="card-title mt-2">Profesionální výbava</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Široká nabídka zvukové a světelné techniky pro malé i střední akce.</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="badge">Až 600 osob</div>
                <h3 class="card-title mt-2">Pro akce do 600 lidí</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Sestavíme ozvučení a osvětlení pro koncerty, svatby i firemní eventy.</p>
            </div>
        </div>
    </section>
@endsection
