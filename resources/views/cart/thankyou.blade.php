@extends('layouts.app')

@section('title', 'Děkujeme — Maraka rental')

@section('content')
    <div class="mx-auto max-w-xl py-10 text-center">
        <div class="mx-auto grid size-14 place-items-center rounded-full" style="background:var(--accent);color:var(--accent-ink)">
            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        </div>
        <div class="kicker mt-6 justify-center">Rezervace odeslána</div>
        <h1 class="mt-3 font-display text-display-lg font-extrabold text-ink">Děkujeme!</h1>
        <p class="mx-auto mt-4 max-w-md leading-relaxed text-muted">Vaši rezervaci jsme přijali a ozveme se vám s potvrzením termínu a cenou. Potvrzení jsme poslali i na váš e-mail.</p>
        <div class="mt-8 flex items-center justify-center gap-3">
            <a href="{{ route('products.index') }}" class="btn-primary">Prohlédnout další techniku</a>
            <a href="/" class="btn-secondary">Na úvod</a>
        </div>
    </div>
@endsection
