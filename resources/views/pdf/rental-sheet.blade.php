@php
    /** @var \App\Support\RentalSheet $sheet */
    $r = $sheet->reservation;
    $rows = $sheet->rows();
    $extras = $sheet->extras();
    $date = fn ($value) => $value?->format('j. n. Y H:i');
    $zone = $r->delivery_zone ? (config("contact.delivery_zones.{$r->delivery_zone}.label") ?? $r->delivery_zone) : null;
    $transport = $zone ?? ($r->delivery ? 'Dovoz a odvoz' : 'Vyzvednutí osobně');
    $logo = public_path('images/logo.png');
    $n = 0;
@endphp
<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<title>Výpůjční list č. {{ $r->id }}</title>
<style>
    @page { margin: 12mm 13mm 15mm; }
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #1c1917; line-height: 1.35; }
    .muted { color: #78716c; }
    .head { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .head td { vertical-align: top; padding: 0; }
    h1 { font-size: 16pt; margin: 0 0 2px; text-align: right; }
    .info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .info td { vertical-align: top; width: 50%; padding: 5px 8px; border: 1px solid #d6d3d1; }
    .info .label { font-size: 7.5pt; text-transform: uppercase; letter-spacing: .04em; color: #78716c; }
    .items { width: 100%; border-collapse: collapse; }
    .items th { font-size: 7.5pt; text-transform: uppercase; letter-spacing: .04em; text-align: left; color: #57534e; border-bottom: 1.5px solid #1c1917; padding: 4px 5px; }
    .items td { border-bottom: 1px solid #d6d3d1; padding: 3px 5px; vertical-align: middle; }
    .items .num { width: 22px; color: #a8a29e; text-align: right; }
    .items .qty { width: 34px; text-align: right; font-weight: bold; white-space: nowrap; }
    .items .check { width: 50px; text-align: center; }
    .items .state { width: 140px; }
    .items tr.set td { background: #f5f5f4; font-weight: bold; }
    .items tr.part .name { padding-left: 18px; }
    .items tr.section td { padding-top: 10px; font-size: 7.5pt; text-transform: uppercase; letter-spacing: .04em; color: #57534e; border-bottom: 1.5px solid #1c1917; }
    .items tr.blank td { height: 20px; }
    .box { display: inline-block; width: 11px; height: 11px; border: 1.2px solid #44403c; }
    .sign { width: 100%; border-collapse: collapse; margin-top: 12px; page-break-inside: avoid; }
    .sign th { width: 62px; text-align: left; vertical-align: bottom; font-size: 9.5pt; padding: 0 6px 14px 0; }
    .sign td { vertical-align: top; padding: 0 0 4px 10px; font-size: 7.5pt; color: #78716c; }
    .sign td.field { width: 30%; }
    .line { border-bottom: 1px solid #a8a29e; height: 22px; margin-bottom: 2px; }
    .confirm { font-size: 7.5pt; color: #78716c; margin-top: 2px; }
    .foot { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7.5pt; color: #a8a29e; text-align: center; }
</style>
</head>
<body>
<div class="foot">
    Maraka rental · {{ config('contact.phone') }} · {{ config('contact.email') }} · IČO {{ config('contact.ico') }} · {{ config('contact.address') }}
</div>

<table class="head">
    <tr>
        <td>
            @if (is_file($logo))
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents($logo)) }}" alt="Maraka rental" style="height: 34px;">
            @else
                <strong style="font-size: 14pt;">Maraka rental</strong>
            @endif
        </td>
        <td style="text-align: right;">
            <h1>Výpůjční list č. {{ $r->id }}</h1>
            <div class="muted">Vytištěno {{ $date(now()) }}</div>
        </td>
    </tr>
</table>

<table class="info">
    <tr>
        <td>
            <div class="label">Zákazník</div>
            <strong>{{ $r->name }}</strong><br>
            @if ($r->phone){{ $r->phone }}<br>@endif
            @if ($r->email){{ $r->email }}@endif
        </td>
        <td>
            <div class="label">Akce</div>
            <strong>{{ $date($r->start_date) }} – {{ $date($r->end_date) }}</strong><br>
            @if ($r->event_place || $r->event_type){{ collect([$r->event_type, $r->event_place])->filter()->implode(', ') }}<br>@endif
            {{ $transport }}
        </td>
    </tr>
    @if (filled($r->note))
        <tr><td colspan="2"><span class="label">Poznámka</span> {{ $r->note }}</td></tr>
    @endif
</table>

<table class="items">
    <thead>
        <tr>
            <th class="num">#</th>
            <th>Položka</th>
            <th class="qty">Ks</th>
            <th class="check">Vydáno</th>
            <th class="check">Vráceno</th>
            <th class="state">Poznámka / stav</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            @if ($row['kind'] === 'set')
                <tr class="set">
                    <td class="num"></td>
                    <td class="name">{{ $row['name'] }}</td>
                    <td class="qty">{{ $row['amount'] }}×</td>
                    <td class="check"></td>
                    <td class="check"></td>
                    <td class="state"></td>
                </tr>
            @else
                <tr class="{{ $row['kind'] }}">
                    <td class="num">{{ ++$n }}</td>
                    <td class="name">{{ $row['name'] }}</td>
                    <td class="qty">{{ $row['amount'] }}</td>
                    <td class="check"><span class="box"></span></td>
                    <td class="check"><span class="box"></span></td>
                    <td class="state"></td>
                </tr>
            @endif
        @endforeach

        @if ($extras)
            <tr class="section"><td colspan="6">Přidáno na skladě</td></tr>
            @foreach ($extras as $extra)
                <tr>
                    <td class="num">{{ ++$n }}</td>
                    <td class="name">{{ $extra['name'] }}</td>
                    <td class="qty">{{ $extra['amount'] }}</td>
                    <td class="check"><span class="box"></span></td>
                    <td class="check"><span class="box"></span></td>
                    <td class="state"></td>
                </tr>
            @endforeach
        @endif

        @for ($i = 0; $i < \App\Support\RentalSheet::BLANK_ROWS; $i++)
            <tr class="blank">
                <td class="num">{{ ++$n }}</td>
                <td class="name"></td>
                <td class="qty"></td>
                <td class="check"><span class="box"></span></td>
                <td class="check"><span class="box"></span></td>
                <td class="state"></td>
            </tr>
        @endfor
    </tbody>
</table>

<table class="sign">
    <tr>
        <th>Výdej</th>
        <td class="field"><div class="line"></div>Datum a čas</td>
        <td class="field"><div class="line"></div>Vydal (Maraka)</td>
        <td class="field"><div class="line"></div>Převzal (zákazník)</td>
    </tr>
    <tr>
        <th>Vrácení</th>
        <td class="field"><div class="line"></div>Datum a čas</td>
        <td class="field"><div class="line"></div>Vrátil (zákazník)</td>
        <td class="field"><div class="line"></div>Převzal zpět (Maraka)</td>
    </tr>
    <tr>
        <td colspan="4" class="confirm" style="padding-left: 0;">Podpisem při výdeji zákazník potvrzuje, že vybavení převzal kompletní a v pořádku.</td>
    </tr>
</table>
</body>
</html>
