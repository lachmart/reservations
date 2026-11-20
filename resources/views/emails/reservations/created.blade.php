<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Potvrzení rezervace</title>
</head>
<body style="font-family: Arial, sans-serif; color:#0f172a;">
<h2>Potvrzení rezervace</h2>
<p>Dobrý den {{ \App\Support\Czech::vocative($reservation->name) ?: $reservation->name }},</p>
<p>děkujeme za Vaši rezervaci. Níže najdete souhrn:</p>
<ul>
    <li>Začátek: {{ optional($reservation->start_date)->format('d.m.Y H:i') }}</li>
    <li>Konec: {{ optional($reservation->end_date)->format('d.m.Y H:i') }}</li>
    <li>Cena za techniku: {{ number_format((float) $reservation->price, 2, ',', ' ') }} Kč</li>
    @if($reservation->delivery)
        <li>Dovoz a zapojení: ano (cenu podle místa potvrdíme)</li>
    @else
        <li>Dovoz a zapojení: ne, vyzvednutí osobně</li>
    @endif
    @if(!empty($reservation->event_type))
        <li>Typ akce: {{ $reservation->event_type }}</li>
    @endif
    @if(!empty($reservation->event_place))
        <li>Místo akce: {{ $reservation->event_place }}</li>
    @endif
</ul>

<h3>Kontakt</h3>
<ul>
    <li>Jméno: {{ $reservation->name }}</li>
    @if(!empty($reservation->phone))
        <li>Telefon: {{ $reservation->phone }}</li>
    @endif
    @if(!empty($reservation->email))
        <li>E-mail: {{ $reservation->email }}</li>
    @endif
</ul>

@if($reservation->reservationItems && $reservation->reservationItems->count())
    <h3>Položky rezervace</h3>
    <ul>
        @foreach($reservation->reservationItems as $item)
            <li>
                {{ optional($item->product)->name ?? ('Produkt #' . $item->product_id) }}
                — množství: {{ (int) $item->amount }} ks
            </li>
        @endforeach
    </ul>
@endif

@if(!empty($reservation->note))
<p>Poznámka: {{ $reservation->note }}</p>
@endif
<p>V případě dotazů nás neváhejte kontaktovat.</p>
<p>S pozdravem,<br>Rezervační tým</p>
</body>
</html>
