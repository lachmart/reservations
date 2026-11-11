{{-- Quick enquiry ("poptávka"). Anchor #poptavka is linked from the header. --}}
@php($enquiryErrors = $errors->hasAny(['name', 'phone', 'email', 'event_date', 'event_type', 'message']))
<section id="poptavka" class="scroll-mt-24 {{ $class ?? '' }}">
    <div class="grid gap-8 rounded-2xl border border-line bg-surface p-6 shadow-card sm:p-10 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <div class="kicker">Nezávazná poptávka</div>
            <h2 class="mt-2 font-display text-display-md font-extrabold text-ink">Napište, co chystáte — set sestavíme za vás</h2>
            <p class="mt-3 leading-relaxed text-muted">
                Stačí datum, typ akce a přibližný počet hostů. Ozveme se s návrhem techniky a cenou, obvykle ještě týž den.
            </p>
            <p class="mt-4 text-sm text-muted">
                Raději voláte? <a href="tel:{{ preg_replace('/[^0-9+]/', '', config('contact.phone')) }}" class="font-semibold text-ink hover:text-accent-ink">{{ config('contact.phone') }}</a>
            </p>
        </div>

        <div class="lg:col-span-3">
            @if(session('enquiry_sent'))
                <div class="rounded-xl border border-line p-6" role="status">
                    <div class="font-display text-xl font-bold text-ink">Děkujeme, poptávku jsme přijali.</div>
                    <p class="mt-2 text-muted">Ozveme se vám co nejdříve.</p>
                </div>
            @else
                <form method="post" action="{{ route('enquiry.store') }}#poptavka" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    {{-- Honeypot for bots; hidden from people and screen readers --}}
                    <div class="hidden" aria-hidden="true">
                        <label>Web <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    </div>

                    @if($enquiryErrors)
                        <ul class="list-inside list-disc text-sm sm:col-span-2" style="color:oklch(0.55 0.17 28)">
                            @foreach(['name', 'phone', 'email', 'event_date', 'event_type', 'message'] as $f)
                                @error($f)<li>{{ $message }}</li>@enderror
                            @endforeach
                        </ul>
                    @endif

                    <label class="block sm:col-span-2">
                        <span class="text-sm font-semibold text-ink">Jméno *</span>
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name" class="field mt-1">
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-ink">Telefon</span>
                        <input type="tel" name="phone" value="{{ old('phone') }}" maxlength="50" autocomplete="tel" class="field mt-1">
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-ink">E-mail</span>
                        <input type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" class="field mt-1">
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-ink">Datum akce</span>
                        <input type="date" name="event_date" value="{{ old('event_date') }}" class="field mt-1">
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-ink">Typ akce</span>
                        <select name="event_type" class="field mt-1">
                            <option value="">Vyberte…</option>
                            @foreach(['Svatba', 'Oslava / narozeniny', 'Ples', 'Firemní akce', 'Koncert', 'Festival', 'Divadlo', 'Konference', 'Jiné'] as $t)
                                <option value="{{ $t }}" @selected(old('event_type') === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block sm:col-span-2">
                        <span class="text-sm font-semibold text-ink">Co potřebujete? *</span>
                        <textarea name="message" rows="4" required maxlength="3000" class="field mt-1" placeholder="Např. svatba pro 120 hostů v sále, ozvučení pro DJ a proslovy, pár světel na parket.">{{ old('message') }}</textarea>
                    </label>
                    <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
                        <button type="submit" class="btn-primary px-6">Odeslat poptávku</button>
                        <span class="text-xs text-faint">Vyplňte telefon nebo e-mail. Nezávazné, nic neplatíte.</span>
                    </div>
                </form>
            @endif
        </div>
    </div>
</section>
