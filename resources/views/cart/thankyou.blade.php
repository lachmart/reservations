@extends('layouts.app')

@section('title', 'Děkujeme — Rezervace')

@section('content')
    <div class="mx-auto max-w-2xl text-center">
        <div class="inline-flex items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 px-3 py-1 text-sm font-medium">Rezervace byla úspěšně vytvořena</div>
        <h1 class="mt-4 text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white">Děkujeme za vaši rezervaci!</h1>
        <p class="mt-3 text-slate-600 dark:text-slate-300">Potvrzení jsme odeslali na váš e-mail. V případě potřeby vás budeme kontaktovat.
        </p>
        <div class="mt-8 flex items-center justify-center gap-3">
            <a href="{{ route('products.index') }}" class="btn-primary">Zpět na produkty</a>
        </div>
    </div>
@endsection
