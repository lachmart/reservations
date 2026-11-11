@extends('layouts.app')

@section('title', 'Košík — Rezervace')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('products.index') }}" class="text-sm font-semibold text-accent-ink hover:underline dark:text-accent">← Pokračovat v prohlížení</a>
            <h1 class="mt-2 text-display-md font-extrabold text-ink">Rezervace</h1>
        </div>
        @if(count($items) > 0)
            <form method="post" action="{{ route('cart.clear') }}" onsubmit="return confirm('Opravdu vyprázdnit košík?');">
                @csrf
                @method('DELETE')
                <button class="btn-ghost text-sm" type="submit">Vyprázdnit košík</button>
            </form>
        @endif
    </div>

    <div id="cart-root" class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Term --}}
            <section class="card">
                <div class="card-body">
                    <h2 class="card-title">Termín rezervace</h2>
                    <form id="cart-dates-form" method="post" action="{{ route('cart.setDates') }}" class="mt-3">
                        @csrf
                        <input id="start_date" type="hidden" name="start_date" value="{{ $start ? \Carbon\Carbon::parse($start)->format('Y-m-d') : '' }}">
                        <input id="end_date" type="hidden" name="end_date" value="{{ $end ? \Carbon\Carbon::parse($end)->format('Y-m-d') : '' }}">
                        <div class="flex flex-col gap-2 rounded-2xl border border-line p-2 sm:flex-row sm:items-stretch" style="background:color-mix(in oklab, var(--ink) 3%, var(--surface))">
                            <label class="flex flex-1 cursor-pointer items-center gap-3 px-3 py-2">
                                <svg class="size-5 shrink-0 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-[0.65rem] font-semibold uppercase tracking-wide text-faint">Termín pronájmu</span>
                                    <input type="text" data-rangepicker readonly placeholder="Vyberte termín v kalendáři" class="w-full cursor-pointer bg-transparent text-sm font-semibold text-ink placeholder:font-medium placeholder:text-faint focus:outline-none">
                                </span>
                            </label>
                            <button class="btn-primary shrink-0" type="submit">Nastavit termín</button>
                        </div>
                    </form>
                    @if(!empty($start) && !empty($end) && !empty($days))
                        <p class="mt-3 text-sm text-muted">Zvolený termín: <span class="font-semibold text-ink">{{ \Carbon\Carbon::parse($start)->format('d.m.Y') }} → {{ \Carbon\Carbon::parse($end)->format('d.m.Y') }}</span> · {{ $days }} {{ $days == 1 ? 'den' : ($days < 5 ? 'dny' : 'dní') }}</p>
                    @else
                        <p class="mt-3 text-sm text-muted">Zadejte termín, ať vidíte dostupnost a cenu.</p>
                    @endif
                    @error('start_date')<p class="mt-2 text-sm" style="color:oklch(0.55 0.17 28)">{{ $message }}</p>@enderror
                    @error('end_date')<p class="mt-1 text-sm" style="color:oklch(0.55 0.17 28)">{{ $message }}</p>@enderror
                    <script>
                        (function(){
                            const cartRoot = document.getElementById('cart-root');
                            if (!cartRoot) return;

                            async function ajaxUpdateAmount(form){
                                const url = form.getAttribute('action');
                                const method = (form.querySelector('input[name="_method"]')?.value || form.getAttribute('method') || 'post').toLowerCase();
                                const amountInput = form.querySelector('input[name="amount"]');
                                const payload = { amount: amountInput ? parseInt(amountInput.value || '1', 10) : 1 };
                                try {
                                    const resp = await axios({ url, method: method === 'patch' ? 'patch' : method, data: payload, headers: { 'Accept': 'application/json' } });
                                    if (resp?.data) {
                                        const d = resp.data;
                                        if (amountInput) {
                                            if (d.product_is_bundle && typeof d.bundle_amount !== 'undefined') amountInput.value = d.bundle_amount;
                                            else if (!d.product_is_bundle && typeof d.standalone_amount !== 'undefined') amountInput.value = d.standalone_amount;
                                        }
                                        const badge = document.querySelector('#nav-cart-link [data-cart-count]');
                                        if (badge) { badge.textContent = String(d.cart_count || 0); badge.classList.toggle('hidden', !((d.cart_count||0) > 0)); }
                                        await refreshCartRoot();
                                        window.showToast && window.showToast(d.message || 'Množství bylo aktualizováno.');
                                    }
                                } catch (e) { window.showToast && window.showToast('Nepodařilo se aktualizovat množství.', true); }
                            }
                            async function refreshCartRoot(){
                                const res = await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                                const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                                const newRoot = doc.getElementById('cart-root');
                                if (newRoot) { cartRoot.innerHTML = newRoot.innerHTML; bindAutoSubmit(cartRoot); bindDateChange(); window.initRangePickers && window.initRangePickers(); }
                            }
                            function bindAutoSubmit(root){
                                root.querySelectorAll('form.auto-submit-on-change').forEach(form => {
                                    const input = form.querySelector('input[type="number"][name="amount"]');
                                    if (input && !input.__b) {
                                        let timer = null, last = input.value, inFlight = false;
                                        const trigger = () => { if (inFlight) return; inFlight = true; ajaxUpdateAmount(form).finally(() => inFlight = false); };
                                        input.addEventListener('input', () => { if (input.value === last) return; last = input.value; clearTimeout(timer); timer = setTimeout(trigger, 400); });
                                        input.addEventListener('change', () => { clearTimeout(timer); trigger(); });
                                        form.addEventListener('submit', (e) => { e.preventDefault(); clearTimeout(timer); trigger(); });
                                        input.__b = true;
                                    }
                                });
                            }
                            async function postDatesAndRefresh(){
                                const form = document.getElementById('cart-dates-form');
                                const start = form.querySelector('#start_date'), end = form.querySelector('#end_date');
                                if (!(start && start.value && end && end.value)) return;
                                await axios.post(form.getAttribute('action'), { start_date: start.value, end_date: end.value });
                                await refreshCartRoot();
                            }
                            function bindDateChange(){
                                const form = document.getElementById('cart-dates-form');
                                if (!form) return;
                                const start = form.querySelector('#start_date'), end = form.querySelector('#end_date');
                                const h = () => postDatesAndRefresh().catch(()=>{});
                                if (start && !start.__d) { start.addEventListener('change', h); start.__d = true; }
                                if (end && !end.__d) { end.addEventListener('change', h); end.__d = true; }
                                if (form && !form.__d) { form.addEventListener('submit', (e)=>{ e.preventDefault(); h(); }); form.__d = true; }
                            }
                            const init = () => { bindAutoSubmit(cartRoot); bindDateChange(); };
                            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
                        })();
                    </script>
                </div>
            </section>

            {{-- Items --}}
            <section class="card">
                <div class="card-body">
                    <h2 class="card-title">Položky</h2>
                    @if(count($items) === 0)
                        <div class="mt-4 rounded-lg border border-dashed border-line px-6 py-10 text-center">
                            <p class="text-muted">Košík je prázdný.</p>
                            <a href="{{ route('products.index') }}" class="btn-primary mt-4">Vybrat techniku</a>
                        </div>
                    @else
                        <ul role="list" class="mt-2 divide-y divide-[color:var(--line)]">
                            @foreach($items as $row)
                                @php($isBundle = ($row['type'] ?? 'product') === 'bundle')
                                <li class="flex items-start justify-between gap-4 py-4">
                                    <div class="min-w-0">
                                        <div class="font-display font-bold text-ink">
                                            {{ $row['product']->name }}
                                            @if($isBundle)<span class="badge-accent ml-2">Set</span>@endif
                                        </div>
                                        <div class="mt-0.5 text-sm text-muted">{{ number_format((float) ($row['price_per_day'] ?? ($row['product']->price_per_day ?? 0)), 0, ',', ' ') }} Kč / den</div>
                                        @if($isBundle)
                                            <ul class="mt-1.5 space-y-0.5 text-xs text-faint">
                                                @foreach($row['components'] as $comp)<li>· {{ $comp->name }} × {{ (int) $comp->pivot->amount }}</li>@endforeach
                                            </ul>
                                        @endif
                                        @if(!is_null($row['available']))
                                            <div class="mt-1 text-xs text-faint">Dostupné v termínu: {{ $row['available'] }} ks</div>
                                        @endif
                                        @if(!empty($days))
                                            <div class="mt-1.5 text-sm font-semibold text-ink">Mezisoučet: {{ number_format((float) ($row['subtotal'] ?? 0), 0, ',', ' ') }} Kč
                                                <span class="text-xs font-normal text-faint">({{ $row['amount'] }} × {{ $days }} {{ $days == 1 ? 'den' : 'dny' }})</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <form method="post" action="{{ $isBundle ? route('cart.updateBundle', $row['product']) : route('cart.update', $row['product']) }}" class="auto-submit-on-change">
                                            @csrf @method('PATCH')
                                            <input type="number" min="1" name="amount" value="{{ old('amount', $row['amount']) }}" class="field w-20 text-center" aria-label="Množství">
                                        </form>
                                        <form method="post" action="{{ $isBundle ? route('cart.removeBundle', $row['product']) : route('cart.remove', $row['product']) }}">
                                            @csrf @method('DELETE')
                                            <button class="btn-danger px-2.5" type="submit" aria-label="Odstranit">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <section class="card">
                <div class="card-body">
                    <h2 class="card-title">Souhrn</h2>
                    @if(!empty($days) && !is_null($total))
                        <div class="mt-3 space-y-2 text-sm">
                            <div class="flex items-center justify-between text-muted"><span>Počet dní</span><span class="font-semibold text-ink">{{ $days }}</span></div>
                            <div class="flex items-center justify-between text-muted"><span>Položek</span><span class="font-semibold text-ink">{{ array_sum(array_map(fn($r) => (int) $r['amount'], $items)) }}</span></div>
                            <div class="rule my-3"></div>
                            <div class="flex items-baseline justify-between"><span class="font-semibold text-ink">Celkem</span><span class="font-display text-2xl font-extrabold text-ink">{{ number_format((float) $total, 0, ',', ' ') }} Kč</span></div>
                        </div>
                    @else
                        <p class="mt-2 text-sm text-muted">Pro zobrazení ceny nastavte termín výše.</p>
                    @endif
                </div>
            </section>

            <section class="card">
                <div class="card-body">
                    <h2 class="card-title">Dokončit rezervaci</h2>
                    <form method="post" action="{{ route('cart.checkout') }}" class="mt-3 space-y-3">
                        @csrf
                        <div><label class="label">Jméno</label><input type="text" name="name" value="{{ old('name') }}" class="field" required></div>
                        <div><label class="label">E-mail</label><input type="email" name="email" value="{{ old('email') }}" class="field" required></div>
                        <div><label class="label">Telefon</label><input type="text" name="phone" value="{{ old('phone') }}" class="field"></div>
                        <div><label class="label">Poznámka</label><textarea name="note" class="field" rows="3">{{ old('note') }}</textarea></div>
                        <button class="btn-primary w-full py-3" type="submit" {{ count($items) === 0 ? 'disabled' : '' }}>Odeslat rezervaci</button>
                        <p class="text-center text-xs text-faint">Ozveme se vám s potvrzením a cenou.</p>
                        @error('cart')<p class="text-sm" style="color:oklch(0.55 0.17 28)">{{ $message }}</p>@enderror
                        @error('dates')<p class="text-sm" style="color:oklch(0.55 0.17 28)">{{ $message }}</p>@enderror
                        @error('server')<p class="text-sm" style="color:oklch(0.55 0.17 28)">{{ $message }}</p>@enderror
                    </form>
                </div>
            </section>
        </div>
    </div>
@endsection
