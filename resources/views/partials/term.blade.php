@php
    $variant = $variant ?? 'bar';
    $startVal = $start ? \Illuminate\Support\Carbon::parse($start)->format('Y-m-d') : '';
    $endVal = $end ? \Illuminate\Support\Carbon::parse($end)->format('Y-m-d') : '';
    $hero = $variant === 'hero';
@endphp
<form method="get" action="{{ route('products.index') }}" class="js-term" data-term-scope>
    @foreach(request()->except(['start_date','end_date','page']) as $k => $v)
        @if(is_array($v))
            @foreach($v as $vv)<input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">@endforeach
        @else
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endif
    @endforeach
    <input type="hidden" name="start_date" value="{{ $startVal }}">
    <input type="hidden" name="end_date" value="{{ $endVal }}">

    <div class="flex flex-col gap-2 rounded-2xl border border-line bg-surface p-2 shadow-card sm:flex-row sm:items-stretch">
        <label class="flex flex-1 cursor-pointer items-center gap-3 rounded-xl px-3 py-2">
            <svg class="size-5 shrink-0 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <span class="min-w-0 flex-1">
                <span class="block text-[0.65rem] font-semibold uppercase tracking-wide text-faint">Termín pronájmu</span>
                <input type="text" data-rangepicker readonly placeholder="Vyberte termín v kalendáři"
                       class="w-full cursor-pointer bg-transparent text-sm font-semibold text-ink placeholder:font-medium placeholder:text-faint focus:outline-none {{ $hero ? 'sm:text-base' : '' }}">
            </span>
        </label>
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
    function pad(n){ return String(n).padStart(2,'0'); }
    function iso(d){ return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate()); }
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('form.js-term [data-preset]');
        if (!btn) return;
        var form = btn.closest('form.js-term');
        var picker = form.querySelector('[data-rangepicker]');
        if (!picker || !picker._flatpickr) return;
        var p = btn.getAttribute('data-preset');
        var start = new Date(); start.setDate(start.getDate() + 1);
        var end = new Date(start);
        if (p === 'weekend') {
            var toFri = (5 - start.getDay() + 7) % 7; if (toFri === 0) toFri = 7;
            start = new Date(); start.setDate(start.getDate() + toFri);
            end = new Date(start); end.setDate(start.getDate() + 2);
        } else {
            end.setDate(start.getDate() + parseInt(p, 10));
        }
        picker._flatpickr.setDate([iso(start), iso(end)], true);
    });
})();
</script>
@endonce
