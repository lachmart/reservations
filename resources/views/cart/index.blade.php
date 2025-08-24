@extends('layouts.app')

@section('title', 'Košík — Rezervace')

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('products.index') }}" class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Pokračovat v prohlížení</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">Košík</h1>
        </div>
        @if(count($items) > 0)
            <form method="post" action="{{ route('cart.clear') }}" onsubmit="return confirm('Opravdu vyprázdnit košík?');">
                @csrf
                @method('DELETE')
                <button class="btn-secondary" type="submit">Vyprázdnit</button>
            </form>
        @endif
    </div>

    <div id="cart-root" class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title">Termín rezervace</h2>
                    <form id="cart-dates-form" method="post" action="{{ route('cart.setDates') }}" class="mt-3 flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <label for="start_date" class="block text-xs text-slate-500">Od</label>
                            <input id="start_date" type="datetime-local" name="start_date" value="{{ old('start_date', $start) }}" class="input">
                        </div>
                        <div>
                            <label for="end_date" class="block text-xs text-slate-500">Do</label>
                            <input id="end_date" type="datetime-local" name="end_date" value="{{ old('end_date', $end) }}" class="input">
                        </div>
                        <div>
                            <button class="btn-primary" type="submit">Nastavit termín</button>
                        </div>
                    </form>
                    <script>
                        (function(){
                            const cartRoot = document.getElementById('cart-root');
                            if (!cartRoot) return;

                            function bindAutoSubmit(root){
                                const forms = root.querySelectorAll('form.auto-submit-on-change');
                                forms.forEach(form => {
                                    const input = form.querySelector('input[type="number"][name="amount"]');
                                    if (input && !input.__autoSubmitBound) {
                                        input.addEventListener('change', () => form.submit());
                                        input.__autoSubmitBound = true;
                                    }
                                });
                            }

                            async function postDatesAndRefresh(){
                                const form = document.getElementById('cart-dates-form');
                                if (!form) return;
                                const start = form.querySelector('#start_date');
                                const end = form.querySelector('#end_date');
                                if (!(start && start.value && end && end.value)) return;
                                await axios.post(form.getAttribute('action'), {
                                    start_date: start.value,
                                    end_date: end.value,
                                });
                                const res = await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                                const html = await res.text();
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');
                                const newRoot = doc.getElementById('cart-root');
                                if (newRoot) {
                                    cartRoot.innerHTML = newRoot.innerHTML;
                                    // rebind for newly injected nodes
                                    bindAutoSubmit(cartRoot);
                                    bindDateChange();
                                }
                            }

                            function bindDateChange(){
                                const form = document.getElementById('cart-dates-form');
                                if (!form) return;
                                const start = form.querySelector('#start_date');
                                const end = form.querySelector('#end_date');
                                const handler = () => { postDatesAndRefresh().catch(()=>{}); };
                                if (start && !start.__dateBound) { start.addEventListener('change', handler); start.__dateBound = true; }
                                if (end && !end.__dateBound) { end.addEventListener('change', handler); end.__dateBound = true; }
                            }

                            // initial bind
                            bindAutoSubmit(cartRoot);
                            bindDateChange();
                        })();
                    </script>
                    @if(!empty($start) && !empty($end) && !empty($days))
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Zvolený termín: {{ $start }} → {{ $end }} ({{ $days }} {{ $days == 1 ? 'den' : 'dny' }})</p>
                    @endif
                    @error('start_date')<p class="text-sm text-red-600 mt-2">{{ $message }}</p>@enderror
                    @error('end_date')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h2 class="card-title">Položky</h2>
                    @if(count($items) === 0)
                        <p class="mt-2 text-slate-600 dark:text-slate-300">Košík je prázdný.</p>
                    @else
                        <ul role="list" class="divide-y divide-slate-200/70 dark:divide-slate-700/50">
                            @foreach($items as $row)
                                <li class="py-4 flex items-start justify-between gap-4">
                                    <div>
                                        <div class="font-medium text-slate-900 dark:text-white">
                                            {{ $row['product']->name }}
                                            @if(($row['type'] ?? 'product') === 'bundle')
                                                <span class="ml-2 text-xs inline-flex items-center px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">Balíček</span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-slate-600 dark:text-slate-300">Cena/den: {{ number_format((float)($row['price_per_day'] ?? ($row['product']->price_per_day ?? 0)), 2, ',', ' ') }} Kč</div>
                                        @if(($row['type'] ?? 'product') === 'bundle')
                                            <div class="mt-1 text-xs text-slate-500">Obsah:
                                                <ul class="list-disc pl-5 mt-1 space-y-0.5">
                                                    @foreach($row['components'] as $comp)
                                                        <li>{{ $comp->name }} × {{ (int) $comp->pivot->amount }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        @if(!is_null($row['available']))
                                            <div class="text-xs text-slate-500">Dostupné v termínu: {{ $row['available'] }} ks</div>
                                        @endif
                                        @if(!empty($days))
                                            <div class="text-sm text-slate-700 dark:text-slate-200 mt-1">
                                                Mezisoučet: {{ number_format((float)($row['subtotal'] ?? 0), 2, ',', ' ') }} Kč
                                                <span class="text-xs text-slate-500">({{ $row['amount'] }} × {{ number_format((float)($row['price_per_day'] ?? ($row['product']->price_per_day ?? 0)), 2, ',', ' ') }} × {{ $days }} {{ $days == 1 ? 'den' : 'dny' }})</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if(($row['type'] ?? 'product') === 'bundle')
                                            <form method="post" action="{{ route('cart.updateBundle', $row['product']) }}" class="flex items-center gap-2 auto-submit-on-change">
                                                @csrf
                                                @method('PATCH')
                                                <input type="number" min="1" name="amount" value="{{ old('amount', $row['amount']) }}" class="input w-24">
                                                <button class="btn-secondary" type="submit">Upravit</button>
                                            </form>
                                            <form method="post" action="{{ route('cart.removeBundle', $row['product']) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn-danger" type="submit">Odstranit</button>
                                            </form>
                                        @else
                                            <form method="post" action="{{ route('cart.update', $row['product']) }}" class="flex items-center gap-2 auto-submit-on-change">
                                                @csrf
                                                @method('PATCH')
                                                <input type="number" min="1" name="amount" value="{{ old('amount', $row['amount']) }}" class="input w-24">
                                                <button class="btn-secondary" type="submit">Upravit</button>
                                            </form>
                                            <form method="post" action="{{ route('cart.remove', $row['product']) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn-danger" type="submit">Odstranit</button>
                                            </form>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
            <script>
                (function(){
                    // Auto-submit any form with class auto-submit-on-change when its number input changes
                    const forms = document.querySelectorAll('form.auto-submit-on-change');
                    forms.forEach(form => {
                        const input = form.querySelector('input[type="number"][name="amount"]');
                        if (input) {
                            input.addEventListener('change', () => {
                                form.submit();
                            });
                        }
                    });
                })();
            </script>
        </div>

        <div class="lg:col-span-1">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title">Souhrn</h2>
                    @if(!empty($days) && !is_null($total))
                        <div class="mt-2 text-sm text-slate-700 dark:text-slate-200">
                            <div class="flex items-center justify-between"><span>Počet dní</span><span>{{ $days }}</span></div>
                            <div class="flex items-center justify-between font-semibold mt-2"><span>Celkem</span><span>{{ number_format((float)$total, 2, ',', ' ') }} Kč</span></div>
                        </div>
                    @else
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Pro zobrazení ceny nastavte termín.</p>
                    @endif
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h2 class="card-title">Dokončit rezervaci</h2>
                    <form method="post" action="{{ route('cart.checkout') }}" class="mt-3 space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs text-slate-500">Jméno</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="input" required>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500">E-mail</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="input" required>
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500">Telefon</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="input">
                        </div>
                        <div>
                            <label class="block text-xs text-slate-500">Poznámka</label>
                            <textarea name="note" class="input" rows="3">{{ old('note') }}</textarea>
                        </div>
                        <button class="btn-primary w-full" type="submit" {{ count($items) === 0 ? 'disabled' : '' }}>Odeslat rezervaci</button>
                        @error('cart')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                        @error('dates')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                        @error('server')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
