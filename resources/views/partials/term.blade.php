@php
    // $start, $end optional (Y-m-d or datetime); $variant = 'hero' | 'bar'
    $variant = $variant ?? 'bar';
    $startVal = $start ? \Illuminate\Support\Carbon::parse($start)->format('Y-m-d') : '';
    $endVal = $end ? \Illuminate\Support\Carbon::parse($end)->format('Y-m-d') : '';
    $hero = $variant === 'hero';
@endphp
<form method="get" action="{{ route('products.index') }}" class="js-term" data-term>
    @foreach(request()->except(['start_date','end_date','page']) as $k => $v)
        @if(is_array($v))
            @foreach($v as $vv)<input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">@endforeach
        @else
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endif
    @endforeach
    <div class="flex flex-col gap-2 rounded-2xl border border-line bg-surface p-2 shadow-card sm:flex-row sm:items-stretch">
        <div class="flex flex-1 items-center gap-2 rounded-xl px-3 py-2">
            <svg class="size-5 shrink-0 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <div class="min-w-0 flex-1">
                <div class="text-[0.65rem] font-semibold uppercase tracking-wide text-faint">Od</div>
                <input type="date" name="start_date" value="{{ $startVal }}" data-start
                       class="w-full bg-transparent text-sm font-semibold text-ink focus:outline-none {{ $hero ? 'sm:text-base' : '' }}">
            </div>
        </div>
        <div class="hidden items-center text-faint sm:flex" aria-hidden="true">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </div>
        <div class="flex flex-1 items-center gap-2 rounded-xl px-3 py-2">
            <div class="min-w-0 flex-1">
                <div class="text-[0.65rem] font-semibold uppercase tracking-wide text-faint">Do</div>
                <input type="date" name="end_date" value="{{ $endVal }}" data-end
                       class="w-full bg-transparent text-sm font-semibold text-ink focus:outline-none {{ $hero ? 'sm:text-base' : '' }}">
            </div>
        </div>
        <button type="submit" class="btn-primary shrink-0 {{ $hero ? 'px-6 text-base' : '' }}">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <span>{{ $submitLabel ?? 'Zobrazit techniku' }}</span>
        </button>
    </div>
    <div class="mt-2 flex flex-wrap items-center gap-2">
        <span class="text-xs text-faint">Rychlá volba:</span>
        <button type="button" class="chip py-1" data-preset="1">Zítra, 1 den</button>
        <button type="button" class="chip py-1" data-preset="weekend">Víkend</button>
        <button type="button" class="chip py-1" data-preset="7">Týden</button>
    </div>
</form>
@once
<script>
(function () {
    function iso(d){ return d.toISOString().slice(0,10); }
    document.querySelectorAll('form.js-term').forEach(function (form) {
        var s = form.querySelector('[data-start]'), e = form.querySelector('[data-end]');
        // keep end >= start
        s.addEventListener('change', function () {
            if (s.value) { s.min && (s.min = ''); e.min = s.value; if (e.value && e.value < s.value) e.value = s.value; }
        });
        form.querySelectorAll('[data-preset]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var p = btn.getAttribute('data-preset');
                var start = new Date(); start.setDate(start.getDate() + 1);
                var end = new Date(start);
                if (p === 'weekend') {
                    // next Friday -> Sunday
                    var day = start.getDay(); var toFri = (5 - day + 7) % 7; if (toFri === 0) toFri = 7;
                    start = new Date(); start.setDate(start.getDate() + toFri);
                    end = new Date(start); end.setDate(start.getDate() + 2);
                } else {
                    end.setDate(start.getDate() + parseInt(p, 10));
                }
                s.value = iso(start); e.value = iso(end);
            });
        });
    });
})();
</script>
@endonce
