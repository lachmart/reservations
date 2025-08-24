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
    <li>Cena: {{ number_format((float) $reservation->price, 2, ',', ' ') }} Kč</li>
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
